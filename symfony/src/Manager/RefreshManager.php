<?php

namespace App\Manager;

use App\Entity\Structure;
use App\Entity\Tag;
use App\Entity\Volunteer;
use App\Tools\PhoneNumberParser;
use Bundles\PegassCrawlerBundle\Entity\Pegass;
use Bundles\PegassCrawlerBundle\Manager\PegassManager;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Refreshes Redcall database based on Pegass cache
 */
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
     * @var TagManager
     */
    private $tagManager;

    /**
     * @var UserInformationManager
     */
    private $userInformationManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param PegassManager          $pegassManager
     * @param StructureManager       $structureManager
     * @param VolunteerManager       $volunteerManager
     * @param TagManager             $tagManager
     * @param UserInformationManager $userInformationManager
     * @param LoggerInterface|null   $logger
     */
    public function __construct(PegassManager $pegassManager,
        StructureManager $structureManager,
        VolunteerManager $volunteerManager,
        TagManager $tagManager,
        UserInformationManager $userInformationManager,
        LoggerInterface $logger = null)
    {
        $this->pegassManager          = $pegassManager;
        $this->structureManager       = $structureManager;
        $this->volunteerManager       = $volunteerManager;
        $this->tagManager             = $tagManager;
        $this->userInformationManager = $userInformationManager;
        $this->logger                 = $logger ?: new NullLogger();
    }

    /**
     * Refreshes everything, it is an heavy operation.
     */
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
        $structure = $this->structureManager->findOneByIdentifier($pegass->getIdentifier());
        if (!$structure) {
            $structure = new Structure();
        }

        // Structure already up to date
        if ($structure->getLastPegassUpdate()
            && $structure->getLastPegassUpdate()->getTimestamp() === $pegass->getUpdatedAt()->getTimestamp()) {
            return;
        }
        $structure->setLastPegassUpdate(clone $pegass->getUpdatedAt());
        $structure->setEnabled(true);

        $this->logger->info('Updating a structure', [
            'type'              => $pegass->getType(),
            'identifier'        => $pegass->getIdentifier(),
            'parent-identifier' => $pegass->getParentIdentifier(),
        ]);

        $structure->setIdentifier($pegass->evaluate('structure.id'));
        $structure->setType($pegass->evaluate('structure.typeStructure'));
        $structure->setName($pegass->evaluate('structure.libelle'));
        $structure->setPresident(ltrim($pegass->evaluate('responsible.responsableId'), '0'));
        $this->structureManager->save($structure);
    }

    public function refreshParentStructures()
    {
        $this->pegassManager->foreach(Pegass::TYPE_STRUCTURE, function (Pegass $pegass) {
            if ($parentId = $pegass->evaluate('structure.parent.id')) {
                $structure = $this->structureManager->findOneByIdentifier($pegass->getIdentifier());

                if ($structure->getParentStructure() && $parentId === $structure->getParentStructure()->getIdentifier()) {
                    return;
                }

                if ($parent = $this->structureManager->findOneByIdentifier($parentId)) {
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
            // Volunteer is invalid (ex: 00000048004C)
            if (!$pegass->evaluate('user.id')) {
                return;
            }

            $this->refreshVolunteer($pegass);
        });
    }

    public function disableInactiveVolunteers()
    {
        $redcallVolunteers = $this->volunteerManager->listVolunteerNivols();

        $pegassVolunteers = array_map(function (string $identifier) {
            return ltrim($identifier, '0');
        }, $this->pegassManager->listIdentifiers(Pegass::TYPE_VOLUNTEER));

        foreach (array_diff($redcallVolunteers, $pegassVolunteers) as $volunteerIdentiifer) {
            $volunteer = $this->volunteerManager->findOneByNivol($volunteerIdentiifer);

            if ($volunteer->isLocked()) {
                $volunteer->setReport([]);
                $volunteer->addReportMessage('import_report.disable_locked');
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
        $volunteer->setReport([]);

        // Volunteer is locked
        if ($volunteer->isLocked()) {
            $volunteer->addReportMessage('import_report.update_locked');
            $this->volunteerManager->save($volunteer);

            return;
        }

        // Volunteer already up to date
        if ($volunteer->getLastPegassUpdate()
            && $volunteer->getLastPegassUpdate()->getTimestamp() === $pegass->getUpdatedAt()->getTimestamp()) {
            return;
        }

        $this->logger->info('Updating a volunteer', [
            'type'              => $pegass->getType(),
            'identifier'        => $pegass->getIdentifier(),
            'parent-identifier' => $pegass->getParentIdentifier(),
        ]);

        $volunteer->setLastPegassUpdate(clone $pegass->getUpdatedAt());

        $enabled = $pegass->evaluate('user.actif');
        if (!$enabled) {
            $volunteer->addReportMessage('import_report.disabled');
        }
        $volunteer->setEnabled($enabled);

        // Update basic information
        $volunteer->setNivol(ltrim($pegass->evaluate('infos.id'), '0'));
        $volunteer->setFirstName($this->normalizeName($pegass->evaluate('user.prenom')));
        $volunteer->setLastName($this->normalizeName($pegass->evaluate('user.nom')));
        $volunteer->setPhoneNumber($this->fetchPhoneNumber($pegass->evaluate('contact')));
        $volunteer->setEmail($this->fetchEmail($pegass->evaluate('contact')));

        // Update volunteer skills
        $skills = $this->fetchSkills($pegass);
        foreach ($skills as $skill) {
            if (!$volunteer->hasTag($skill)) {
                $volunteer->getTags()->add($this->tagManager->findOneByLabel($skill));
            }
        }
        foreach ($volunteer->getTags() as $tag) {
            if (!in_array($tag->getLabel(), $skills)) {
                $volunteer->getTags()->removeElement($tag);
            }
        }

        // Update structures
        $volunteer->getStructures()->clear();
        foreach (array_filter(explode('|', $pegass->getParentIdentifier())) as $identifier) {
            if ($structure = $this->structureManager->findOneByIdentifier($identifier)) {
                $volunteer->addStructure($structure);
            }
        }

        // Some issues may lead to not contact a volunteer properly
        if (!$volunteer->getPhoneNumber() && !$volunteer->getEmail()) {
            $volunteer->addReportMessage('import_report.no_contact');
            $volunteer->setEnabled(false);
        } elseif (!$volunteer->getPhoneNumber()) {
            $volunteer->addReportMessage('import_report.no_phone');
        } elseif (!$volunteer->getEmail()) {
            $volunteer->addReportMessage('import_report.no_email');
        }

        // Disabling minors
        if ($volunteer->isMinor()) {
            $volunteer->addReportMessage('import_report.minor');
            $volunteer->setEnabled(false);
        }

        $this->volunteerManager->save($volunteer);

        // If volunteer is bound to a RedCall user, update its structures
        $userInformation = $this->userInformationManager->findOneByNivol($volunteer->getNivol());
        if ($userInformation) {
            $this->userInformationManager->updateNivol($userInformation, $volunteer->getNivol());
        }
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

    /**
     * @param array $contact
     *
     * @return string|null
     */
    private function fetchPhoneNumber(array $contact): ?string
    {
        $phoneKeys = ['POR', 'PORT', 'PORE', 'TELDOM', 'TELTRAV'];

        // Filter out keys that are not phones
        $contact = array_filter($contact, function ($data) use ($phoneKeys) {
            return in_array($data['moyenComId'] ?? [], $phoneKeys)
                   && PhoneNumberParser::parse($data['libelle'] ?? false);
        });

        // Order phones in order to take work phone last
        usort($contact, function ($a, $b) use ($phoneKeys) {
            return array_search($a['moyenComId'], $phoneKeys) <=> array_search($b['moyenComId'], $phoneKeys);
        });

        if (!$contact) {
            return null;
        }

        return PhoneNumberParser::parse(reset($contact)['libelle']);
    }

    /**
     * @param array $contact
     *
     * @return string|null
     */
    private function fetchEmail(array $contact): ?string
    {
        $emailKeys = ['MAIL', 'MAILDOM', 'MAILTRAV'];

        // Filter out keys that are not emails
        $contact = array_filter($contact, function ($data) use ($emailKeys) {
            return in_array($data['moyenComId'] ?? [], $emailKeys)
                   && preg_match('/^.+\@.+\..+$/', $data['libelle'] ?? false);
        });

        // Order emails
        usort($contact, function ($a, $b) use ($emailKeys) {

            // Red cross emails should be put last
            foreach (Volunteer::RED_CROSS_DOMAINS as $domain) {
                if (false !== stripos($a['libelle'] ?? false, $domain)) {
                    return 1;
                }
                if (false !== stripos($b['libelle'] ?? false, $domain)) {
                    return -1;
                }
            }

            return array_search($a['moyenComId'], $emailKeys) <=> array_search($b['moyenComId'], $emailKeys);
        });

        if (!$contact) {
            return null;
        }

        return reset($contact)['libelle'];
    }

    /**
     * @param Pegass $pegass
     *
     * @return array
     */
    private function fetchSkills(Pegass $pegass)
    {
        $skills = [];

        // US, AS
        foreach ($pegass->evaluate('actions') as $action) {
            if (1 == ($action['groupeAction']['id'] ?? false)) {
                $skills[] = Tag::TAG_EMERGENCY_ASSISTANCE;
            }

            if (2 == ($action['groupeAction']['id'] ?? false)) {
                $skills[] = Tag::TAG_SOCIAL_ASSISTANCE;
            }
        }

        // VL, VPSP
        foreach ($pegass->evaluate('skills') as $skill) {
            if (9 == ($skill['id'] ?? false)) {
                $skills[] = Tag::TAG_DRVR_VL;
            }

            if (10 == ($skill['id'] ?? false)) {
                $skills[] = Tag::TAG_DRVR_VPSP;
            }
        }

        // PSC1, PSE1, PSE2, CI
        foreach ($pegass->evaluate('trainings') as $training) {
            if (in_array($training['formation']['code'] ?? false, ['RECCI', 'CI', 'CIP3'])) {
                $skills[] = Tag::TAG_CI;
            }

            if (in_array($training['formation']['code'] ?? false, ['RECPSE2', 'PSE2'])) {
                $skills[] = Tag::TAG_PSE_2;
            }

            if (in_array($training['formation']['code'] ?? false, ['RECPSE1', 'PSE1'])) {
                $skills[] = Tag::TAG_PSE_1;
            }

            if (in_array($training['formation']['code'] ?? false, ['RECPSC1', 'PSC1'])) {
                $skills[] = Tag::TAG_PSC_1;
            }
        }

        return $skills;
    }
}