<?php

namespace App\Manager;

use App\Entity\Pegass;
use App\Entity\Structure;
use App\Entity\Volunteer;

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
     * @var VolunteerManager
     */
    private $volunteerManager;

    /**
     * @param PegassManager    $pegassManager
     * @param StructureManager $structureManager
     * @param VolunteerManager $volunteerManager
     */
    public function __construct(PegassManager $pegassManager,
        StructureManager $structureManager,
        VolunteerManager $volunteerManager)
    {
        $this->pegassManager    = $pegassManager;
        $this->structureManager = $structureManager;
        $this->volunteerManager = $volunteerManager;
    }

    public function refresh()
    {
        $this->refreshStructures();
        $this->refreshVolunteers();
    }

    public function refreshStructures()
    {
        $this->disableInactiveStructures();

        // Import or refresh structures
        $this->pegassManager->foreach(Pegass::TYPE_STRUCTURE, function (Pegass $pegass) {
            $this->refreshStructure($pegass);
        });

        $this->refreshParentStructures();
    }

    /**
     * @param Pegass $pegass
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function refreshStructure(Pegass $pegass)
    {
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
    }

    public function refreshParentStructures()
    {
        $this->pegassManager->foreach(Pegass::TYPE_STRUCTURE, function (Pegass $pegass) {
            if ($parentId = $pegass->evaluate('structure.parent.id')) {
                $structure = $this->structureManager->getStructureByIdentifier($pegass->getIdentifier());

                if ($structure->getParentStructure() && $parentId === $structure->getParentStructure()->getIdentifier()) {
                    return;
                }

                if ($parent = $this->structureManager->getStructureByIdentifier($parentId)) {
                    $structure->setParentStructure($parent);
                    $this->structureManager->save($structure);
                }
            }
        });
    }

    public function disableInactiveStructures()
    {
        $redcallStructures = $this->structureManager->listStructureIdentifiers();
        $pegassStructures  = $this->pegassManager->listIdentifiers(Pegass::TYPE_STRUCTURE);
        foreach (array_diff($redcallStructures, $pegassStructures) as $structureIdentiifer) {
            $this->structureManager->disableByIdentifier($structureIdentiifer);
        }
    }

    public function refreshVolunteers()
    {
        $this->disableInactiveVolunteers();

        $this->pegassManager->foreach(Pegass::TYPE_VOLUNTEER, function (Pegass $pegass) {
            $this->refreshVolunteer($pegass);
        });
    }

    public function disableInactiveVolunteers()
    {
        $redcallVolunteers = $this->volunteerManager->listVolunteerNivols();
        $pegassVolunteers  = $this->pegassManager->listIdentifiers(Pegass::TYPE_VOLUNTEER);
        foreach (array_diff($redcallVolunteers, $pegassVolunteers) as $volunteerIdentiifer) {
            $volunteer = $this->volunteerManager->findOneByNivol($volunteerIdentiifer);

            if ($volunteer->isLocked()) {
                $volunteer->setReport([]);
                $volunteer->addWarning('Cannot update a locked volunteer.');
            } else {
                $volunteer->setEnabled(false);
            }

            $this->volunteerManager->save($volunteer);
        }
    }

    /**
     * @param Pegass $pegass
     */
    public function refreshVolunteer(Pegass $pegass)
    {
        // Create or update?
        $volunteer = $this->volunteerManager->findOneByNivol($pegass->getIdentifier());
        if (!$volunteer) {
            $volunteer = new Volunteer();
        }

        // Volunteer is locked
        if ($volunteer->isLocked()) {
            $volunteer->setReport([]);
            $volunteer->addWarning('import_report.locked');
            $this->volunteerManager->save($volunteer);

            return;
        }

        // Volunteer already up to date
        if ($volunteer->getLastPegassUpdate()
            && $volunteer->getLastPegassUpdate()->getTimestamp() === $pegass->getUpdatedAt()->getTimestamp()) {
            return;
        }
        $volunteer->setLastPegassUpdate(clone $pegass->getUpdatedAt());

        // Updating basic information
        $volunteer->setNivol(ltrim($pegass->evaluate('infos.id'), '0'));
        $volunteer->setFirstName($this->normalizeName($pegass->evaluate('user.prenom')));
        $volunteer->setLastName($this->normalizeName($pegass->evaluate('user.nom')));
        $volunteer->setEnabled($pegass->evaluate('user.actif'));

        // Some issues may lead to not contact a volunteer properly
        if (!$volunteer->getPhoneNumber() && !$volunteer->getEmail()) {
            $volunteer->addError('import_report.no_contact');
            $volunteer->setEnabled(false);
        } elseif (!$volunteer->getPhoneNumber()) {
            $volunteer->addWarning('import_report.no_phone');
        } elseif (!$volunteer->getEmail()) {
            $volunteer->addWarning('import_report.no_email');
        }

        // Disabling minors
        if ($volunteer->isMinor()) {
            $volunteer->addError('import_report.minor');
            $volunteer->setEnabled(false);
        }

        $this->volunteerManager->save($volunteer);

        die();

    }

    /**
     * @param string $name
     *
     * @return string
     */
    private function normalizeName(string $name): string
    {
        return sprintf('%s%s',
            mb_strtoupper(mb_substr($name, 0, 1)),
            mb_strtolower(mb_substr($name, 1))
        );
    }
}