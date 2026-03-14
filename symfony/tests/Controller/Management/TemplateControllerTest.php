<?php

namespace App\Tests\Controller\Management;

use App\Entity\Template;
use App\Tests\Base\BaseWebTestCase;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class TemplateControllerTest extends BaseWebTestCase
{
    private function getFixtures($container) : DataFixtures
    {
        return new DataFixtures(
            $container->get('doctrine.orm.entity_manager'),
            $container->get('security.password_encoder')
        );
    }

    private function getCsrfToken($container, string $id = 'csrf') : string
    {
        /** @var CsrfTokenManagerInterface $tokenManager */
        $tokenManager = $container->get('security.csrf.token_manager');

        return $tokenManager->getToken($id)->getValue();
    }

    public function testTemplateListEmpty()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin     = $fixtures->createRawUser('tpl_list_empty@test.com', 'password', true);
        $structure = $fixtures->createStructure('TPL EMPTY STRUCT', 'EXT-TPL-EMPTY');
        $fixtures->assignUserToStructure($admin, $structure);

        $this->login($client, $admin);

        $client->request('GET', sprintf('/management/structures/%d/template', $structure->getId()));
        $this->assertResponseIsSuccessful();
    }

    public function testTemplateListShowsTemplates()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin     = $fixtures->createRawUser('tpl_list_show@test.com', 'password', true);
        $structure = $fixtures->createStructure('TPL SHOW STRUCT', 'EXT-TPL-SHOW');
        $fixtures->assignUserToStructure($admin, $structure);

        $fixtures->createTemplate($structure, 'My SMS Template', 'sms', 'Hello from SMS');

        $this->login($client, $admin);

        $crawler = $client->request('GET', sprintf('/management/structures/%d/template', $structure->getId()));
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.table', 'My SMS Template');
    }

    public function testCreateSmsTemplate()
    {
        $client = static::createClient();
        $client->followRedirects();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin     = $fixtures->createRawUser('tpl_create_sms@test.com', 'password', true);
        $structure = $fixtures->createStructure('TPL CREATE SMS', 'EXT-TPL-CSMS');
        $fixtures->assignUserToStructure($admin, $structure);

        $this->login($client, $admin);

        $crawler = $client->request('GET', sprintf('/management/structures/%d/template/new', $structure->getId()));
        $this->assertResponseIsSuccessful();

        $form                        = $crawler->filter('form[name="template"]')->form();
        $form['template[name]']      = 'New SMS Template';
        $form['template[type]']      = 'sms';
        $form['template[language]']  = 'fr';
        $form['template[body_text]'] = 'Hello SMS body content';

        $client->submit($form);
        $this->assertResponseIsSuccessful();

        $em = $client->getContainer()->get('doctrine')->getManager();
        $em->clear();

        $template = $em->getRepository(Template::class)->findOneBy(['name' => 'New SMS Template']);
        $this->assertNotNull($template, 'SMS template should be created in DB');
        $this->assertSame('sms', $template->getType());
        $this->assertSame('Hello SMS body content', $template->getBody());
        $this->assertSame($structure->getId(), $template->getStructure()->getId());
    }

    public function testCreateEmailTemplate()
    {
        $client = static::createClient();
        $client->followRedirects();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin     = $fixtures->createRawUser('tpl_create_email@test.com', 'password', true);
        $structure = $fixtures->createStructure('TPL CREATE EMAIL', 'EXT-TPL-CEML');
        $fixtures->assignUserToStructure($admin, $structure);

        $this->login($client, $admin);

        $crawler = $client->request('GET', sprintf('/management/structures/%d/template/new', $structure->getId()));
        $this->assertResponseIsSuccessful();

        $form                        = $crawler->filter('form[name="template"]')->form();
        $form['template[name]']      = 'New Email Template';
        $form['template[type]']      = 'email';
        $form['template[language]']  = 'fr';
        $form['template[subject]']   = 'Test Email Subject';
        $form['template[body_html]'] = '<p>Hello email body</p>';

        $client->submit($form);
        $this->assertResponseIsSuccessful();

        $em = $client->getContainer()->get('doctrine')->getManager();
        $em->clear();

        $template = $em->getRepository(Template::class)->findOneBy(['name' => 'New Email Template']);
        $this->assertNotNull($template, 'Email template should be created in DB');
        $this->assertSame('email', $template->getType());
        $this->assertSame('Test Email Subject', $template->getSubject());
        $this->assertStringContainsString('Hello email body', $template->getBody());
        $this->assertSame($structure->getId(), $template->getStructure()->getId());
    }

    public function testEditTemplate()
    {
        $client = static::createClient();
        $client->followRedirects();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin     = $fixtures->createRawUser('tpl_edit@test.com', 'password', true);
        $structure = $fixtures->createStructure('TPL EDIT STRUCT', 'EXT-TPL-EDIT');
        $fixtures->assignUserToStructure($admin, $structure);

        $template = $fixtures->createTemplate($structure, 'Original Template', 'sms', 'Original body');

        $this->login($client, $admin);

        $crawler = $client->request('GET', sprintf(
            '/management/structures/%d/template/%d/edit',
            $structure->getId(),
            $template->getId()
        ));
        $this->assertResponseIsSuccessful();

        $form                        = $crawler->filter('form[name="template"]')->form();
        $form['template[name]']      = 'Updated Template Name';
        $form['template[body_text]'] = 'Updated body content';

        $client->submit($form);
        $this->assertResponseIsSuccessful();

        $em = $client->getContainer()->get('doctrine')->getManager();
        $em->clear();

        $updated = $em->getRepository(Template::class)->find($template->getId());
        $this->assertNotNull($updated);
        $this->assertSame('Updated Template Name', $updated->getName());
        $this->assertSame('Updated body content', $updated->getBody());
    }

    public function testDeleteTemplate()
    {
        $client = static::createClient();
        $client->followRedirects();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin     = $fixtures->createRawUser('tpl_delete@test.com', 'password', true);
        $structure = $fixtures->createStructure('TPL DELETE STRUCT', 'EXT-TPL-DEL');
        $fixtures->assignUserToStructure($admin, $structure);

        $template   = $fixtures->createTemplate($structure, 'Template To Delete', 'sms', 'Delete me');
        $templateId = $template->getId();

        $this->login($client, $admin);

        $csrf = $this->getCsrfToken($client->getContainer(), 'csrf');

        $client->request('GET', sprintf(
            '/management/structures/%d/template/%d/%s/delete',
            $structure->getId(),
            $templateId,
            $csrf
        ));
        $this->assertResponseIsSuccessful();

        $em = $client->getContainer()->get('doctrine')->getManager();
        $em->clear();

        $deleted = $em->getRepository(Template::class)->find($templateId);
        $this->assertNull($deleted, 'Template should be removed from DB');
    }
}
