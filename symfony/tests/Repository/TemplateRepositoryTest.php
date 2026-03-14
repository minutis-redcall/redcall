<?php

namespace App\Tests\Repository;

use App\Entity\Communication;
use App\Entity\Template;
use App\Enum\Type;
use App\Repository\TemplateRepository;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TemplateRepositoryTest extends KernelTestCase
{
    /** @var TemplateRepository */
    private $repository;

    /** @var DataFixtures */
    private $fixtures;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->repository = self::$container->get('doctrine.orm.entity_manager')
            ->getRepository(Template::class);

        $this->fixtures = new DataFixtures(
            self::$container->get('doctrine.orm.entity_manager'),
            self::$container->get('security.password_encoder')
        );
    }

    // ── add / remove ──

    public function testAdd(): void
    {
        $setup = $this->fixtures->createAdminWithStructure('tpladd-' . uniqid() . '@test.com', 'TPL Add Struct', 'TPL-ADD-' . uniqid());
        $template = new Template();
        $template->setName('Added Template');
        $template->setType(Communication::TYPE_SMS);
        $template->setBody('Hello');
        $template->setStructure($setup['structure']);
        $template->setLanguage('fr');
        $template->setPriority(0);

        $this->repository->add($template);

        $found = $this->repository->find($template->getId());
        $this->assertNotNull($found);
        $this->assertSame('Added Template', $found->getName());
    }

    public function testRemove(): void
    {
        $structure = $this->fixtures->createStructure('TPL Remove Struct', 'TPL-REM-' . uniqid());
        $setup = ['template' => $this->fixtures->createTemplate($structure, 'Removable Template')];
        $templateId = $setup['template']->getId();

        $this->repository->remove($setup['template']);

        $em = self::$container->get('doctrine.orm.entity_manager');
        $em->clear();
        $this->assertNull($this->repository->find($templateId));
    }

    // ── getTemplatesForStructure ──

    public function testGetTemplatesForStructure(): void
    {
        $structure = $this->fixtures->createStructure('TPL Struct', 'TPL-STRUCT-' . uniqid());
        $this->fixtures->createTemplate($structure, 'Struct Template', Communication::TYPE_SMS);

        $results = $this->repository->getTemplatesForStructure($structure)
            ->getQuery()->getResult();

        $names = array_map(function (Template $t) { return $t->getName(); }, $results);
        $this->assertContains('Struct Template', $names);
    }

    public function testGetTemplatesForStructureExcludesOtherStructures(): void
    {
        $structure = $this->fixtures->createStructure('My Struct', 'TPL-MY-' . uniqid());
        $this->fixtures->createTemplate($structure, 'My Template');
        $otherStructure = $this->fixtures->createStructure('Other Struct', 'TPLO-' . uniqid());
        $this->fixtures->createTemplate($otherStructure, 'Other Template');

        $results = $this->repository->getTemplatesForStructure($structure)
            ->getQuery()->getResult();

        $names = array_map(function (Template $t) { return $t->getName(); }, $results);
        $this->assertContains('My Template', $names);
        $this->assertNotContains('Other Template', $names);
    }

    // ── findByTypeForUserStructures ──

    public function testFindByTypeForUserStructures(): void
    {
        $user = $this->fixtures->createRawUser('tpl-type-' . uniqid() . '@test.com', 'password', true);
        $structure = $this->fixtures->createStructure('TPL Type Struct', 'TPL-TYPE-' . uniqid());
        $this->fixtures->assignUserToStructure($user, $structure);
        $this->fixtures->createTemplate($structure, 'SMS Template', Communication::TYPE_SMS, 'SMS body');

        $results = $this->repository->findByTypeForUserStructures($user, Type::SMS());

        $names = array_map(function (Template $t) { return $t->getName(); }, $results);
        $this->assertContains('SMS Template', $names);
    }

    public function testFindByTypeForUserStructuresExcludesOtherTypes(): void
    {
        $setup = $this->fixtures->createAdminWithStructure('tpltype-' . uniqid() . '@test.com', 'TPL Type Struct', 'TPLTYPE-' . uniqid());
        $this->fixtures->createTemplate($setup['structure'], 'Email Template', Communication::TYPE_EMAIL, 'Email body');
        $this->fixtures->createTemplate($setup['structure'], 'SMS Template2', Communication::TYPE_SMS, 'SMS body');

        $results = $this->repository->findByTypeForUserStructures($setup['user'], Type::EMAIL());

        $names = array_map(function (Template $t) { return $t->getName(); }, $results);
        $this->assertContains('Email Template', $names);
        $this->assertNotContains('SMS Template2', $names);
    }
}
