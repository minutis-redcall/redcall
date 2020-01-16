<?php

namespace App\Manager;

use App\Entity\Pegass;
use App\Entity\Structure;

class RefreshManager
{
    /**
     * @var PegassManager
     */
    private $pegassManager;

    /**
     * @var StructureManager
     */
    private $structureManager;

    /**
     * @param PegassManager    $pegassManager
     * @param StructureManager $structureManager
     */
    public function __construct(PegassManager $pegassManager, StructureManager $structureManager)
    {
        $this->pegassManager    = $pegassManager;
        $this->structureManager = $structureManager;
    }

    public function refresh()
    {
        $this->refreshStructures();
        $this->refreshVolunteers();
    }

    /**
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function refreshStructures()
    {
        // Disable structures that do not exist anymore
        $redcallStructures = $this->structureManager->listStructureIdentifiers();
        $pegassStructures  = $this->pegassManager->listIdentifiers(Pegass::TYPE_STRUCTURE);
        foreach (array_diff($redcallStructures, $pegassStructures) as $structureIdentiifer) {
            $this->structureManager->disableByIdentifier($structureIdentiifer);
        }

        // Import or update structures
        $this->pegassManager->foreach(Pegass::TYPE_STRUCTURE, function (Pegass $pegass) {
            $structure = $this->structureManager->getStructureByIdentifier($pegass->getIdentifier());
            if (!$structure) {
                $structure = new Structure();
            }

            $structure->setIdentifier($pegass->evaluate('structure.id'));
            $structure->setType($pegass->evaluate('structure.typeStructure'));
            $structure->setName($pegass->evaluate('structure.libelle'));
            $structure->setPresident($pegass->evaluate('responsible.responsableId'));
            $structure->setEnabled(true);
            $this->structureManager->save($structure);
        });
    }

    public function refreshVolunteers()
    {
        $this->pegassManager->foreach(Pegass::TYPE_VOLUNTEER, function (Pegass $entity) {

        });
    }
}