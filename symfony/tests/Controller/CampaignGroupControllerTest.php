<?php

namespace App\Tests\Controller;

use App\Entity\Campaign;
use App\Entity\VolunteerGroup;
use App\Tests\Base\BaseWebTestCase;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class CampaignGroupControllerTest extends BaseWebTestCase
{
    private function getFixtures($container) : DataFixtures
    {
        return new DataFixtures(
            $container->get('doctrine.orm.entity_manager'),
            $container->get('security.password_hasher')
        );
    }

    private function getCsrfToken($container, string $tokenId = 'campaign') : string
    {
        /** @var CsrfTokenManagerInterface $tokenManager */
        $tokenManager = $container->get('security.csrf.token_manager');

        if (!$container->get('request_stack')->getMainRequest()) {
            $req = \Symfony\Component\HttpFoundation\Request::create('/');
            $req->setSession(new \Symfony\Component\HttpFoundation\Session\Session(new \Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage()));
            $container->get('request_stack')->push($req);
        }

        return $tokenManager->getToken($tokenId)->getValue();
    }

    /**
     * Create a campaign in which the requesting user has CAMPAIGN_ACCESS.
     */
    private function createAccessibleCampaign($container) : array
    {
        $fixtures = $this->getFixtures($container);

        $suffix    = uniqid();
        $user      = $fixtures->createRawUser("group-user-{$suffix}@test.com", 'password');
        $structure = $fixtures->createStructure("GROUP-STRUCT-{$suffix}", "EXT-GRP-{$suffix}");
        $fixtures->assignUserToStructure($user, $structure);
        $volunteer = $fixtures->createVolunteer($user, "VOL-GRP-{$suffix}", "grp-vol-{$suffix}@test.com");
        $fixtures->assignVolunteerToStructure($volunteer, $structure);

        $campaign = $fixtures->createCampaign("Grp Campaign {$suffix}");
        $campaign->setUser($user);
        $em = $container->get('doctrine.orm.entity_manager');
        $em->persist($campaign);
        $em->flush();

        $communication = $fixtures->createCommunication($campaign);
        $fixtures->createMessage($communication, $volunteer);

        return compact('user', 'structure', 'volunteer', 'campaign');
    }

    // ──────────────────────────────────────────────
    // POST /campaign/{id}/group/rename/{index}
    // ──────────────────────────────────────────────

    public function testGroupRenamePersistsName(): void
    {
        $client = static::createClient();
        $data   = $this->createAccessibleCampaign($client->getContainer());
        $this->login($client, $data['user']);

        $csrf = $this->getCsrfToken($client->getContainer());
        $client->request(
            'POST',
            sprintf('/campaign/%d/group/rename/0', $data['campaign']->getId()),
            ['name' => 'Group Alpha', 'csrf' => $csrf]
        );

        $this->assertResponseIsSuccessful();
        $this->assertJson($client->getResponse()->getContent());

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $em->clear();
        $refreshed = $em->find(Campaign::class, $data['campaign']->getId());
        $this->assertSame('Group Alpha', $refreshed->getGroupNames()[0]);
    }

    public function testGroupRenameRejectsBadCsrf(): void
    {
        $client = static::createClient();
        $data   = $this->createAccessibleCampaign($client->getContainer());
        $this->login($client, $data['user']);

        $client->request(
            'POST',
            sprintf('/campaign/%d/group/rename/0', $data['campaign']->getId()),
            ['name' => 'Foo', 'csrf' => 'bad-token']
        );

        $this->assertResponseStatusCodeSame(404);
    }

    public function testGroupRenameRejectsAnonymous(): void
    {
        $client = static::createClient();
        $data   = $this->createAccessibleCampaign($client->getContainer());

        $client->request('POST', sprintf('/campaign/%d/group/rename/0', $data['campaign']->getId()), [
            'name' => 'Foo',
            'csrf' => $this->getCsrfToken($client->getContainer()),
        ]);

        // Anonymous gets a 302 to the entry point.
        $this->assertResponseStatusCodeSame(302);
    }

    // ──────────────────────────────────────────────
    // POST /campaign/{id}/group/volunteer/{vid}/toggle/{index}
    // ──────────────────────────────────────────────

    public function testGroupToggleAddsAndRemovesVolunteer(): void
    {
        $client    = static::createClient();
        $container = $client->getContainer();
        $data      = $this->createAccessibleCampaign($container);
        $this->login($client, $data['user']);
        $csrf = $this->getCsrfToken($container);
        $url  = sprintf(
            '/campaign/%d/group/volunteer/%d/toggle/0',
            $data['campaign']->getId(),
            $data['volunteer']->getId()
        );

        // First call: create the link
        $client->request('POST', $url, ['csrf' => $csrf]);
        $this->assertResponseIsSuccessful();

        $em = $container->get('doctrine.orm.entity_manager');
        $em->clear();
        $link = $em->getRepository(VolunteerGroup::class)->findOneBy([
            'campaign'   => $data['campaign']->getId(),
            'volunteer'  => $data['volunteer']->getId(),
            'groupIndex' => 0,
        ]);
        $this->assertNotNull($link, 'Toggle should create a VolunteerGroup link on first call');

        // Second call: remove the link
        $client->request('POST', $url, ['csrf' => $csrf]);
        $this->assertResponseIsSuccessful();

        $em->clear();
        $link = $em->getRepository(VolunteerGroup::class)->findOneBy([
            'campaign'   => $data['campaign']->getId(),
            'volunteer'  => $data['volunteer']->getId(),
            'groupIndex' => 0,
        ]);
        $this->assertNull($link, 'Toggle should remove the VolunteerGroup link on the second call');
    }

    public function testGroupToggleRejectsBadCsrf(): void
    {
        $client    = static::createClient();
        $container = $client->getContainer();
        $data      = $this->createAccessibleCampaign($container);
        $this->login($client, $data['user']);

        $client->request('POST', sprintf(
            '/campaign/%d/group/volunteer/%d/toggle/0',
            $data['campaign']->getId(),
            $data['volunteer']->getId()
        ), ['csrf' => 'bad']);

        $this->assertResponseStatusCodeSame(404);
    }
}
