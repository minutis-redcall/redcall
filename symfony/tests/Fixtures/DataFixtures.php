<?php

namespace App\Tests\Fixtures;

use App\Entity\Answer;
use App\Entity\Badge;
use App\Entity\Campaign;
use App\Entity\Category;
use App\Entity\Choice;
use App\Entity\Communication;
use App\Entity\Media;
use App\Entity\Message;
use App\Entity\Operation;
use App\Entity\Pegass;
use App\Entity\PrefilledAnswers;
use App\Entity\Structure;
use App\Entity\Template;
use App\Entity\User;
use App\Entity\Volunteer;
use App\Entity\VolunteerList;
use App\Entity\VolunteerSession;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class DataFixtures
{
    private $entityManager;
    private $encoder;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordEncoderInterface $encoder)
    {
        $this->entityManager = $entityManager;
        $this->encoder       = $encoder;
    }

    // ──────────────────────────────────────────────
    // Entity factories (one per entity)
    // ──────────────────────────────────────────────

    public function createRawUser(
        string $username = 'user',
        string $password = 'password',
        bool $admin = false,
        bool $verified = true
    ) : User {
        $user = new User();
        $user->setUsername($username);
        $user->setLocale('fr');
        $user->setTimezone('Europe/Paris');
        $user->setPassword($this->encoder->encodePassword($user, $password));
        $user->setIsVerified($verified);
        $user->setIsTrusted(true);
        $user->setIsRoot($admin);
        $user->setIsAdmin($admin);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function createVolunteer(
        User $user,
        string $externalId = '123456789',
        string $email = 'volunteer@example.com'
    ) : Volunteer {
        $volunteer = new Volunteer();
        $volunteer->setExternalId($externalId);
        $volunteer->setEmail($email);
        $volunteer->setUser($user);
        $volunteer->setEnabled(true);
        $volunteer->setLocked(false);
        $volunteer->setPhoneNumberOptin(true);
        $volunteer->setEmailOptin(true);

        $user->setVolunteer($volunteer);

        $this->entityManager->persist($volunteer);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $volunteer;
    }

    public function createStandaloneVolunteer(
        string $externalId = 'VOL-001',
        string $email = 'standalone@example.com'
    ) : Volunteer {
        $volunteer = new Volunteer();
        $volunteer->setExternalId($externalId);
        $volunteer->setEmail($email);
        $volunteer->setEnabled(true);
        $volunteer->setLocked(false);
        $volunteer->setPhoneNumberOptin(true);
        $volunteer->setEmailOptin(true);

        $this->entityManager->persist($volunteer);
        $this->entityManager->flush();

        return $volunteer;
    }

    public function createStructure(
        string $name = 'TEST STRUCTURE',
        string $externalId = 'EXT-001',
        bool $enabled = true
    ) : Structure {
        $structure = new Structure();
        $structure->setExternalId($externalId);
        $structure->setName($name);
        $structure->setEnabled($enabled);
        $structure->setLocked(false);

        $this->entityManager->persist($structure);
        $this->entityManager->flush();

        return $structure;
    }

    public function assignUserToStructure(User $user, Structure $structure) : void
    {
        $user->addStructure($structure);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public function assignVolunteerToStructure(Volunteer $volunteer, Structure $structure) : void
    {
        $structure->addVolunteer($volunteer);
        $this->entityManager->persist($structure);
        $this->entityManager->persist($volunteer);
        $this->entityManager->flush();
    }

    public function createBadge(
        string $name = 'Test Badge',
        string $externalId = 'BADGE-001',
        bool $enabled = true,
        bool $visible = true
    ) : Badge {
        $badge = new Badge();
        $badge->setExternalId($externalId);
        $badge->setName($name);
        $badge->setEnabled($enabled);
        $badge->setVisibility($visible);
        $badge->setLocked(false);

        $this->entityManager->persist($badge);
        $this->entityManager->flush();

        return $badge;
    }

    public function createCategory(
        string $name = 'Test Category',
        string $externalId = 'CAT-001',
        bool $enabled = true
    ) : Category {
        $category = new Category();
        $category->setExternalId($externalId);
        $category->setName($name);
        $category->setEnabled($enabled);
        $category->setLocked(false);
        $category->setPriority(0);

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        return $category;
    }

    public function createCampaign(
        string $label = 'Test Campaign',
        string $type = Campaign::TYPE_GREEN,
        bool $active = true
    ) : Campaign {
        $campaign = new Campaign();
        $campaign->setLabel($label);
        $campaign->setType($type);
        $campaign->setActive($active);
        $campaign->setCreatedAt(new \DateTime());
        $campaign->setExpiresAt(new \DateTime('+7 days'));

        $this->entityManager->persist($campaign);
        $this->entityManager->flush();

        return $campaign;
    }

    public function createCommunication(
        Campaign $campaign,
        string $type = Communication::TYPE_SMS,
        string $body = 'Test message body'
    ) : Communication {
        $communication = new Communication();
        $communication->setCampaign($campaign);
        $communication->setType($type);
        $communication->setBody($body);
        $communication->setCreatedAt(new \DateTime());
        $communication->setLanguage('fr');

        if ($type === Communication::TYPE_EMAIL) {
            $communication->setSubject('Test Subject');
        }

        $campaign->addCommunication($communication);

        $this->entityManager->persist($communication);
        $this->entityManager->persist($campaign);
        $this->entityManager->flush();

        return $communication;
    }

    public function createChoice(Communication $communication, string $label, string $code) : Choice
    {
        $choice = new Choice();
        $choice->setCode($code);
        $choice->setLabel($label);
        $choice->setCommunication($communication);

        $communication->addChoice($choice);

        $this->entityManager->persist($choice);
        $this->entityManager->persist($communication);
        $this->entityManager->flush();

        return $choice;
    }

    public function createMessage(
        Communication $communication,
        Volunteer $volunteer,
        bool $sent = true
    ) : Message {
        $message = new Message();
        $message->setCommunication($communication);
        $message->setVolunteer($volunteer);
        $message->setSent($sent);
        $message->setCode(bin2hex(random_bytes(4)));
        $message->setPrefix(strtoupper(substr(md5(random_bytes(4)), 0, 2)));

        $communication->addMessage($message);

        $this->entityManager->persist($message);
        $this->entityManager->persist($communication);
        $this->entityManager->flush();

        return $message;
    }

    public function createAnswer(
        Message $message,
        string $raw = 'Yes',
        bool $unclear = false,
        array $choices = []
    ) : Answer {
        $answer = new Answer();
        $answer->setMessage($message);
        $answer->setRaw($raw);
        $answer->setReceivedAt(new \DateTime());
        $answer->setUpdatedAt(new \DateTime());
        $answer->setUnclear($unclear);

        foreach ($choices as $choice) {
            $answer->addChoice($choice);
        }

        $this->entityManager->persist($answer);
        $this->entityManager->flush();

        return $answer;
    }

    public function createPrefilledAnswers(
        string $label = 'Test Answers',
        array $answers = ['Yes', 'No', 'Maybe'],
        ?Structure $structure = null
    ) : PrefilledAnswers {
        $pfa = new PrefilledAnswers();
        $pfa->setLabel($label);
        $pfa->setAnswers($answers);
        $pfa->setColors([Campaign::TYPE_GREEN]);

        if ($structure) {
            $pfa->setStructure($structure);
        }

        $this->entityManager->persist($pfa);
        $this->entityManager->flush();

        return $pfa;
    }

    public function createTemplate(
        Structure $structure,
        string $name = 'Test Template',
        string $type = Communication::TYPE_SMS,
        string $body = 'Hello world'
    ) : Template {
        $template = new Template();
        $template->setName($name);
        $template->setType($type);
        $template->setBody($body);
        $template->setStructure($structure);
        $template->setLanguage('fr');
        $template->setPriority(0);

        if ($type === Communication::TYPE_EMAIL) {
            $template->setSubject('Test Subject');
        }

        $this->entityManager->persist($template);
        $this->entityManager->flush();

        return $template;
    }

    public function createVolunteerList(
        Structure $structure,
        string $name = 'Test List',
        array $volunteers = []
    ) : VolunteerList {
        $list = new VolunteerList();
        $list->setName($name);
        $list->setStructure($structure);
        $list->setAudience([]);

        foreach ($volunteers as $volunteer) {
            $list->addVolunteer($volunteer);
        }

        $this->entityManager->persist($list);
        $this->entityManager->flush();

        return $list;
    }

    public function createVolunteerSession(
        Volunteer $volunteer,
        ?string $sessionId = null
    ) : VolunteerSession {
        $session = new VolunteerSession();
        $session->setVolunteer($volunteer);
        $session->setSessionId($sessionId ?? bin2hex(random_bytes(16)));
        $session->setCreatedAt(new \DateTime());

        $this->entityManager->persist($session);
        $this->entityManager->flush();

        return $session;
    }

    public function createPegass(
        string $type = Pegass::TYPE_VOLUNTEER,
        string $identifier = 'PEG-001',
        ?array $content = null,
        bool $enabled = true
    ) : Pegass {
        $pegass = new Pegass();
        $pegass->setType($type);
        $pegass->setIdentifier($identifier);
        $pegass->setContent($content);
        $pegass->setEnabled($enabled);

        $this->entityManager->persist($pegass);
        $this->entityManager->flush();

        return $pegass;
    }

    public function createOperation(
        Campaign $campaign,
        int $operationExternalId = 12345
    ) : Operation {
        $operation = new Operation();
        $operation->setOperationExternalId($operationExternalId);

        $campaign->setOperation($operation);

        $this->entityManager->persist($operation);
        $this->entityManager->persist($campaign);
        $this->entityManager->flush();

        return $operation;
    }

    public function createMedia(
        ?Communication $communication = null,
        ?string $uuid = null,
        string $url = 'https://example.com/image.jpg'
    ) : Media {
        $media = new Media();
        $media->setUuid($uuid ?? bin2hex(random_bytes(16)));
        $media->setHash(md5(random_bytes(16)));
        $media->setUrl($url);
        $media->setCreatedAt(new \DateTime());

        if ($communication) {
            $media->setCommunication($communication);
            $communication->addImage($media);
            $this->entityManager->persist($communication);
        }

        $this->entityManager->persist($media);
        $this->entityManager->flush();

        return $media;
    }

    // ──────────────────────────────────────────────
    // Scenario presets (common entity graphs)
    // ──────────────────────────────────────────────

    /**
     * Admin user assigned to a structure.
     *
     * @return array{user: User, structure: Structure}
     */
    public function createAdminWithStructure(
        string $username = 'admin@test.com',
        string $structureName = 'TEST STRUCTURE',
        string $structureExtId = 'EXT-001'
    ) : array {
        $user      = $this->createRawUser($username, 'password', true);
        $structure = $this->createStructure($structureName, $structureExtId);
        $this->assignUserToStructure($user, $structure);

        return ['user' => $user, 'structure' => $structure];
    }

    /**
     * Trusted (non-admin) user assigned to a structure.
     *
     * @return array{user: User, structure: Structure}
     */
    public function createUserWithStructure(
        string $username = 'user@test.com',
        string $structureName = 'TEST STRUCTURE',
        string $structureExtId = 'EXT-001'
    ) : array {
        $user      = $this->createRawUser($username, 'password', false);
        $structure = $this->createStructure($structureName, $structureExtId);
        $this->assignUserToStructure($user, $structure);

        return ['user' => $user, 'structure' => $structure];
    }

    /**
     * User with a linked volunteer and structure (all cross-assigned).
     * This is the minimum setup needed for campaign voter access.
     *
     * @return array{user: User, volunteer: Volunteer, structure: Structure}
     */
    public function createUserWithVolunteerAndStructure(
        string $username = 'user@test.com',
        bool $admin = false,
        string $volunteerExtId = 'VOL-001',
        string $structureName = 'TEST STRUCTURE',
        string $structureExtId = 'EXT-001'
    ) : array {
        $user      = $this->createRawUser($username, 'password', $admin);
        $volunteer = $this->createVolunteer($user, $volunteerExtId);
        $structure = $this->createStructure($structureName, $structureExtId);
        $this->assignUserToStructure($user, $structure);
        $this->assignVolunteerToStructure($volunteer, $structure);

        return [
            'user'      => $user,
            'volunteer' => $volunteer,
            'structure' => $structure,
        ];
    }

    /**
     * Full campaign graph: user + volunteer + structure + campaign + communication + choices + message.
     * The user has voter access (CAMPAIGN_ACCESS / CAMPAIGN_OWNER) via shared structure.
     *
     * @return array{user: User, volunteer: Volunteer, structure: Structure, campaign: Campaign, communication: Communication, message: Message, choices: Choice[]}
     */
    public function createFullCampaign(
        string $username = 'user@test.com',
        bool $admin = false,
        string $communicationType = Communication::TYPE_SMS,
        array $choiceLabels = ['Yes', 'No']
    ) : array {
        // Derive unique IDs from username to avoid collisions across tests
        $hash      = substr(md5($username), 0, 8);
        $setup     = $this->createUserWithVolunteerAndStructure(
            $username, $admin, 'VOL-' . $hash, 'STRUCTURE ' . $hash, 'EXT-' . $hash
        );
        $campaign  = $this->createCampaign();
        $comm      = $this->createCommunication($campaign, $communicationType);

        $choices = [];
        foreach ($choiceLabels as $i => $label) {
            $choices[] = $this->createChoice($comm, $label, (string) ($i + 1));
        }

        $message = $this->createMessage($comm, $setup['volunteer']);

        return [
            'user'          => $setup['user'],
            'volunteer'     => $setup['volunteer'],
            'structure'     => $setup['structure'],
            'campaign'      => $campaign,
            'communication' => $comm,
            'message'       => $message,
            'choices'       => $choices,
        ];
    }

    /**
     * Admin user with structure + template.
     *
     * @return array{user: User, structure: Structure, template: Template}
     */
    public function createAdminWithTemplate(
        string $templateName = 'Test Template',
        string $templateType = Communication::TYPE_SMS,
        string $templateBody = 'Hello world'
    ) : array {
        $setup    = $this->createAdminWithStructure();
        $template = $this->createTemplate($setup['structure'], $templateName, $templateType, $templateBody);

        return array_merge($setup, ['template' => $template]);
    }

    /**
     * Admin user with structure + prefilled answers.
     *
     * @return array{user: User, structure: Structure, prefilledAnswers: PrefilledAnswers}
     */
    public function createAdminWithPrefilledAnswers(
        string $label = 'Test Answers',
        array $answers = ['Yes', 'No', 'Maybe']
    ) : array {
        $setup = $this->createAdminWithStructure();
        $pfa   = $this->createPrefilledAnswers($label, $answers, $setup['structure']);

        return array_merge($setup, ['prefilledAnswers' => $pfa]);
    }

    /**
     * Badge inside a category.
     *
     * @return array{category: Category, badge: Badge}
     */
    public function createBadgeWithCategory(
        string $badgeName = 'Test Badge',
        string $categoryName = 'Test Category'
    ) : array {
        $category = $this->createCategory($categoryName, 'CAT-' . substr(md5($categoryName), 0, 6));
        $badge    = $this->createBadge($badgeName, 'BADGE-' . substr(md5($badgeName), 0, 6));
        $badge->setCategory($category);

        $this->entityManager->persist($badge);
        $this->entityManager->flush();

        return ['category' => $category, 'badge' => $badge];
    }

    /**
     * Volunteer session for GDPR/personal space access.
     *
     * @return array{volunteer: Volunteer, session: VolunteerSession}
     */
    public function createVolunteerWithSession(
        string $volunteerExtId = 'VOL-001',
        string $email = 'volunteer@example.com'
    ) : array {
        $volunteer = $this->createStandaloneVolunteer($volunteerExtId, $email);
        $session   = $this->createVolunteerSession($volunteer);

        return ['volunteer' => $volunteer, 'session' => $session];
    }

    // ──────────────────────────────────────────────
    // Utility
    // ──────────────────────────────────────────────

    public function getEntityManager() : EntityManagerInterface
    {
        return $this->entityManager;
    }
}
