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
 *
 * Query to check inconsistencies:
 *
 * select count(*)
 * from pegass p
 * left join volunteer v on v.nivol = trim(leading '0' from p.identifier)
 * where p.type = 'volunteer'
 * and p.enabled = 1
 * and v.id is null
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

    public function refresh(bool $force)
    {
        $this->refreshStructures($force);
        $this->refreshVolunteers($force);
    }

    public function refreshStructures(bool $force)
    {
        $this->disableInactiveStructures();

        // Import or refresh structures
        $this->pegassManager->foreach(Pegass::TYPE_STRUCTURE, function (Pegass $pegass) use ($force) {
            $this->debug('Walking through a structure', [
                'identifier' => $pegass->getIdentifier(),
            ]);

            $this->refreshStructure($pegass, $force);
        });

        $this->refreshParentStructures();
    }

    public function disableInactiveStructures()
    {
        $this->debug('Disabling inactive structures');

        // RedCall structures that are enabled
        $redcallStructures = $this->structureManager->listStructureIdentifiers();

        // Pegass structures that are enabled
        $pegassStructures = $this->pegassManager->listIdentifiers(Pegass::TYPE_STRUCTURE, true);

        // We try to enable Pegass structures not in RedCall structures
        foreach (array_diff($pegassStructures, $redcallStructures) as $structureIdentiifer) {
            if (0 === $structureIdentiifer) {
                continue;
            }

            $this->debug('Enabling a structure', [
                'identifier' => $structureIdentiifer,
            ]);

            $this->structureManager->enableByIdentifier($structureIdentiifer);
        }

        // We disable RedCall structures not in Pegass structures
        foreach (array_diff($redcallStructures, $pegassStructures) as $structureIdentiifer) {
            if (0 === $structureIdentiifer) {
                continue;
            }

            $this->debug('Disabling a structure', [
                'identifier' => $structureIdentiifer,
            ]);

            $this->structureManager->disableByIdentifier($structureIdentiifer);
        }
    }

    public function refreshStructure(Pegass $pegass, bool $force)
    {
        if (!$pegass->evaluate('structure.id')) {
            return;
        }

        $structure = $this->structureManager->findOneByIdentifier($pegass->getIdentifier());
        if (!$structure) {
            $structure = new Structure();
        }

        // Structure already up to date
        if (!$force && $structure->getLastPegassUpdate()
            && $structure->getLastPegassUpdate()->getTimestamp() === $pegass->getUpdatedAt()->getTimestamp()) {
            return;
        }

        $structure->setLastPegassUpdate(clone $pegass->getUpdatedAt());
        $structure->setEnabled(true);

        $this->debug('Updating a structure', [
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
            $this->debug('Updating parent structures for a structure', [
                'identifier'        => $pegass->getIdentifier(),
                'parent_identifier' => $pegass->getParentIdentifier(),
            ]);

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

    public function refreshVolunteers(bool $force)
    {
        $this->disableInactiveVolunteers();

        $this->pegassManager->foreach(Pegass::TYPE_VOLUNTEER, function (Pegass $pegass) use ($force) {
            $this->debug('Walking through a volunteer', [
                'identifier' => $pegass->getIdentifier(),
            ]);

            // Volunteer is invalid (ex: 00000048004C)
            if (!$pegass->evaluate('user.id')) {
                return;
            }

            $this->refreshVolunteer($pegass, $force);
        });
    }

    public function disableInactiveVolunteers()
    {
        $this->debug('Disabling inactive volunteers');
        $redcallVolunteers = $this->volunteerManager->listVolunteerNivols();

        $pegassVolunteers = array_map(function (string $identifier) {
            return ltrim($identifier, '0');
        }, $this->pegassManager->listIdentifiers(Pegass::TYPE_VOLUNTEER));

        foreach (array_diff($redcallVolunteers, $pegassVolunteers) as $volunteerIdentiifer) {
            $this->debug('Disabling a volunteer', [
                'identifier' => $volunteerIdentiifer,
            ]);

            $volunteer = $this->volunteerManager->findOneByNivol($volunteerIdentiifer);

            if ($volunteer->isLocked()) {
                $volunteer->setReport([]);
                $volunteer->addReport('import_report.disable_locked');
            }

            // Even if locked, it's important to disable the volunteer if it is inactive
            // except if it has been created manually.
            if ('2100-12-31' !== $volunteer->getLastPegassUpdate()->format('Y-m-d')) {
                $volunteer->setEnabled(false);
            }

            $this->volunteerManager->save($volunteer);
        }
    }

    public function refreshVolunteer(Pegass $pegass, bool $force)
    {
        // Create or update?
        $volunteer = $this->volunteerManager->findOneByNivol($pegass->getIdentifier());
        if (!$volunteer) {
            $volunteer = new Volunteer();
        }

        $volunteer->setIdentifier($pegass->getIdentifier());
        $volunteer->setNivol(ltrim($pegass->getIdentifier(), '0'));
        $volunteer->setReport([]);

        // Update structures based on where volunteer was found while crawling structures
        foreach (array_filter(explode('|', $pegass->getParentIdentifier())) as $identifier) {
            if ($structure = $this->structureManager->findOneByIdentifier($identifier)) {
                $volunteer->addStructure($structure);
            }
        }

        // Add structures based on the actions performed by the volunteer
        $identifiers = [];
        foreach ($pegass->evaluate('actions') ?? [] as $action) {
            if (isset($action['structure']['identifier']) && !in_array($action['structure']['identifier'], $identifiers)) {
                if ($structure = $this->structureManager->findOneByIdentifier($action['structure']['identifier'])) {
                    $volunteer->addStructure($structure);
                }
                $identifiers[] = $action['structure']['identifier'];
            }
        }

        // Volunteer is locked
        if ($volunteer->isLocked()) {
            $volunteer->addReport('import_report.update_locked');
            $this->volunteerManager->save($volunteer);

            // If volunteer is bound to a RedCall user, update its structures
            $userInformation = $this->userInformationManager->findOneByNivol($volunteer->getNivol());
            if ($userInformation) {
                $this->userInformationManager->updateNivol($userInformation, $volunteer->getNivol());
            }

            return;
        }

        // Volunteer already up to date
        if (!$force && $volunteer->getLastPegassUpdate()
            && $volunteer->getLastPegassUpdate()->getTimestamp() === $pegass->getUpdatedAt()->getTimestamp()) {
            $this->volunteerManager->save($volunteer);

            return;
        }

        $this->debug('Updating a volunteer', [
            'type'              => $pegass->getType(),
            'identifier'        => $pegass->getIdentifier(),
            'parent-identifier' => $pegass->getParentIdentifier(),
        ]);

        $volunteer->setLastPegassUpdate(clone $pegass->getUpdatedAt());

        if (!$pegass->evaluate('user.id')) {
            $volunteer->addReport('import_report.failed');
            $this->volunteerManager->save($volunteer);

            return;
        }

        $enabled = $pegass->evaluate('user.actif');
        if (!$enabled) {
            $volunteer->addReport('import_report.disabled');
        }
        $volunteer->setEnabled($enabled ?? false);

        // Update basic information
        $volunteer->setFirstName($this->normalizeName($pegass->evaluate('user.prenom')));
        $volunteer->setLastName($this->normalizeName($pegass->evaluate('user.nom')));
        $volunteer->setPhoneNumber($this->fetchPhoneNumber($pegass->evaluate('contact')));
        $volunteer->setEmail($this->fetchEmail($pegass->evaluate('infos'), $pegass->evaluate('contact')));

        // Update volunteer skills
        $skills = $this->fetchSkills($pegass);
        foreach ($skills as $skill) {
            $volunteer->addTag(
                $this->tagManager->findOneByLabel($skill)
            );
        }
        foreach ($volunteer->getTags() as $tag) {
            if (!in_array($tag->getLabel(), $skills)) {
                $volunteer->removeTag($tag);
            }
        }

        // Some issues may lead to not contact a volunteer properly
        if (!$volunteer->getPhoneNumber() && !$volunteer->getEmail()) {
            $volunteer->addReport('import_report.no_contact');
            $volunteer->setEnabled(false);
        } elseif (!$volunteer->getPhoneNumber()) {
            $volunteer->addReport('import_report.no_phone');
        } elseif (!$volunteer->getEmail()) {
            $volunteer->addReport('import_report.no_email');
        }

        // Disabling minors
        if ($volunteer->isMinor()) {
            $volunteer->addReport('import_report.minor');
            $volunteer->setEnabled(false);
        }

        $this->volunteerManager->save($volunteer);

        // If volunteer is bound to a RedCall user, update its structures
        $userInformation = $this->userInformationManager->findOneByNivol($volunteer->getNivol());
        if ($userInformation) {
            $this->userInformationManager->updateNivol($userInformation, $volunteer->getNivol());
        }
    }

    private function normalizeName(string $name): string
    {
        return sprintf('%s%s',
            mb_strtoupper(mb_substr($name, 0, 1)),
            mb_strtolower(mb_substr($name, 1))
        );
    }

    private function fetchPhoneNumber(array $contact): ?string
    {
        $phoneKeys = ['POR', 'PORT', 'TELDOM', 'TELTRAV', 'PORE'];

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

    private function fetchEmail(array $infos, array $contact): ?string
    {
        $emailKeys = ['MAIL', 'MAILDOM', 'MAILTRAV'];

        // Filter out keys that are not emails
        $contact = array_filter($contact, function ($data) use ($emailKeys) {
            return in_array($data['moyenComId'] ?? [], $emailKeys)
                && preg_match('/^.+\@.+\..+$/', $data['libelle'] ?? false);
        });

        // If volunteer has a favorite email, we return it
        if ($no = ($infos['mailMoyenComId']['numero'] ?? null)) {
            foreach ($contact as $item) {
                if ($no === ($item['numero'] ?? null)) {
                    return $item['libelle'];
                }
            }
        }

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
            // Check skill expiration (expiration date + 6 months)
            if (isset($training['dateRecyclage']) && preg_match('/^\d{4}\-\d{2}\-\d{2}T\d{2}:\d{2}:\d{2}$/', $training['dateRecyclage'])) {
                $expiration = (new \DateTime($training['dateRecyclage']))->add(new \DateInterval('P6M'));
                if (time() > $expiration->getTimestamp()) {
                    continue;
                }
            }

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

    private function debug(string $message, array $params = [])
    {
        $this->logger->info($message, $params);

        echo sprintf('%s %s (%s)', date('d/m/Y H:i:s'), $message, json_encode($params)), PHP_EOL;
    }
}