<?php

namespace App\Manager;

use App\Entity\Phone;
use App\Entity\Structure;
use App\Entity\Tag;
use App\Entity\Volunteer;
use App\Task\SyncOneWithPegass;
use Bundles\GoogleTaskBundle\Service\TaskSender;
use Bundles\PegassCrawlerBundle\Entity\Pegass;
use Bundles\PegassCrawlerBundle\Manager\PegassManager;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberType;
use libphonenumber\PhoneNumberUtil;
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
    private const RED_CROSS_DOMAINS = [
        'croix-rouge.fr',
    ];

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
     * @var UserManager
     */
    private $userManager;

    /**
     * @var PhoneManager
     */
    private $phoneManager;

    /**
     * @var TaskSender
     */
    private $async;

    /**
     * @var LoggerInterface|null
     */
    private $logger;

    public function __construct(PegassManager $pegassManager,
        StructureManager $structureManager,
        VolunteerManager $volunteerManager,
        TagManager $tagManager,
        UserManager $userManager,
        PhoneManager $phoneManager,
        TaskSender $async,
        LoggerInterface $logger = null)
    {
        $this->pegassManager    = $pegassManager;
        $this->structureManager = $structureManager;
        $this->volunteerManager = $volunteerManager;
        $this->tagManager       = $tagManager;
        $this->userManager      = $userManager;
        $this->phoneManager     = $phoneManager;
        $this->async            = $async;
        $this->logger           = $logger ?: new NullLogger();
    }

    public function refresh(bool $force)
    {
        $this->refreshStructures($force);
        $this->refreshVolunteers($force);
    }

    public function refreshAsync()
    {
        $this->structureManager->synchronizeWithPegass();

        $this->pegassManager->foreach(Pegass::TYPE_STRUCTURE, function (Pegass $pegass) {
            $this->async->fire(SyncOneWithPegass::class, [
                'type'       => Pegass::TYPE_STRUCTURE,
                'identifier' => $pegass->getIdentifier(),
            ]);
        });

        $this->async->fire(SyncOneWithPegass::class, [
            'type'       => SyncOneWithPegass::PARENT_STRUCUTRES,
            'identifier' => null,
        ]);

        $this->volunteerManager->synchronizeWithPegass();

        $this->pegassManager->foreach(Pegass::TYPE_VOLUNTEER, function (Pegass $pegass) {
            // Volunteer is invalid (ex: 00000048004C)
            if (!$pegass->evaluate('user.id')) {
                return;
            }

            $this->async->fire(SyncOneWithPegass::class, [
                'type'       => Pegass::TYPE_VOLUNTEER,
                'identifier' => $pegass->getIdentifier(),
            ]);
        });
    }

    public function refreshStructures(bool $force)
    {
        $this->structureManager->synchronizeWithPegass();

        // Import or refresh structures
        $this->pegassManager->foreach(Pegass::TYPE_STRUCTURE, function (Pegass $pegass) use ($force) {
            $this->debug('Walking through a structure', [
                'identifier' => $pegass->getIdentifier(),
            ]);

            $this->refreshStructure($pegass, $force);
        });

        $this->refreshParentStructures();
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
        $this->volunteerManager->synchronizeWithPegass();

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
        $structureIdsVolunteerBelongsTo = [];
        foreach (array_filter(explode('|', $pegass->getParentIdentifier())) as $identifier) {
            if ($structure = $this->structureManager->findOneByIdentifier($identifier)) {
                $volunteer->addStructure($structure);
                $structureIdsVolunteerBelongsTo[] = $structure->getId();
            }
        }

        // Add structures based on the actions performed by the volunteer
        $identifiers = [];
        foreach ($pegass->evaluate('actions') ?? [] as $action) {
            if (isset($action['structure']['id']) && !in_array($action['structure']['id'], $identifiers)) {
                if ($structure = $this->structureManager->findOneByIdentifier($action['structure']['id'])) {
                    $volunteer->addStructure($structure);
                    $structureIdsVolunteerBelongsTo[] = $structure->getId();
                }
                $identifiers[] = $action['structure']['id'];
            }
        }

        // Volunteer is locked
        if ($volunteer->isLocked()) {
            $volunteer->addReport('import_report.update_locked');
            $this->volunteerManager->save($volunteer);

            // If volunteer is bound to a RedCall user, update its structures
            $user = $this->userManager->findOneByNivol($volunteer->getNivol());
            if ($user) {
                $this->userManager->updateNivol($user, $volunteer->getNivol());
            }

            return;
        }

        // Remove volunteer from structures he does not belong to anymore
        $structuresToRemove = [];
        foreach ($volunteer->getStructures() as $structure) {
            if (!in_array($structure->getId(), $structureIdsVolunteerBelongsTo)) {
                $structuresToRemove[] = $structure;
            }
        }
        foreach ($structuresToRemove as $structure) {
            $volunteer->removeStructure($structure);
        }

        // Volunteer disabled on Pegass side
        $enabled = $pegass->evaluate('user.actif');
        if (!$enabled) {
            $volunteer->addReport('import_report.disabled');
            $volunteer->setEnabled(false);
            $this->volunteerManager->save($volunteer);

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

        $volunteer->setEnabled(true);

        // Update basic information
        $volunteer->setFirstName($this->normalizeName($pegass->evaluate('user.prenom')));
        $volunteer->setLastName($this->normalizeName($pegass->evaluate('user.nom')));

        if (!$volunteer->isPhoneNumberLocked()) {
            $this->fetchPhoneNumber($volunteer, $pegass->evaluate('contact'));
        }

        if (!$volunteer->isEmailLocked()) {
            $volunteer->setEmail($this->fetchEmail($pegass->evaluate('infos'), $pegass->evaluate('contact')));
        }

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
            $this->volunteerManager->save($volunteer);

            return;
        }

        $this->volunteerManager->save($volunteer);

        // If volunteer is bound to a RedCall user, update its structures
        $user = $this->userManager->findOneByNivol($volunteer->getNivol());
        if ($user) {
            $this->userManager->updateNivol($user, $volunteer->getNivol());
        }
    }

    private function normalizeName(string $name) : string
    {
        return sprintf('%s%s',
            mb_strtoupper(mb_substr($name, 0, 1)),
            mb_strtolower(mb_substr($name, 1))
        );
    }

    private function fetchPhoneNumber(Volunteer $volunteer, array $contact)
    {
        $phoneKeys = ['POR', 'PORT', 'TELDOM', 'TELTRAV', 'PORE'];

        // Filter out keys that are not phones
        $contact = array_filter($contact, function ($data) use ($phoneKeys) {
            return in_array($data['moyenComId'] ?? [], $phoneKeys);
        });

        // Order phones in order to take work phone last
        usort($contact, function ($a, $b) use ($phoneKeys) {
            return array_search($a['moyenComId'], $phoneKeys) <=> array_search($b['moyenComId'], $phoneKeys);
        });

        if (!$contact) {
            return;
        }

        $phoneUtil = PhoneNumberUtil::getInstance();
        foreach ($contact as $key => $row) {
            try {
                /** @var PhoneNumber $parsed */
                $parsed = $phoneUtil->parse($row['libelle'], Phone::DEFAULT_LANG);

                if (PhoneNumberType::MOBILE !== $phoneUtil->getNumberType($parsed)) {
                    continue;
                }

                $e164 = $phoneUtil->format($parsed, PhoneNumberFormat::E164);
                if (!$volunteer->hasPhoneNumber($e164) && !$this->phoneManager->findOneByPhoneNumber($e164)) {
                    $phone = new Phone();
                    $phone->setPreferred(0 === $volunteer->getPhones()->count());
                    $phone->setE164($e164);
                    $volunteer->addPhone($phone);
                }
            } catch (NumberParseException $e) {
                continue;
            }
        }

        // Cleaning: do not integrate non mobile phones
        foreach ($volunteer->getPhones() as $phone) {
            /** @var Phone $phone */
            $parsed = $phoneUtil->parse($phone->getE164(), Phone::DEFAULT_LANG);
            if (PhoneNumberType::MOBILE !== $phoneUtil->getNumberType($parsed)) {
                $volunteer->removePhone($phone);
            }
        }
        if (1 === $volunteer->getPhones()->count()) {
            foreach ($volunteer->getPhones() as $phone) {
                $phone->setPreferred(true);
            }
        }
    }

    private function fetchEmail(array $infos, array $contact) : ?string
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
            foreach (self::RED_CROSS_DOMAINS as $domain) {
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

        // VL, VPSP, MAR, CEM
        foreach ($pegass->evaluate('skills') as $skill) {
            if (9 == ($skill['id'] ?? false)) {
                $skills[] = Tag::TAG_DRVR_VL;
            }

            if (10 == ($skill['id'] ?? false)) {
                $skills[] = Tag::TAG_DRVR_VPSP;
            }

            if (15 == ($skill['id'] ?? false)) {
                $skills[] = Tag::TAG_MAR;
            }

            if (8 == ($skill['id'] ?? false)) {
                $skills[] = Tag::TAG_CEM;
            }
        }

        // DLAS, DLUS, CEM
        foreach ($pegass->evaluate('nominations') as $nomination) {
            if (309 == ($nomination['id'] ?? false)) {
                $skills[] = Tag::TAG_DLAS;
            }
            if (40 == ($nomination['id'] ?? false)) {
                $skills[] = Tag::TAG_DLUS;
            }
            if (331 == ($nomination['id'] ?? false)) {
                $skills[] = Tag::TAG_CEM;
            }
        }

        // PSC1, PSE1, PSE2, CI, TCAU, TCEO
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

            if (in_array($training['formation']['code'] ?? false, ['TCAU'])) {
                $skills[] = Tag::TAG_TCAU;
            }

            if (in_array($training['formation']['code'] ?? false, ['TCEO'])) {
                $skills[] = Tag::TAG_TCEO;
            }
        }

        return $skills;
    }

    private function debug(string $message, array $params = [])
    {
        $this->logger->info($message, $params);

        if ('cli' === php_sapi_name()) {
            echo sprintf('%s %s (%s)', date('d/m/Y H:i:s'), $message, json_encode($params)), PHP_EOL;
        }
    }
}