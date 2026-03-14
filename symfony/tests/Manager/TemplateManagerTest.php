<?php

namespace App\Tests\Manager;

use App\Entity\Communication;
use App\Entity\Template;
use App\Enum\Type;
use App\Manager\TemplateManager;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class TemplateManagerTest extends KernelTestCase
{
    private TemplateManager $manager;
    private DataFixtures $fixtures;

    protected function setUp() : void
    {
        self::bootKernel();

        $container      = static::getContainer();
        $this->manager  = $container->get(TemplateManager::class);
        $this->fixtures = new DataFixtures(
            $container->get('doctrine.orm.entity_manager'),
            $container->get('security.password_encoder')
        );
    }

    public function testFindReturnsTemplateById()
    {
        $structure = $this->fixtures->createStructure('FIND STRUCT', 'FIND-EXT-' . uniqid());
        $template  = $this->fixtures->createTemplate($structure, 'Find Test', Communication::TYPE_SMS, 'Hello find');

        $found = $this->manager->find($template->getId());

        $this->assertNotNull($found);
        $this->assertSame($template->getId(), $found->getId());
        $this->assertSame('Find Test', $found->getName());
    }

    public function testFindReturnsNullForNonExistentId()
    {
        $found = $this->manager->find(999999);

        $this->assertNull($found);
    }

    public function testGetTemplatesForStructureReturnsQueryBuilder()
    {
        $structure = $this->fixtures->createStructure('STRUCT TEST', 'STRUCT-TEST-' . uniqid());
        $this->fixtures->createTemplate($structure, 'Struct Test', Communication::TYPE_SMS, 'body');

        $qb = $this->manager->getTemplatesForStructure($structure);

        $this->assertInstanceOf(\Doctrine\ORM\QueryBuilder::class, $qb);

        $results = $qb->getQuery()->getResult();
        $this->assertCount(1, $results);
        $this->assertSame('Struct Test', $results[0]->getName());
    }

    public function testGetTemplatesForStructureDoesNotReturnOtherStructureTemplates()
    {
        $structure1 = $this->fixtures->createStructure('STRUCT A', 'STRUCT-A-' . uniqid());
        $this->fixtures->createTemplate($structure1, 'Template A', Communication::TYPE_SMS, 'body A');

        $structure2 = $this->fixtures->createStructure('OTHER STRUCT', 'EXT-OTHER-' . uniqid());
        $this->fixtures->createTemplate($structure2, 'Template B', Communication::TYPE_SMS, 'body B');

        $results = $this->manager->getTemplatesForStructure($structure1)->getQuery()->getResult();

        $this->assertCount(1, $results);
        $this->assertSame('Template A', $results[0]->getName());
    }

    public function testFindByTypeForCurrentUser()
    {
        $user      = $this->fixtures->createRawUser('tpl-type@test.com', 'password', true);
        $structure = $this->fixtures->createStructure('TPL TYPE STRUCT', 'TPL-TYPE-' . uniqid());
        $this->fixtures->assignUserToStructure($user, $structure);
        $this->fixtures->createTemplate($structure, 'SMS Template', Communication::TYPE_SMS, 'sms body');

        // Also create an email template in same structure
        $this->fixtures->createTemplate($structure, 'Email Template', Communication::TYPE_EMAIL, '<p>email body</p>');

        // Authenticate user
        $container    = static::getContainer();
        $tokenStorage = $container->get('security.token_storage');
        $token        = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $tokenStorage->setToken($token);

        $smsTemplates = $this->manager->findByTypeForCurrentUser(Type::SMS());

        $this->assertCount(1, $smsTemplates);
        $this->assertSame('SMS Template', $smsTemplates[0]->getName());

        $emailTemplates = $this->manager->findByTypeForCurrentUser(Type::EMAIL());

        $this->assertCount(1, $emailTemplates);
        $this->assertSame('Email Template', $emailTemplates[0]->getName());
    }

    public function testAddPersistsTemplate()
    {
        $structure = $this->fixtures->createStructure('ADD STRUCT', 'EXT-ADD-' . uniqid());

        $template = new Template();
        $template->setName('Added Template');
        $template->setType(Communication::TYPE_SMS);
        $template->setBody('new body');
        $template->setStructure($structure);
        $template->setLanguage('fr');
        $template->setPriority(0);

        $this->manager->add($template);

        $this->assertNotNull($template->getId());

        $found = $this->manager->find($template->getId());
        $this->assertNotNull($found);
        $this->assertSame('Added Template', $found->getName());
    }

    public function testRemoveDeletesTemplate()
    {
        $structure = $this->fixtures->createStructure('REMOVE STRUCT', 'REMOVE-EXT-' . uniqid());
        $template  = $this->fixtures->createTemplate($structure, 'To Remove', Communication::TYPE_SMS, 'remove me');
        $id        = $template->getId();

        $this->manager->remove($template);

        $this->fixtures->getEntityManager()->clear();

        $found = $this->manager->find($id);
        $this->assertNull($found);
    }
}
