<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Tests\Base\BaseWebTestCase;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class FavoriteBadgeControllerTest extends BaseWebTestCase
{
    private function getFixtures($container) : DataFixtures
    {
        return new DataFixtures(
            $container->get('doctrine.orm.entity_manager'),
            $container->get('security.password_encoder')
        );
    }

    private function getCsrfToken($container) : string
    {
        /** @var CsrfTokenManagerInterface $tokenManager */
        $tokenManager = $container->get('security.csrf.token_manager');

        return $tokenManager->getToken('csrf')->getValue();
    }

    public function testFavoriteBadgeIndex()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $user = $fixtures->createRawUser('favbadge_user@example.com', 'password');

        $this->login($client, $user);

        $crawler = $client->request('GET', '/favorite-badge');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testAddFavoriteBadge()
    {
        $client = static::createClient();
        $client->followRedirects();
        $fixtures = $this->getFixtures($client->getContainer());

        $user  = $fixtures->createRawUser('favbadge_add@example.com', 'password');
        $badge = $fixtures->createBadge('Test Fav Badge', 'FAV-BADGE-001');

        $this->login($client, $user);

        // Add badge to user favorites directly and verify it shows
        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $user->addFavoriteBadge($badge);
        $em->persist($user);
        $em->flush();

        $crawler = $client->request('GET', '/favorite-badge');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.table', 'Test Fav Badge');
    }

    public function testDeleteFavoriteBadge()
    {
        $client = static::createClient();
        $client->followRedirects();
        $fixtures = $this->getFixtures($client->getContainer());

        $user  = $fixtures->createRawUser('favbadge_del@example.com', 'password');
        $badge = $fixtures->createBadge('Badge To Delete', 'FAV-BADGE-DEL');

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $user->addFavoriteBadge($badge);
        $em->persist($user);
        $em->flush();

        $this->login($client, $user);

        // Visit the index page first so the fav_badge_{id} setting is initialized.
        // Without this, the delete redirect back to index would re-add all public
        // badges (including the one just removed) via the first-visit initialization.
        $client->request('GET', '/favorite-badge');
        $this->assertResponseIsSuccessful();

        $csrf = $this->getCsrfToken($client->getContainer());

        $client->request('GET', sprintf('/favorite-badge/delete/%s/%d', $csrf, $badge->getId()));

        $this->assertResponseIsSuccessful();

        // Verify the badge was removed from favorites
        $em->clear();
        $refreshedUser = $em->getRepository(User::class)->find($user->getId());
        $this->assertNotContains(
            $badge->getId(),
            $refreshedUser->getFavoriteBadges()->map(function ($b) {
                return $b->getId();
            })->toArray()
        );
    }
}
