<?php

namespace App\Tests\Controller\Management;

use App\Entity\VolunteerList;
use App\Tests\Base\BaseWebTestCase;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class VolunteerListControllerTest extends BaseWebTestCase
{
    private function getFixtures($container) : DataFixtures
    {
        return new DataFixtures(
            $container->get('doctrine.orm.entity_manager'),
            $container->get('security.password_hasher')
        );
    }

    private function getCsrfToken($container, string $tokenId = 'csrf') : string
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

    private function bootUserWithStructure($client): array
    {
        $container = $client->getContainer();
        $fixtures  = $this->getFixtures($container);

        $suffix    = uniqid();
        $user      = $fixtures->createRawUser("vol_list-{$suffix}@test.com", 'password');
        $structure = $fixtures->createStructure("VL-STR-{$suffix}", "EXT-VL-{$suffix}");
        $fixtures->assignUserToStructure($user, $structure);
        $this->login($client, $user);

        return [$user, $structure];
    }

    public function testHomeAction(): void
    {
        $client = static::createClient();
        $this->bootUserWithStructure($client);

        $client->request('GET', '/management/structures/volunteer-lists/');
        $this->assertResponseIsSuccessful();
    }

    public function testIndexAction(): void
    {
        $client            = static::createClient();
        [, $structure]     = $this->bootUserWithStructure($client);

        $client->request('GET', sprintf('/management/structures/volunteer-lists/%d/', $structure->getId()));
        $this->assertResponseIsSuccessful();
    }

    public function testIndexForbiddenForOutsider(): void
    {
        $client    = static::createClient();
        $fixtures  = $this->getFixtures($client->getContainer());
        $user      = $fixtures->createRawUser('vol_list_out-'.uniqid().'@test.com', 'password');
        $structure = $fixtures->createStructure('VL-OUT-STR-'.uniqid(), 'EXT-VLO-'.uniqid());

        $this->login($client, $user);
        $client->request('GET', sprintf('/management/structures/volunteer-lists/%d/', $structure->getId()));

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreateActionRenders(): void
    {
        $client            = static::createClient();
        [, $structure]     = $this->bootUserWithStructure($client);

        $client->request('GET', sprintf('/management/structures/volunteer-lists/%d/create', $structure->getId()));
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testCardsRendersExistingList(): void
    {
        $client            = static::createClient();
        $container         = $client->getContainer();
        [, $structure]     = $this->bootUserWithStructure($client);

        $fixtures = $this->getFixtures($container);
        $list     = $fixtures->createVolunteerList($structure, 'Cards-List-'.uniqid());

        $client->request('GET', sprintf(
            '/management/structures/volunteer-lists/%d/cards/%d',
            $structure->getId(),
            $list->getId()
        ));
        $this->assertResponseIsSuccessful();
    }

    public function testDeleteListRedirectsToIndex(): void
    {
        $client            = static::createClient();
        $container         = $client->getContainer();
        [, $structure]     = $this->bootUserWithStructure($client);

        $fixtures = $this->getFixtures($container);
        $list     = $fixtures->createVolunteerList($structure, 'Del-List-'.uniqid());
        $listId   = $list->getId();
        $csrf     = $this->getCsrfToken($container);

        $client->request('GET', sprintf(
            '/management/structures/volunteer-lists/%d/remove/%s/%d',
            $structure->getId(),
            $csrf,
            $listId
        ));

        $this->assertResponseRedirects(sprintf(
            '/management/structures/volunteer-lists/%d/',
            $structure->getId()
        ));

        $em = $container->get('doctrine.orm.entity_manager');
        $em->clear();
        $this->assertNull($em->getRepository(VolunteerList::class)->find($listId));
    }

    public function testDeleteOneVolunteerRedirectsToCards(): void
    {
        $client            = static::createClient();
        $container         = $client->getContainer();
        [$user, $structure] = $this->bootUserWithStructure($client);

        $fixtures  = $this->getFixtures($container);
        $volunteer = $fixtures->createStandaloneVolunteer('VL-STD-'.uniqid());
        $fixtures->assignVolunteerToStructure($volunteer, $structure);
        $list      = $fixtures->createVolunteerList($structure, 'D1V-List-'.uniqid(), [$volunteer]);

        $csrf = $this->getCsrfToken($container);
        $client->request('GET', sprintf(
            '/management/structures/volunteer-lists/%d/remove-one-volunteer/%s/%d/%d',
            $structure->getId(),
            $csrf,
            $list->getId(),
            $volunteer->getId()
        ));

        $this->assertResponseRedirects(sprintf(
            '/management/structures/volunteer-lists/%d/cards/%d',
            $structure->getId(),
            $list->getId()
        ));
    }
}
