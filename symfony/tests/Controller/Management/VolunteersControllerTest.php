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
            $container->get('security.password_encoder')
        );
    }

    private function getCsrfToken($container, string $tokenId = 'token') : string
    {
        /** @var CsrfTokenManagerInterface $tokenManager */
        $tokenManager = $container->get('security.csrf.token_manager');

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

        $crawler = $client->request('GET', '/management/volunteers/');
        $this->assertResponseIsSuccessful();

        $responseContent = $client->getResponse()->getContent();
        $this->assertTrue(
            str_contains($responseContent, 'Jean') || str_contains($responseContent, 'Dupont'),
            'Volunteer info should appear in the volunteer list'
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

        $admin     = $fixtures->createRawUser('vol_delete@example.com', 'password', true);
        $structure = $fixtures->createStructure('VOL DELETE STRUCTURE', 'EXT-VOL-DELETE');
        $fixtures->assignUserToStructure($admin, $structure);

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

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $em->persist($volunteer);
        $em->flush();

        $fixtures->assignVolunteerToStructure($volunteer, $structure);

        $volunteerId = $volunteer->getId();

        $this->login($client, $admin);

        // Request the delete confirmation page
        $crawler = $client->request('GET', sprintf('/management/volunteers/delete/%d', $volunteerId));
        $this->assertResponseIsSuccessful();

        // Submit the confirmation form with the "confirm" button
        $form = $crawler->filter('form')->form();
        $form['form[confirm]']->click();
        $client->submit($form);

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
}
