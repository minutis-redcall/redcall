<?php

namespace App\Tests\Controller;

use App\Entity\VolunteerSession;
use App\Tests\Base\BaseWebTestCase;
use App\Tests\Fixtures\DataFixtures;
use Ramsey\Uuid\Uuid;

class SpaceControllerTest extends BaseWebTestCase
{
    private function getFixtures($container) : DataFixtures
    {
        return new DataFixtures(
            $container->get('doctrine.orm.entity_manager'),
            $container->get('security.password_encoder')
        );
    }

    /**
     * Creates a volunteer and a corresponding VolunteerSession in the DB,
     * then stores the session ID in the HTTP session so the voter grants access.
     *
     * Returns the session ID string to use in URLs.
     */
    private function createVolunteerSession($client) : string
    {
        $container = $client->getContainer();
        $fixtures  = $this->getFixtures($container);

        $user      = $fixtures->createRawUser('space_user@example.com', 'password');
        $volunteer = $fixtures->createVolunteer($user, 'SPACE-VOL-001', 'space_user@example.com');

        $em = $container->get('doctrine.orm.entity_manager');

        $volunteerSession = new VolunteerSession();
        $volunteerSession->setVolunteer($volunteer);
        $volunteerSession->setSessionId(Uuid::uuid4()->toString());
        $volunteerSession->setCreatedAt(new \DateTime());

        $em->persist($volunteerSession);
        $em->flush();

        // Store in the HTTP session so the VolunteerSessionVoter grants access
        $session = $container->get('session');
        $session->set('volunteer-session', $volunteerSession->getSessionId());
        $session->save();

        // Attach session cookie to browser
        $client->getCookieJar()->set(
            new \Symfony\Component\BrowserKit\Cookie($session->getName(), $session->getId())
        );

        return $volunteerSession->getSessionId();
    }

    public function testSpaceHomeAccessible()
    {
        $client = static::createClient();
        $client->disableReboot();

        $sessionId = $this->createVolunteerSession($client);

        $client->request('GET', sprintf('/space/%s/', $sessionId));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('a[href*="space_consult_data"], a[href*="consult-data"]');
    }

    public function testSpaceConsultData()
    {
        $client = static::createClient();
        $client->disableReboot();

        $sessionId = $this->createVolunteerSession($client);

        $client->request('GET', sprintf('/space/%s/consult-data', $sessionId));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.rc-section');
    }

    public function testSpaceDeleteData()
    {
        $client = static::createClient();
        $client->disableReboot();

        $sessionId = $this->createVolunteerSession($client);

        $crawler = $client->request('GET', sprintf('/space/%s/delete-data', $sessionId));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('#ask-deletion');
        $this->assertSelectorExists('#delete-buttons');
    }

    public function testSpaceInvalidSession()
    {
        $client = static::createClient();

        $client->request('GET', '/space/00000000-0000-0000-0000-000000000000/');

        // Invalid session should not return 200 (either 403 or redirect)
        $this->assertResponseStatusCodeSame(403);
    }
}
