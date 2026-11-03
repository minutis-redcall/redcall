<?php

namespace App\Tests\Controller\Management;

use App\Entity\Volunteer;
use App\Tests\Base\BaseWebTestCase;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class VolunteersControllerTest extends BaseWebTestCase
{
    private function getFixtures($container) : DataFixtures
    {
        return new DataFixtures(
            $container->get('doctrine.orm.entity_manager'),
            $container->get('security.password_hasher')
        );
    }

    private function getCsrfToken($container, string $tokenId = 'token') : string
    {
        /** @var CsrfTokenManagerInterface $tokenManager */
        $tokenManager = $container->get('security.csrf.token_manager');

        // Sf6: CSRF token storage needs a session in RequestStack
        if (!$container->get('request_stack')->getMainRequest()) {
            $req = \Symfony\Component\HttpFoundation\Request::create('/');
            $req->setSession(new \Symfony\Component\HttpFoundation\Session\Session(new \Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage()));
            $container->get('request_stack')->push($req);
        }

        return $tokenManager->getToken($tokenId)->getValue();
    }

    public function testListVolunteers()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $user      = $fixtures->createRawUser('vol_list@example.com', 'password', true);
        $structure = $fixtures->createStructure('VOL LIST STRUCTURE', 'EXT-VOL-LIST');
        $fixtures->assignUserToStructure($user, $structure);

        $volunteer = new Volunteer();
        $volunteer->setExternalId('VOL-LIST-001');
        $volunteer->setFirstName('Jean');
        $volunteer->setLastName('Dupont');
        $volunteer->setEmail('jean.dupont@example.com');
        $volunteer->setEnabled(true);
        $volunteer->setLocked(false);
        $volunteer->setPhoneNumberOptin(true);
        $volunteer->setEmailOptin(true);

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $em->persist($volunteer);
        $em->flush();

        $fixtures->assignVolunteerToStructure($volunteer, $structure);

        $this->login($client, $user);

        $crawler = $client->request('GET', '/management/volunteers');
        $this->assertResponseIsSuccessful();

        $responseContent = $client->getResponse()->getContent();
        $this->assertTrue(
            str_contains($responseContent, 'Jean') || str_contains($responseContent, 'Dupont'),
            'Volunteer info should appear in the volunteer list'
        );
    }

    public function testVolunteerCardIconsCarryAccessibleLabels()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $user      = $fixtures->createRawUser('vol_aria@example.com', 'password', true);
        $structure = $fixtures->createStructure('VOL ARIA STRUCT', 'EXT-VOL-ARIA');
        $fixtures->assignUserToStructure($user, $structure);

        $volunteer = new Volunteer();
        $volunteer->setExternalId('VOL-ARIA-001');
        $volunteer->setFirstName('Marie');
        $volunteer->setLastName('Curie');
        $volunteer->setEnabled(true);
        $volunteer->setLocked(false);
        $volunteer->setPhoneNumberOptin(true);
        $volunteer->setEmailOptin(true);

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $em->persist($volunteer);
        $em->flush();

        $fixtures->assignVolunteerToStructure($volunteer, $structure);

        $this->login($client, $user);
        $crawler = $client->request('GET', '/management/volunteers');
        $this->assertResponseIsSuccessful();

        // The lock-toggle is a bare 🔒/🔓 emoji — needs aria-label so screen
        // readers don't just announce a glyph the user can't interpret.
        $this->assertGreaterThanOrEqual(
            1,
            $crawler->filter('a.toggle-action[aria-label]')->count(),
            'Lock/unlock toggle on the volunteer card must declare an aria-label — it has no visible text label.'
        );
    }

    public function testVolunteerFormHeadingHierarchyHasNoSkippedLevel()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $user      = $fixtures->createRawUser('vol_h_skip@example.com', 'password', true);
        $structure = $fixtures->createStructure('VOL HEADING STRUCT', 'EXT-VOL-HEAD');
        $fixtures->assignUserToStructure($user, $structure);

        $this->login($client, $user);

        $crawler = $client->request('GET', '/management/volunteers/create');
        $this->assertResponseIsSuccessful();

        // The form has one page-level <h1> and a series of section headings.
        // Section headings must be <h2>, not <h3>, so an assistive-tech "go
        // to next heading" jump lands at "General" / "Contact" / etc. and
        // doesn't skip a level.
        $this->assertSame(
            1,
            $crawler->filter('h1')->count(),
            'Volunteer create form must have exactly one <h1>.'
        );
        $this->assertGreaterThanOrEqual(
            2,
            $crawler->filter('h2')->count(),
            'Volunteer create form should expose its tab sections as <h2> so the heading outline is contiguous (h1 → h2 → …), not jump from h1 to h3.'
        );
    }

    public function testCreateVolunteer()
    {
        $client = static::createClient();
        $client->followRedirects();
        $fixtures = $this->getFixtures($client->getContainer());

        $user      = $fixtures->createRawUser('vol_create@example.com', 'password', true);
        $structure = $fixtures->createStructure('VOL CREATE STRUCTURE', 'EXT-VOL-CREATE');
        $fixtures->assignUserToStructure($user, $structure);

        $this->login($client, $user);

        $crawler = $client->request('GET', '/management/volunteers/create');
        $this->assertResponseIsSuccessful();

        $form                          = $crawler->filter('form[name="volunteer"]')->form();
        $form['volunteer[firstName]']  = 'Nouveau';
        $form['volunteer[lastName]']   = 'Benevole';
        $form['volunteer[email]']      = 'nouveau.benevole@example.com';
        $form['volunteer[emailOptin]'] = true;

        $client->submit($form);
        $this->assertResponseIsSuccessful();

        $em = $client->getContainer()->get('doctrine')->getManager();
        $em->clear();

        $volunteerRepo = $em->getRepository(Volunteer::class);
        $created       = $volunteerRepo->findOneBy(['email' => 'nouveau.benevole@example.com']);
        $this->assertNotNull($created, 'Volunteer should be created in the database');
        $this->assertSame('Nouveau', $created->getFirstName());
        $this->assertSame('Benevole', $created->getLastName());
    }

    public function testEditVolunteer()
    {
        $client = static::createClient();
        $client->followRedirects();
        $fixtures = $this->getFixtures($client->getContainer());

        $user      = $fixtures->createRawUser('vol_edit@example.com', 'password', true);
        $structure = $fixtures->createStructure('VOL EDIT STRUCTURE', 'EXT-VOL-EDIT');
        $fixtures->assignUserToStructure($user, $structure);

        $volunteer = new Volunteer();
        $volunteer->setExternalId('VOL-EDIT-001');
        $volunteer->setFirstName('Pierre');
        $volunteer->setLastName('Martin');
        $volunteer->setEmail('pierre.martin@example.com');
        $volunteer->setEnabled(true);
        $volunteer->setLocked(false);
        $volunteer->setPhoneNumberOptin(true);
        $volunteer->setEmailOptin(true);

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $em->persist($volunteer);
        $em->flush();

        $fixtures->assignVolunteerToStructure($volunteer, $structure);

        $this->login($client, $user);

        $crawler = $client->request('GET', sprintf('/management/volunteers/manual-update/%d', $volunteer->getId()));
        $this->assertResponseIsSuccessful();

        $form                         = $crawler->filter('form[name="volunteer"]')->form();
        $form['volunteer[firstName]'] = 'PierreUpdated';
        $form['volunteer[lastName]']  = 'MartinUpdated';

        $client->submit($form);
        $this->assertResponseIsSuccessful();

        $em->clear();

        $volunteerRepo = $em->getRepository(Volunteer::class);
        $updated       = $volunteerRepo->find($volunteer->getId());
        $this->assertSame('PierreUpdated', $updated->getFirstName());
        $this->assertSame('MartinUpdated', $updated->getLastName());
    }

    public function testToggleLockVolunteer()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $user      = $fixtures->createRawUser('vol_lock@example.com', 'password', true);
        $structure = $fixtures->createStructure('VOL LOCK STRUCTURE', 'EXT-VOL-LOCK');
        $fixtures->assignUserToStructure($user, $structure);

        $volunteer = new Volunteer();
        $volunteer->setExternalId('VOL-LOCK-001');
        $volunteer->setFirstName('Lock');
        $volunteer->setLastName('Test');
        $volunteer->setEmail('lock.test@example.com');
        $volunteer->setEnabled(true);
        $volunteer->setLocked(false);
        $volunteer->setPhoneNumberOptin(true);
        $volunteer->setEmailOptin(true);

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $em->persist($volunteer);
        $em->flush();

        $fixtures->assignVolunteerToStructure($volunteer, $structure);

        $this->assertFalse($volunteer->isLocked(), 'Volunteer should start unlocked');

        $this->login($client, $user);
        $csrf = $this->getCsrfToken($client->getContainer());

        $client->request('GET', sprintf(
            '/management/volunteers/toggle-lock-%d/%s',
            $volunteer->getId(),
            $csrf
        ));

        $em->clear();

        $volunteerRepo = $em->getRepository(Volunteer::class);
        $updated       = $volunteerRepo->find($volunteer->getId());
        $this->assertTrue($updated->isLocked(), 'Volunteer should be locked after toggle');
    }

    public function testToggleEnableVolunteer()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $user      = $fixtures->createRawUser('vol_enable@example.com', 'password', true);
        $structure = $fixtures->createStructure('VOL ENABLE STRUCTURE', 'EXT-VOL-ENABLE');
        $fixtures->assignUserToStructure($user, $structure);

        $volunteer = new Volunteer();
        $volunteer->setExternalId('VOL-ENABLE-001');
        $volunteer->setFirstName('Enable');
        $volunteer->setLastName('Test');
        $volunteer->setEmail('enable.test@example.com');
        $volunteer->setEnabled(true);
        $volunteer->setLocked(false);
        $volunteer->setPhoneNumberOptin(true);
        $volunteer->setEmailOptin(true);

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $em->persist($volunteer);
        $em->flush();

        $fixtures->assignVolunteerToStructure($volunteer, $structure);

        $this->assertTrue($volunteer->isEnabled(), 'Volunteer should start enabled');

        $this->login($client, $user);
        $csrf = $this->getCsrfToken($client->getContainer());

        $client->request('GET', sprintf(
            '/management/volunteers/toggle-enable-%d/%s',
            $volunteer->getId(),
            $csrf
        ));

        $em->clear();

        $volunteerRepo = $em->getRepository(Volunteer::class);
        $updated       = $volunteerRepo->find($volunteer->getId());
        $this->assertFalse($updated->isEnabled(), 'Volunteer should be disabled after toggle');
    }

    public function testDeleteVolunteer()
    {
        $client = static::createClient();
        $client->followRedirects();
        $fixtures = $this->getFixtures($client->getContainer());

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');

        // Admin needs a linked volunteer for launchNewCampaign to work
        $admin         = $fixtures->createRawUser('vol_delete@example.com', 'password', true);
        $structure     = $fixtures->createStructure('VOL DELETE STRUCTURE', 'EXT-VOL-DELETE');
        $adminVol      = $fixtures->createVolunteer($admin, 'VOL-ADMIN-DEL', 'vol_delete@example.com');
        $fixtures->assignUserToStructure($admin, $structure);
        $fixtures->assignVolunteerToStructure($adminVol, $structure);

        // Create a volunteer with no user linked (required for deletion)
        $volunteer = new Volunteer();
        $volunteer->setExternalId('VOL-DELETE-001');
        $volunteer->setFirstName('Delete');
        $volunteer->setLastName('Me');
        $volunteer->setEmail('delete.me@example.com');
        $volunteer->setEnabled(true);
        $volunteer->setLocked(false);
        $volunteer->setPhoneNumberOptin(true);
        $volunteer->setEmailOptin(true);

        $em->persist($volunteer);
        $em->flush();

        $fixtures->assignVolunteerToStructure($volunteer, $structure);

        $volunteerId = $volunteer->getId();

        $this->login($client, $admin);

        // Request the delete confirmation page
        $crawler = $client->request('GET', sprintf('/management/volunteers/delete/%d', $volunteerId));
        $this->assertResponseIsSuccessful();

        // Submit the confirmation form with the "confirm" button via raw POST
        $formNode = $crawler->filter('form')->first();
        $client->request('POST', $formNode->attr('action') ?: sprintf('/management/volunteers/delete/%d', $volunteerId), [
            'form' => ['confirm' => ''],
        ]);


        $em->clear();

        $volunteerRepo = $em->getRepository(Volunteer::class);
        $deleted       = $volunteerRepo->find($volunteerId);
        // After deletion the volunteer is anonymized, so either null or anonymized
        $this->assertTrue(
            $deleted === null
            || $deleted->getFirstName() !== 'Delete'
            || $deleted->getEmail() !== 'delete.me@example.com',
            'Volunteer should be deleted or anonymized after confirmation'
        );
    }

    // ──────────────────────────────────────────────
    // GET /management/volunteers/sync-log/{id}
    // ──────────────────────────────────────────────

    public function testSyncLogRendersForAdminEvenWhenNoSnapshot(): void
    {
        $client    = static::createClient();
        $fixtures  = $this->getFixtures($client->getContainer());
        $admin     = $fixtures->createRawUser('vol_sl_admin-'.uniqid().'@test.com', 'password', true);
        $volunteer = $fixtures->createStandaloneVolunteer('VOL-SL-'.uniqid());

        $this->login($client, $admin);
        $client->request('GET', sprintf('/management/volunteers/sync-log/%d', $volunteer->getId()));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.alert-warning', 'A "no snapshot" banner should be visible when no sync data exists yet');
    }

    public function testSyncLogForbiddenForNonAdmin(): void
    {
        $client    = static::createClient();
        $fixtures  = $this->getFixtures($client->getContainer());
        $user      = $fixtures->createRawUser('vol_sl_user-'.uniqid().'@test.com', 'password', false);
        $volunteer = $fixtures->createStandaloneVolunteer('VOL-SLU-'.uniqid());

        $this->login($client, $user);
        $client->request('GET', sprintf('/management/volunteers/sync-log/%d', $volunteer->getId()));

        $this->assertResponseStatusCodeSame(403);
    }

    // ──────────────────────────────────────────────
    // GET /management/volunteers/edit-structures/{id}
    // ──────────────────────────────────────────────

    public function testEditStructuresRendersForAdmin(): void
    {
        $client    = static::createClient();
        $fixtures  = $this->getFixtures($client->getContainer());
        $admin     = $fixtures->createRawUser('vol_es-'.uniqid().'@test.com', 'password', true);
        $volunteer = $fixtures->createStandaloneVolunteer('VOL-ES-'.uniqid());

        $this->login($client, $admin);
        $client->request('GET', sprintf('/management/volunteers/edit-structures/%d', $volunteer->getId()));

        $this->assertResponseIsSuccessful();
    }

    public function testEditStructuresForbiddenForNonAdmin(): void
    {
        $client    = static::createClient();
        $fixtures  = $this->getFixtures($client->getContainer());
        $user      = $fixtures->createRawUser('vol_esu-'.uniqid().'@test.com', 'password', false);
        $volunteer = $fixtures->createStandaloneVolunteer('VOL-ESU-'.uniqid());

        $this->login($client, $user);
        $client->request('GET', sprintf('/management/volunteers/edit-structures/%d', $volunteer->getId()));

        $this->assertResponseStatusCodeSame(403);
    }

    // ──────────────────────────────────────────────
    // GET /management/volunteers/add-structure/{csrf}/{id}
    // ──────────────────────────────────────────────

    public function testAddStructureLinksVolunteerToStructure(): void
    {
        $client    = static::createClient();
        $container = $client->getContainer();
        $fixtures  = $this->getFixtures($container);
        $admin     = $fixtures->createRawUser('vol_as-'.uniqid().'@test.com', 'password', true);
        $volunteer = $fixtures->createStandaloneVolunteer('VOL-AS-'.uniqid());
        $structure = $fixtures->createStructure('AS-STR-'.uniqid(), 'EXT-AS-'.uniqid());

        $this->login($client, $admin);
        $csrf = $this->getCsrfToken($container, 'volunteer');

        $client->request('GET', sprintf(
            '/management/volunteers/add-structure/%s/%d?structure=%d',
            $csrf,
            $volunteer->getId(),
            $structure->getId()
        ));

        $this->assertResponseRedirects(sprintf('/management/volunteers/edit-structures/%d', $volunteer->getId()));
    }

    public function testAddStructureBadCsrf404(): void
    {
        $client    = static::createClient();
        $fixtures  = $this->getFixtures($client->getContainer());
        $admin     = $fixtures->createRawUser('vol_as_bad-'.uniqid().'@test.com', 'password', true);
        $volunteer = $fixtures->createStandaloneVolunteer('VOL-ASB-'.uniqid());
        $structure = $fixtures->createStructure('ASB-STR-'.uniqid(), 'EXT-ASB-'.uniqid());

        $this->login($client, $admin);
        $client->request('GET', sprintf(
            '/management/volunteers/add-structure/bad/%d?structure=%d',
            $volunteer->getId(),
            $structure->getId()
        ));

        $this->assertResponseStatusCodeSame(404);
    }

    // ──────────────────────────────────────────────
    // /management/volunteers/remove-all-structures/{csrf}/{id}
    // ──────────────────────────────────────────────

    public function testRemoveAllStructuresClearsLinks(): void
    {
        $client    = static::createClient();
        $container = $client->getContainer();
        $fixtures  = $this->getFixtures($container);
        $admin     = $fixtures->createRawUser('vol_ras-'.uniqid().'@test.com', 'password', true);
        $volunteer = $fixtures->createStandaloneVolunteer('VOL-RAS-'.uniqid());
        $structure = $fixtures->createStructure('RAS-STR-'.uniqid(), 'EXT-RAS-'.uniqid());
        $fixtures->assignVolunteerToStructure($volunteer, $structure);

        $this->login($client, $admin);
        $csrf = $this->getCsrfToken($container, 'volunteer');

        $client->request('GET', sprintf(
            '/management/volunteers/remove-all-structures/%s/%d',
            $csrf,
            $volunteer->getId()
        ));

        $this->assertResponseRedirects(sprintf('/management/volunteers/edit-structures/%d', $volunteer->getId()));

        $em = $container->get('doctrine.orm.entity_manager');
        $em->clear();
        $refreshed = $em->find(Volunteer::class, $volunteer->getId());
        $this->assertCount(0, $refreshed->getStructures());
    }

    // ──────────────────────────────────────────────
    // /management/volunteers/delete-structure/{csrf}/{volunteerId}/{structureId}
    // ──────────────────────────────────────────────

    public function testDeleteStructureRemovesOneLink(): void
    {
        $client    = static::createClient();
        $container = $client->getContainer();
        $fixtures  = $this->getFixtures($container);
        $admin     = $fixtures->createRawUser('vol_ds-'.uniqid().'@test.com', 'password', true);
        $volunteer = $fixtures->createStandaloneVolunteer('VOL-DS-'.uniqid());
        $struct1   = $fixtures->createStructure('DS1-'.uniqid(), 'EXT-DS1-'.uniqid());
        $struct2   = $fixtures->createStructure('DS2-'.uniqid(), 'EXT-DS2-'.uniqid());
        $fixtures->assignVolunteerToStructure($volunteer, $struct1);
        $fixtures->assignVolunteerToStructure($volunteer, $struct2);

        $this->login($client, $admin);
        $csrf = $this->getCsrfToken($container, 'volunteer');

        $client->request('GET', sprintf(
            '/management/volunteers/delete-structure/%s/%d/%d',
            $csrf,
            $volunteer->getId(),
            $struct1->getId()
        ));

        $this->assertResponseRedirects(sprintf('/management/volunteers/edit-structures/%d', $volunteer->getId()));

        $em = $container->get('doctrine.orm.entity_manager');
        $em->clear();
        $refreshed = $em->find(Volunteer::class, $volunteer->getId());
        $this->assertCount(1, $refreshed->getStructures());
    }

    // ──────────────────────────────────────────────
    // /management/volunteers/list-user-structures
    // ──────────────────────────────────────────────

    public function testListUserStructuresReturnsJson(): void
    {
        $client    = static::createClient();
        $fixtures  = $this->getFixtures($client->getContainer());
        $user      = $fixtures->createRawUser('vol_lus-'.uniqid().'@test.com', 'password');
        $structure = $fixtures->createStructure('LUS-STR-'.uniqid(), 'EXT-LUS-'.uniqid());
        $fixtures->assignUserToStructure($user, $structure);
        $volunteer = $fixtures->createVolunteer($user, 'VOL-LUS-'.uniqid(), 'lus-vol-'.uniqid().'@test.com');
        $fixtures->assignVolunteerToStructure($volunteer, $structure);

        $this->login($client, $user);
        $client->request('POST', '/management/volunteers/list-user-structures', ['id' => $volunteer->getId()]);

        $this->assertResponseIsSuccessful();
        $decoded = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('title', $decoded);
        $this->assertArrayHasKey('body', $decoded);
    }

    public function testListUserStructuresEscapesVolunteerNameInTitle(): void
    {
        // Regression (CERT-FR RM #1080870, audit H6): the title is inserted
        // with jQuery .html(), so a manager could plant a script in a
        // volunteer's name and have it run in an admin's session.
        $client    = static::createClient();
        $container = $client->getContainer();
        $fixtures  = $this->getFixtures($container);
        $user      = $fixtures->createRawUser('vol_xss-'.uniqid().'@test.com', 'password');
        $structure = $fixtures->createStructure('XSS-STR-'.uniqid(), 'EXT-XSS-'.uniqid());
        $fixtures->assignUserToStructure($user, $structure);
        $volunteer = $fixtures->createVolunteer($user, 'VOL-XSS-'.uniqid(), 'xss-vol-'.uniqid().'@test.com');
        $fixtures->assignVolunteerToStructure($volunteer, $structure);

        $em = $container->get('doctrine.orm.entity_manager');
        $volunteer->setFirstName('<svg/onload=alert(1)>');
        $volunteer->setLastName('Doe'); // display name needs both names
        $em->persist($volunteer);
        $em->flush();

        $this->login($client, $user);
        $client->request('POST', '/management/volunteers/list-user-structures', ['id' => $volunteer->getId()]);

        $this->assertResponseIsSuccessful();
        $title = json_decode($client->getResponse()->getContent(), true)['title'];
        $this->assertStringNotContainsString('<', $title);
        $this->assertMatchesRegularExpression('/&lt;svg/i', $title);
    }

    // ──────────────────────────────────────────────
    // Anonymize page authorization (CERT-FR RM #1080870, audit M1)
    // ──────────────────────────────────────────────

    /**
     * A manager of one structure, an own volunteer and a foreign volunteer,
     * each with one SMS reply.
     */
    private function bootAnonymizeScenario($client): array
    {
        $container = $client->getContainer();
        $fixtures  = $this->getFixtures($container);
        $suffix    = uniqid();

        $user      = $fixtures->createRawUser("m1-{$suffix}@test.com", 'password');
        $structure = $fixtures->createStructure("M1-STR-{$suffix}", "EXT-M1-{$suffix}");
        $fixtures->assignUserToStructure($user, $structure);
        $other     = $fixtures->createStructure("M1-OTHER-{$suffix}", "EXT-M1O-{$suffix}");

        $own = $fixtures->createStandaloneVolunteer("M1-OWN-{$suffix}", "m1-own-{$suffix}@test.com");
        $fixtures->assignVolunteerToStructure($own, $structure);
        $foreign = $fixtures->createStandaloneVolunteer("M1-FOR-{$suffix}", "m1-for-{$suffix}@test.com");
        $fixtures->assignVolunteerToStructure($foreign, $other);

        $campaign      = $fixtures->createCampaign("M1 {$suffix}");
        $communication = $fixtures->createCommunication($campaign);
        $ownAnswer     = $fixtures->createAnswer($fixtures->createMessage($communication, $own), "own secret {$suffix}");
        $foreignAnswer = $fixtures->createAnswer($fixtures->createMessage($communication, $foreign), "foreign secret {$suffix}");

        $this->login($client, $user);

        return compact('own', 'foreign', 'ownAnswer', 'foreignAnswer');
    }

    public function testAnonymizePageDeniesVolunteerOutsidePerimeter(): void
    {
        $client = static::createClient();
        $s      = $this->bootAnonymizeScenario($client);

        $client->request('GET', sprintf(
            '/management/volunteers/delete/%d/%d',
            $s['foreign']->getId(),
            $s['foreignAnswer']->getId()
        ));

        $this->assertResponseStatusCodeSame(403);
        $this->assertStringNotContainsString($s['foreignAnswer']->getRaw(), $client->getResponse()->getContent());
    }

    public function testAnonymizePageRejectsAnswerOfAnotherVolunteer(): void
    {
        $client = static::createClient();
        $s      = $this->bootAnonymizeScenario($client);

        $client->request('GET', sprintf(
            '/management/volunteers/delete/%d/%d',
            $s['own']->getId(),
            $s['foreignAnswer']->getId()
        ));

        $this->assertResponseStatusCodeSame(404);
        $this->assertStringNotContainsString($s['foreignAnswer']->getRaw(), $client->getResponse()->getContent());
    }

    public function testAnonymizePageStillShowsOwnVolunteerAnswer(): void
    {
        $client = static::createClient();
        $s      = $this->bootAnonymizeScenario($client);

        $client->request('GET', sprintf(
            '/management/volunteers/delete/%d/%d',
            $s['own']->getId(),
            $s['ownAnswer']->getId()
        ));

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString($s['ownAnswer']->getRaw(), $client->getResponse()->getContent());
    }

    /**
     * Same setup as testDeleteVolunteer, where "confirm" does anonymize.
     */
    private function bootAnonymizableVolunteer($client): int
    {
        $fixtures = $this->getFixtures($client->getContainer());
        $em       = $client->getContainer()->get('doctrine.orm.entity_manager');
        $suffix   = uniqid();

        $admin     = $fixtures->createRawUser("m1c-{$suffix}@example.com", 'password', true);
        $structure = $fixtures->createStructure("M1C STRUCTURE {$suffix}", "EXT-M1C-{$suffix}");
        $adminVol  = $fixtures->createVolunteer($admin, "M1C-ADMIN-{$suffix}", "m1c-{$suffix}@example.com");
        $fixtures->assignUserToStructure($admin, $structure);
        $fixtures->assignVolunteerToStructure($adminVol, $structure);

        $volunteer = new Volunteer();
        $volunteer->setExternalId("M1C-VOL-{$suffix}");
        $volunteer->setFirstName('Keep');
        $volunteer->setLastName('Me');
        $volunteer->setEmail("keep.me.{$suffix}@example.com");
        $volunteer->setEnabled(true);
        $volunteer->setLocked(false);
        $volunteer->setPhoneNumberOptin(true);
        $volunteer->setEmailOptin(true);
        $em->persist($volunteer);
        $em->flush();
        $fixtures->assignVolunteerToStructure($volunteer, $structure);

        $this->login($client, $admin);

        return $volunteer->getId();
    }

    private function postAnonymizeButton($client, int $id, string $button): ?Volunteer
    {
        $client->request('POST', sprintf('/management/volunteers/delete/%d', $id), [
            'form' => [$button => ''],
        ]);

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $em->clear();

        return $em->getRepository(Volunteer::class)->find($id);
    }

    public function testAnonymizeConfirmButtonAnonymizes(): void
    {
        // Control for the cancel test below: this setup really anonymizes.
        $client = static::createClient();
        $fresh  = $this->postAnonymizeButton($client, $this->bootAnonymizableVolunteer($client), 'confirm');

        $this->assertTrue(null === $fresh || 'Keep' !== $fresh->getFirstName());
    }

    public function testAnonymizeCancelButtonDoesNotAnonymize(): void
    {
        // Without JavaScript, "Cancel" is a plain submit button.
        $client = static::createClient();
        $fresh  = $this->postAnonymizeButton($client, $this->bootAnonymizableVolunteer($client), 'cancel');

        $this->assertNotNull($fresh);
        $this->assertSame('Keep', $fresh->getFirstName());
    }

    // ──────────────────────────────────────────────
    // NIVOL and privilege badges (CERT-FR RM #1080870, audit M6)
    //
    // A manager could create a volunteer carrying the "RT MR" badge (the sync
    // then creates a verified, trusted RedCall user for it), and could rewrite
    // the NIVOL of an existing volunteer (unbinding it from its RedCall user).
    // ──────────────────────────────────────────────

    private function bootManagerWithVolunteer($client, bool $admin = false): array
    {
        $fixtures  = $this->getFixtures($client->getContainer());
        $suffix    = uniqid();
        $user      = $fixtures->createRawUser("m6-{$suffix}@test.com", 'password', $admin);
        $structure = $fixtures->createStructure("M6 {$suffix}", "EXT-M6-{$suffix}");
        $fixtures->assignUserToStructure($user, $structure);
        $volunteer = $fixtures->createStandaloneVolunteer("M6VOL{$suffix}", "m6-vol-{$suffix}@test.com");
        $volunteer->setFirstName('Jean');
        $volunteer->setLastName('Dupont');
        $fixtures->assignVolunteerToStructure($volunteer, $structure);
        $this->login($client, $user);

        return [$user, $structure, $volunteer];
    }

    private function reloadVolunteer($client, int $id): Volunteer
    {
        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $em->clear();

        return $em->getRepository(Volunteer::class)->find($id);
    }

    private function volunteerBadgeNames(Volunteer $volunteer): array
    {
        return array_map(function ($badge) {
            return $badge->getName();
        }, $volunteer->getBadges()->toArray());
    }

    public function testNonAdminCannotRewriteNivolOfExistingVolunteer(): void
    {
        $client = static::createClient();
        [, , $volunteer] = $this->bootManagerWithVolunteer($client);
        $nivol  = $volunteer->getExternalId();

        $crawler = $client->request('GET', sprintf('/management/volunteers/manual-update/%d', $volunteer->getId()));
        $form    = $crawler->filter('form[name="volunteer"]')->form();
        $values  = $form->getPhpValues();
        $values['volunteer']['externalId'] = 'HIJACKED';
        $client->request('POST', $form->getUri(), $values);

        $this->assertSame($nivol, $this->reloadVolunteer($client, $volunteer->getId())->getExternalId());
    }

    public static function rtmrVisibilityProvider(): array
    {
        return ['invisible badge' => [false], 'visible badge' => [true]];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('rtmrVisibilityProvider')]
    public function testNonAdminCannotGrantRtmrBadge(bool $visible): void
    {
        $client = static::createClient();
        [, , $volunteer] = $this->bootManagerWithVolunteer($client);
        $rtmr   = $this->getFixtures($client->getContainer())
                       ->createBadge(\App\Sync\Reconciliation\RtmrReconciliator::RTMR_BADGE, 'M6-RTMR-'.uniqid(), true, $visible);

        $crawler = $client->request('GET', sprintf('/management/volunteers/manual-update/%d', $volunteer->getId()));
        $form    = $crawler->filter('form[name="volunteer"]')->form();
        $values  = $form->getPhpValues();
        if ($visible) {
            $values['volunteer']['badges']['visible'][] = (string) $rtmr->getId();
        } else {
            $values['volunteer']['badges']['invisible'] = (string) $rtmr->getId();
        }
        $client->request('POST', $form->getUri(), $values);

        $this->assertNotContains(
            \App\Sync\Reconciliation\RtmrReconciliator::RTMR_BADGE,
            $this->volunteerBadgeNames($this->reloadVolunteer($client, $volunteer->getId()))
        );
    }

    public function testAdminCanStillGrantRtmrBadge(): void
    {
        $client = static::createClient();
        [, , $volunteer] = $this->bootManagerWithVolunteer($client, true);
        $rtmr   = $this->getFixtures($client->getContainer())
                       ->createBadge(\App\Sync\Reconciliation\RtmrReconciliator::RTMR_BADGE, 'M6-RTMR-'.uniqid(), true, false);

        $crawler = $client->request('GET', sprintf('/management/volunteers/manual-update/%d', $volunteer->getId()));
        $form    = $crawler->filter('form[name="volunteer"]')->form();
        $values  = $form->getPhpValues();
        $values['volunteer']['badges']['invisible'] = (string) $rtmr->getId();
        $client->request('POST', $form->getUri(), $values);

        $this->assertContains(
            \App\Sync\Reconciliation\RtmrReconciliator::RTMR_BADGE,
            $this->volunteerBadgeNames($this->reloadVolunteer($client, $volunteer->getId()))
        );
    }

    public function testNonAdminCannotCreateVolunteerOutsideTheirStructures(): void
    {
        $client = static::createClient();
        $this->bootManagerWithVolunteer($client);
        $email  = 'm6-orphan-'.uniqid().'@test.com';

        $crawler = $client->request('GET', '/management/volunteers/create');
        $form    = $crawler->filter('form[name="volunteer"]')->form();
        $values  = $form->getPhpValues();
        $values['volunteer']['firstName'] = 'Orphan';
        $values['volunteer']['lastName']  = 'Volunteer';
        $values['volunteer']['email']     = $email;
        unset($values['volunteer']['structures']);
        $client->request('POST', $form->getUri(), $values);

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $em->clear();
        $this->assertNull($em->getRepository(Volunteer::class)->findOneBy(['email' => $email]));
    }

    public function testNonAdminCanStillCreateVolunteerInTheirStructure(): void
    {
        $client = static::createClient();
        [, $structure] = $this->bootManagerWithVolunteer($client);
        $email  = 'm6-new-'.uniqid().'@test.com';

        $crawler = $client->request('GET', '/management/volunteers/create');
        $form    = $crawler->filter('form[name="volunteer"]')->form();
        $values  = $form->getPhpValues();
        $values['volunteer']['externalId'] = 'M6NEW'.strtoupper(uniqid());
        $values['volunteer']['firstName']  = 'New';
        $values['volunteer']['lastName']   = 'Volunteer';
        $values['volunteer']['email']      = $email;
        $values['volunteer']['structures'] = [(string) $structure->getId()];
        $client->request('POST', $form->getUri(), $values);

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $em->clear();
        $this->assertNotNull($em->getRepository(Volunteer::class)->findOneBy(['email' => $email]));
    }

    // ──────────────────────────────────────────────
    // Structures outside the form's choices (CERT-FR RM #1080870, audit M7)
    //
    // Non-admin choices only listed the user's direct structures, and saving
    // replaced the collection: every other structure (another unit's, or a
    // sub-structure of the manager's own) was silently dropped.
    // ──────────────────────────────────────────────

    private function saveVolunteerForm($client, Volunteer $volunteer, ?callable $alter = null): void
    {
        $crawler = $client->request('GET', sprintf('/management/volunteers/manual-update/%d', $volunteer->getId()));
        $form    = $crawler->filter('form[name="volunteer"]')->form();
        $values  = $form->getPhpValues();
        // Proves the form was actually saved (a rejected form keeps everything).
        $values['volunteer']['firstName'] = 'Saved';
        if ($alter) {
            $values = $alter($values);
        }
        $client->request('POST', $form->getUri(), $values);

        $this->assertSame('Saved', $this->reloadVolunteer($client, $volunteer->getId())->getFirstName(), 'The form should have been saved');
    }

    private function structureIdsOf($client, Volunteer $volunteer): array
    {
        $ids = array_map(function ($structure) {
            return $structure->getId();
        }, $this->reloadVolunteer($client, $volunteer->getId())->getStructures()->toArray());
        sort($ids);

        return $ids;
    }

    public function testSavingKeepsStructuresOutsideManagerPerimeter(): void
    {
        $client = static::createClient();
        [, $own, $volunteer] = $this->bootManagerWithVolunteer($client);
        $other  = $this->getFixtures($client->getContainer())->createStructure('M7 OTHER '.uniqid(), 'EXT-M7O-'.uniqid());
        $this->getFixtures($client->getContainer())->assignVolunteerToStructure($volunteer, $other);
        $expected = [$own->getId(), $other->getId()];
        sort($expected);

        $this->saveVolunteerForm($client, $volunteer);

        $this->assertSame($expected, $this->structureIdsOf($client, $volunteer));
    }

    public function testSavingKeepsChildStructureOfManager(): void
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());
        [, $own]  = $this->bootManagerWithVolunteer($client);
        $em       = $client->getContainer()->get('doctrine.orm.entity_manager');

        $child = $fixtures->createStructure('M7 CHILD '.uniqid(), 'EXT-M7C-'.uniqid());
        $child->setParentStructure($own);
        $em->persist($child);
        $em->flush();
        $volunteer = $fixtures->createStandaloneVolunteer('M7CHILD'.uniqid(), 'm7-child-'.uniqid().'@test.com');
        $volunteer->setFirstName('Paul');
        $volunteer->setLastName('Child');
        $fixtures->assignVolunteerToStructure($volunteer, $child);

        $this->saveVolunteerForm($client, $volunteer);

        $this->assertSame([$child->getId()], $this->structureIdsOf($client, $volunteer));
    }

    public function testManagerCanStillRemoveTheirOwnStructure(): void
    {
        $client = static::createClient();
        [, , $volunteer] = $this->bootManagerWithVolunteer($client);
        $other  = $this->getFixtures($client->getContainer())->createStructure('M7 LEFT '.uniqid(), 'EXT-M7L-'.uniqid());
        $this->getFixtures($client->getContainer())->assignVolunteerToStructure($volunteer, $other);

        $this->saveVolunteerForm($client, $volunteer, function (array $values) {
            unset($values['volunteer']['structures']);

            return $values;
        });

        $this->assertSame([$other->getId()], $this->structureIdsOf($client, $volunteer));
    }
}
