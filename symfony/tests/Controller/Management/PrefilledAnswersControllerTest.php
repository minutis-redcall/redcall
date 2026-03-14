<?php

namespace App\Tests\Controller\Management;

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

    private function getCsrfToken($container, string $id = 'prefilled_answers') : string
    {
        /** @var CsrfTokenManagerInterface $tokenManager */
        $tokenManager = $container->get('security.csrf.token_manager');

        return $tokenManager->getToken($id)->getValue();
    }

    public function testPrefilledAnswersList()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin     = $fixtures->createRawUser('pfa_list@test.com', 'password', true);
        $structure = $fixtures->createStructure('PFA LIST STRUCT', 'EXT-PFA-LIST');
        $fixtures->assignUserToStructure($admin, $structure);

        $this->login($client, $admin);

        $client->request('GET', sprintf('/management/structures/%d/prefilled-answers/', $structure->getId()));
        $this->assertResponseIsSuccessful();
    }

    public function testPrefilledAnswersListShowsData()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin     = $fixtures->createRawUser('pfa_list_show@test.com', 'password', true);
        $structure = $fixtures->createStructure('PFA SHOW STRUCT', 'EXT-PFA-SHOW');
        $fixtures->assignUserToStructure($admin, $structure);

        $fixtures->createPrefilledAnswers('Emergency Answers', ['Yes', 'No'], $structure);

        $this->login($client, $admin);

        $crawler = $client->request('GET', sprintf('/management/structures/%d/prefilled-answers/', $structure->getId()));
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Emergency Answers', $client->getResponse()->getContent());
    }

    public function testCreatePrefilledAnswers()
    {
        $client = static::createClient();
        $client->followRedirects();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin     = $fixtures->createRawUser('pfa_create@test.com', 'password', true);
        $structure = $fixtures->createStructure('PFA CREATE STRUCT', 'EXT-PFA-CRT');
        $fixtures->assignUserToStructure($admin, $structure);

        $this->login($client, $admin);

        $crawler = $client->request('GET', sprintf('/management/structures/%d/prefilled-answers/new', $structure->getId()));
        $this->assertResponseIsSuccessful();

        $form                                  = $crawler->filter('form[name="prefilled_answers"]')->form();
        $form['prefilled_answers[label]']      = 'New PFA Label';
        $form['prefilled_answers[colors]']     = ['1_green'];
        $form['prefilled_answers[answers][0]'] = 'Answer One';
        $form['prefilled_answers[answers][1]'] = 'Answer Two';

        $client->submit($form);
        $this->assertResponseIsSuccessful();

        $em = $client->getContainer()->get('doctrine')->getManager();
        $em->clear();

        $pfa = $em->getRepository(PrefilledAnswers::class)->findOneBy(['label' => 'New PFA Label']);
        $this->assertNotNull($pfa, 'Prefilled answers should be created in DB');
        $this->assertSame($structure->getId(), $pfa->getStructure()->getId());
        $this->assertContains('Answer One', $pfa->getAnswers());
        $this->assertContains('Answer Two', $pfa->getAnswers());
    }

    public function testEditPrefilledAnswers()
    {
        $client = static::createClient();
        $client->followRedirects();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin     = $fixtures->createRawUser('pfa_edit@test.com', 'password', true);
        $structure = $fixtures->createStructure('PFA EDIT STRUCT', 'EXT-PFA-EDT');
        $fixtures->assignUserToStructure($admin, $structure);

        $pfa = $fixtures->createPrefilledAnswers('Original PFA', ['Yes', 'No'], $structure);

        $this->login($client, $admin);

        $crawler = $client->request('GET', sprintf(
            '/management/structures/%d/prefilled-answers/%d/editor',
            $structure->getId(),
            $pfa->getId()
        ));
        $this->assertResponseIsSuccessful();

        $form                             = $crawler->filter('form[name="prefilled_answers"]')->form();
        $form['prefilled_answers[label]'] = 'Updated PFA Label';

        $client->submit($form);
        $this->assertResponseIsSuccessful();

        $em = $client->getContainer()->get('doctrine')->getManager();
        $em->clear();

        $updated = $em->getRepository(PrefilledAnswers::class)->find($pfa->getId());
        $this->assertNotNull($updated);
        $this->assertSame('Updated PFA Label', $updated->getLabel());
    }

    public function testDeletePrefilledAnswers()
    {
        $client = static::createClient();
        $client->followRedirects();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin     = $fixtures->createRawUser('pfa_delete@test.com', 'password', true);
        $structure = $fixtures->createStructure('PFA DELETE STRUCT', 'EXT-PFA-DEL');
        $fixtures->assignUserToStructure($admin, $structure);

        $pfa   = $fixtures->createPrefilledAnswers('PFA To Delete', ['Yes', 'No'], $structure);
        $pfaId = $pfa->getId();

        $this->login($client, $admin);

        $csrf = $this->getCsrfToken($client->getContainer(), 'prefilled_answers');

        $client->request('GET', sprintf(
            '/management/structures/%d/prefilled-answers/%d/delete?csrf=%s',
            $structure->getId(),
            $pfaId,
            $csrf
        ));
        $this->assertResponseIsSuccessful();

        $em = $client->getContainer()->get('doctrine')->getManager();
        $em->clear();

        $deleted = $em->getRepository(PrefilledAnswers::class)->find($pfaId);
        $this->assertNull($deleted, 'Prefilled answers should be removed from DB');
    }
}
