<?php

namespace App\Tests\Repository;

use App\Entity\PrefilledAnswers;
use App\Repository\PrefilledAnswersRepository;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PrefilledAnswersRepositoryTest extends KernelTestCase
{
    /** @var PrefilledAnswersRepository */
    private $repository;

    /** @var DataFixtures */
    private $fixtures;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->repository = self::$container->get('doctrine.orm.entity_manager')
            ->getRepository(PrefilledAnswers::class);

        $this->fixtures = new DataFixtures(
            self::$container->get('doctrine.orm.entity_manager'),
            self::$container->get('security.password_encoder')
        );
    }

    // ── getPrefilledAnswersByStructure ──

    public function testGetPrefilledAnswersByStructure(): void
    {
        $structure = $this->fixtures->createStructure('PFA STRUCT 1', 'PFA-EXT-' . uniqid());
        $this->fixtures->createPrefilledAnswers('Struct Answers', ['Oui', 'Non'], $structure);

        $qb = $this->repository->getPrefilledAnswersByStructure($structure);
        $results = $qb->getQuery()->getResult();

        $labels = array_map(function (PrefilledAnswers $pa) { return $pa->getLabel(); }, $results);
        $this->assertContains('Struct Answers', $labels);
    }

    public function testGetPrefilledAnswersByStructureExcludesOtherStructures(): void
    {
        $structure = $this->fixtures->createStructure('PFA MY STRUCT', 'PFA-MY-' . uniqid());
        $this->fixtures->createPrefilledAnswers('My Answers', ['Yes', 'No', 'Maybe'], $structure);
        $otherStructure = $this->fixtures->createStructure('PFA Other', 'PFA-OTHER-' . uniqid());
        $this->fixtures->createPrefilledAnswers('Other Answers', ['A', 'B'], $otherStructure);

        $qb = $this->repository->getPrefilledAnswersByStructure($structure);
        $results = $qb->getQuery()->getResult();

        $labels = array_map(function (PrefilledAnswers $pa) { return $pa->getLabel(); }, $results);
        $this->assertContains('My Answers', $labels);
        $this->assertNotContains('Other Answers', $labels);
    }

    // ── findByUserForStructureAndGlobal ──

    public function testFindByUserForStructureAndGlobal(): void
    {
        $user = $this->fixtures->createRawUser('pfa-user-' . uniqid() . '@test.com', 'password', true);
        $structure = $this->fixtures->createStructure('PFA USER STRUCT', 'PFA-USER-' . uniqid());
        $this->fixtures->assignUserToStructure($user, $structure);
        $this->fixtures->createPrefilledAnswers('User Answers', ['Yes', 'No', 'Maybe'], $structure);

        // Also create a global PFA (no structure)
        $this->fixtures->createPrefilledAnswers('Global Answers', ['Y', 'N'], null);

        $results = $this->repository->findByUserForStructureAndGlobal($user);

        $labels = array_map(function (PrefilledAnswers $pa) { return $pa->getLabel(); }, $results);
        $this->assertContains('User Answers', $labels);
        $this->assertContains('Global Answers', $labels);
    }

    public function testFindByUserForStructureAndGlobalExcludesOtherStructures(): void
    {
        $user = $this->fixtures->createRawUser('pfa-excl-' . uniqid() . '@test.com', 'password', true);
        $structure = $this->fixtures->createStructure('PFA ACCESS STRUCT', 'PFA-ACC-' . uniqid());
        $this->fixtures->assignUserToStructure($user, $structure);
        $this->fixtures->createPrefilledAnswers('Accessible Answers', ['Yes', 'No', 'Maybe'], $structure);
        $otherStructure = $this->fixtures->createStructure('PFA Inacc', 'PFA-INACC-' . uniqid());
        $this->fixtures->createPrefilledAnswers('Inaccessible Answers', ['X'], $otherStructure);

        $results = $this->repository->findByUserForStructureAndGlobal($user);

        $labels = array_map(function (PrefilledAnswers $pa) { return $pa->getLabel(); }, $results);
        $this->assertContains('Accessible Answers', $labels);
        $this->assertNotContains('Inaccessible Answers', $labels);
    }

    // ── getGlobalPrefilledAnswers ──

    public function testGetGlobalPrefilledAnswers(): void
    {
        $this->fixtures->createPrefilledAnswers('Global Only', ['Go'], null);

        $results = $this->repository->getGlobalPrefilledAnswers()
            ->getQuery()->getResult();

        $labels = array_map(function (PrefilledAnswers $pa) { return $pa->getLabel(); }, $results);
        $this->assertContains('Global Only', $labels);
    }

    public function testGetGlobalPrefilledAnswersExcludesStructureBound(): void
    {
        $structure = $this->fixtures->createStructure('PFA Excl Struct', 'PFA-EXCL-EXT');
        $this->fixtures->createPrefilledAnswers('Struct Bound', ['Y', 'N'], $structure);
        $this->fixtures->createPrefilledAnswers('Global Free', ['A', 'B'], null);

        $results = $this->repository->getGlobalPrefilledAnswers()
            ->getQuery()->getResult();

        $labels = array_map(function (PrefilledAnswers $pa) { return $pa->getLabel(); }, $results);
        $this->assertContains('Global Free', $labels);
        $this->assertNotContains('Struct Bound', $labels);
    }
}
