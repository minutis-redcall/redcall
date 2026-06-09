<?php

namespace App\Tests\Controller\Management;

use App\Tests\Base\BaseWebTestCase;
use App\Tests\Fixtures\DataFixtures;

class ManagementHomeControllerTest extends BaseWebTestCase
{
    private function getFixtures($container) : DataFixtures
    {
        return new DataFixtures(
            $container->get('doctrine.orm.entity_manager'),
            $container->get('security.password_hasher')
        );
    }

    public function testAnonymousIsRedirected(): void
    {
        $client = static::createClient();
        $client->request('GET', '/management/');

        $this->assertResponseStatusCodeSame(302);
    }

    public function testManagementHomeRendersForTrustedUser(): void
    {
        $client = static::createClient();
        $user   = $this->getFixtures($client->getContainer())
                       ->createRawUser('mgmt-home-'.uniqid().'@test.com', 'password', false, true);

        $this->login($client, $user);
        $client->request('GET', '/management/');

        $this->assertResponseIsSuccessful();
        // The page exposes the management actions; assert a stable structural marker.
        $this->assertSelectorExists('h1');
    }

    public function testManagementHomeRendersStructureHierarchy(): void
    {
        $client    = static::createClient();
        $fixtures  = $this->getFixtures($client->getContainer());
        $em        = $client->getContainer()->get('doctrine.orm.entity_manager');

        $user = $fixtures->createRawUser('mgmt-tree-'.uniqid().'@test.com', 'password', false, true);

        $dt    = $fixtures->createStructure('DT TEST', 'DT-'.uniqid());
        $ulA   = $fixtures->createStructure('UL ALPHA', 'ULA-'.uniqid());
        $ulB   = $fixtures->createStructure('UL BETA', 'ULB-'.uniqid());

        $ulA->setParentStructure($dt);
        $ulB->setParentStructure($dt);
        $em->persist($ulA);
        $em->persist($ulB);
        $em->flush();

        $fixtures->assignUserToStructure($user, $dt);
        $fixtures->assignUserToStructure($user, $ulA);
        $fixtures->assignUserToStructure($user, $ulB);

        $this->login($client, $user);
        $crawler = $client->request('GET', '/management/');

        $this->assertResponseIsSuccessful();

        // Root + two children render as three tree nodes.
        $this->assertCount(3, $crawler->filter('.tree-node'));

        // The DT is the only depth-0 root because its parent is null.
        $this->assertCount(1, $crawler->filter('.tree-node[data-depth="0"]'));
        $this->assertCount(2, $crawler->filter('.tree-node[data-depth="1"]'));

        // The depth-1 nodes must live inside the root's tree-children list.
        $this->assertCount(
            2,
            $crawler->filter('.tree-node[data-depth="0"] > .tree-children > .tree-node[data-depth="1"]')
        );

        $this->assertStringContainsString('DT TEST', $crawler->filter('.tree-node[data-depth="0"] .tree-name')->text());
    }
}
