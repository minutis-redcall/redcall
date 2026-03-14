<?php

namespace App\Tests\Repository;

use App\Entity\Answer;
use App\Repository\AnswerRepository;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AnswerRepositoryTest extends KernelTestCase
{
    /** @var AnswerRepository */
    private $repository;

    /** @var DataFixtures */
    private $fixtures;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->repository = self::$container->get('doctrine.orm.entity_manager')
            ->getRepository(Answer::class);

        $this->fixtures = new DataFixtures(
            self::$container->get('doctrine.orm.entity_manager'),
            self::$container->get('security.password_encoder')
        );
    }

    // ── clearAnswers ──

    public function testClearAnswers(): void
    {
        $fullCampaign = $this->fixtures->createFullCampaign('clrans@test.com');
        $answer = $this->fixtures->createAnswer(
            $fullCampaign['message'],
            'A1',
            false,
            [$fullCampaign['choices'][0]]
        );

        $this->assertCount(1, $answer->getChoices());

        // Refresh message from DB so its answers collection is loaded
        $em = self::$container->get('doctrine.orm.entity_manager');
        $em->clear();
        $freshMessage = $em->getRepository(\App\Entity\Message::class)->find($fullCampaign['message']->getId());

        $this->repository->clearAnswers($freshMessage);

        $em->clear();
        $freshAnswer = $this->repository->find($answer->getId());
        $this->assertCount(0, $freshAnswer->getChoices());
    }

    // ── clearChoices ──

    public function testClearChoices(): void
    {
        $fullCampaign = $this->fixtures->createFullCampaign('clrch@test.com');
        $choice = $fullCampaign['choices'][0];
        $answer = $this->fixtures->createAnswer(
            $fullCampaign['message'],
            'A1',
            false,
            [$choice]
        );

        // Refresh message from DB so its answers collection is loaded
        $em = self::$container->get('doctrine.orm.entity_manager');
        $em->clear();
        $freshMessage = $em->getRepository(\App\Entity\Message::class)->find($fullCampaign['message']->getId());
        $freshChoice = $em->getRepository(\App\Entity\Choice::class)->find($choice->getId());

        $this->repository->clearChoices($freshMessage, [$freshChoice]);

        $em->clear();
        $freshAnswer = $this->repository->find($answer->getId());
        $this->assertCount(0, $freshAnswer->getChoices());
    }

    // ── getSearchQueryBuilder ──

    public function testGetSearchQueryBuilder(): void
    {
        $fullCampaign = $this->fixtures->createFullCampaign('srchans@test.com');
        $this->fixtures->createAnswer($fullCampaign['message'], 'UniqueAnswerText123');

        $results = $this->repository->getSearchQueryBuilder('UniqueAnswerText123')
            ->getQuery()->getResult();

        $raws = array_map(function (Answer $a) { return $a->getRaw(); }, $results);
        $this->assertContains('UniqueAnswerText123', $raws);
    }

    public function testGetSearchQueryBuilderNoResults(): void
    {
        $results = $this->repository->getSearchQueryBuilder('XXXXXXXXNONEXISTENT999')
            ->getQuery()->getResult();

        $this->assertEmpty($results);
    }

    // ── getVolunteerAnswersQueryBuilder ──

    public function testGetVolunteerAnswersQueryBuilder(): void
    {
        $fullCampaign = $this->fixtures->createFullCampaign('volans@test.com');
        $this->fixtures->createAnswer($fullCampaign['message'], 'Volunteer Answer');

        $results = $this->repository->getVolunteerAnswersQueryBuilder($fullCampaign['volunteer'])
            ->getQuery()->getResult();

        $this->assertNotEmpty($results);
        $raws = array_map(function (Answer $a) { return $a->getRaw(); }, $results);
        $this->assertContains('Volunteer Answer', $raws);
    }

    public function testGetVolunteerAnswersQueryBuilderEmptyForOtherVolunteer(): void
    {
        $fullCampaign = $this->fixtures->createFullCampaign('otherv@test.com');
        $this->fixtures->createAnswer($fullCampaign['message'], 'My Answer');

        $otherVol = $this->fixtures->createStandaloneVolunteer('OTHER-ANS-001', 'otherans@test.com');

        $results = $this->repository->getVolunteerAnswersQueryBuilder($otherVol)
            ->getQuery()->getResult();

        $this->assertEmpty($results);
    }
}
