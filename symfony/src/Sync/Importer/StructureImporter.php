<?php

namespace App\Sync\Importer;

use App\Entity\Structure;
use App\Manager\StructureManager;
use App\Sync\Dto\StructureRow;

class StructureImporter
{
    private StructureManager $structureManager;

    public function __construct(StructureManager $structureManager)
    {
        $this->structureManager = $structureManager;
    }

    public function import(StructureRow $row, ?\DateTimeImmutable $syncedAt = null) : void
    {
        $structure = $this->structureManager->findOneByExternalId($row->id);

        if ($structure && $structure->isLocked()) {
            return;
        }

        if (!$structure) {
            $structure = new Structure();
            $structure->setExternalId($row->id);
        }

        if ($row->parentId !== null) {
            $parent = $this->structureManager->findOneByExternalId($row->parentId);
            if ($parent) {
                $structure->setParentStructure($parent);
            }
        }

        $structure->setName($this->decodeEntities($row->label));
        $structure->setShortcut($this->decodeEntities($row->shortLabel));
        $structure->setEnabled(true);
        $structure->setLastSyncedAt(\DateTime::createFromImmutable($syncedAt ?? new \DateTimeImmutable()));

        $this->structureManager->save($structure);
    }

    private function decodeEntities(string $value) : string
    {
        return html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
