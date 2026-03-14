<?php

namespace App\Tests\Controller\Admin;

use App\Entity\PrefilledAnswers;
use App\Tests\Base\BaseWebTestCase;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class PrefilledAnswersControllerTest extends BaseWebTestCase
{
    private function getFixtures($container) : DataFixtures
    {
        return new DataFixtures(
            $container->get('doctrine.orm.entity_manager'),
            $container->get('security.password_encoder')
        );
    }

    private function getCsrfToken($container, string $tokenId) : string
    {
        /** @var CsrfTokenManagerInterface $tokenManager */
        $tokenManager = $container->get('security.csrf.token_manager');

        return $tokenManager->getToken($tokenId)->getValue();
    }

    public function testPrefilledAnswersList()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin = $fixtures->createRawUser('pfa_list_admin@test.com', 'password', true);
        $pfa   = $fixtures->createPrefilledAnswers('Test Answers List', ['Yes', 'No']);

        $this->login($client, $admin);

        $crawler = $client->request('GET', '/admin/reponses-pre-remplies/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Test Answers List');
    }

    public function testPrefilledAnswersCreate()
    {
        $client = static::createClient();
        $client->followRedirects();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin = $fixtures->createRawUser('pfa_create_admin@test.com', 'password', true);

        $this->login($client, $admin);

        $crawler = $client->request('GET', '/admin/reponses-pre-remplies/editer/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testPrefilledAnswersEdit()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin = $fixtures->createRawUser('pfa_edit_admin@test.com', 'password', true);
        $pfa   = $fixtures->createPrefilledAnswers('Edit Me', ['A', 'B']);

        $this->login($client, $admin);

        $crawler = $client->request('GET', sprintf('/admin/reponses-pre-remplies/editer/%d', $pfa->getId()));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testPrefilledAnswersDelete()
    {
        $client = static::createClient();
        $client->followRedirects();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin = $fixtures->createRawUser('pfa_del_admin@test.com', 'password', true);
        $pfa   = $fixtures->createPrefilledAnswers('Delete Me', ['X', 'Y']);
        $pfaId = $pfa->getId();

        $this->login($client, $admin);

        $csrf = $this->getCsrfToken($client->getContainer(), 'prefilled_answers');

        $client->request('GET', sprintf('/admin/reponses-pre-remplies/supprimer/%s/%d', $csrf, $pfaId));

        $this->assertResponseIsSuccessful();

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $em->clear();
        $deletedPfa = $em->getRepository(PrefilledAnswers::class)->find($pfaId);
        $this->assertNull($deletedPfa);
    }
}
