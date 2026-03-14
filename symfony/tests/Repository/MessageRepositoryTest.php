<?php

namespace App\Tests\Repository;

use App\Entity\Communication;
use App\Entity\Message;
use App\Repository\MessageRepository;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MessageRepositoryTest extends KernelTestCase
{
    /** @var MessageRepository */
    private $repository;

    /** @var DataFixtures */
    private $fixtures;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->repository = self::$container->get('doctrine.orm.entity_manager')
            ->getRepository(Message::class);

        $this->fixtures = new DataFixtures(
            self::$container->get('doctrine.orm.entity_manager'),
            self::$container->get('security.password_encoder')
        );
    }

    private function buildFullCampaign(string $prefix): array
    {
        return $this->fixtures->createFullCampaign(
            $prefix . '@test.com',
            false,
            Communication::TYPE_SMS,
            ['Yes', 'No']
        );
    }

    // ── findOneByIdNoCache ──

    public function testFindOneByIdNoCache(): void
    {
        $full = $this->buildFullCampaign('msg-find');
        $message = $full['message'];

        $found = $this->repository->findOneByIdNoCache($message->getId());
        $this->assertNotNull($found);
        $this->assertSame($message->getId(), $found->getId());
    }

    public function testFindOneByIdNoCacheReturnsNullForNonexistent(): void
    {
        $found = $this->repository->findOneByIdNoCache(999999);
        $this->assertNull($found);
    }

    // ── getNumberOfSentMessages ──

    public function testGetNumberOfSentMessages(): void
    {
        $full = $this->buildFullCampaign('msg-sent');
        $message = $full['message'];
        $message->setMessageId('twilio-123');
        $em = self::$container->get('doctrine.orm.entity_manager');
        $em->persist($message);
        $em->flush();

        $count = $this->repository->getNumberOfSentMessages($full['campaign']);
        $this->assertSame(1, (int) $count);
    }

    public function testGetNumberOfSentMessagesReturnsZeroWhenNoneSent(): void
    {
        $full = $this->buildFullCampaign('msg-nosent');
        $count = $this->repository->getNumberOfSentMessages($full['campaign']);
        $this->assertSame(0, (int) $count);
    }

    // ── getLatestMessageUpdated ──

    public function testGetLatestMessageUpdated(): void
    {
        $this->buildFullCampaign('msg-latest');

        $latest = $this->repository->getLatestMessageUpdated();
        $this->assertNotNull($latest);
        $this->assertInstanceOf(Message::class, $latest);
    }

    // ── getActiveMessagesForVolunteer ──

    public function testGetActiveMessagesForVolunteer(): void
    {
        $full = $this->buildFullCampaign('msg-active');

        $results = $this->repository->getActiveMessagesForVolunteer($full['volunteer']);

        $this->assertNotEmpty($results);
        $ids = array_map(function (Message $m) { return $m->getId(); }, $results);
        $this->assertContains($full['message']->getId(), $ids);
    }

    public function testGetActiveMessagesForVolunteerExcludesInactiveCampaigns(): void
    {
        $full = $this->buildFullCampaign('msg-inactv');
        $full['campaign']->setActive(false);
        $em = self::$container->get('doctrine.orm.entity_manager');
        $em->persist($full['campaign']);
        $em->flush();

        $results = $this->repository->getActiveMessagesForVolunteer($full['volunteer']);

        $ids = array_map(function (Message $m) { return $m->getId(); }, $results);
        $this->assertNotContains($full['message']->getId(), $ids);
    }

    // ── getLatestMessagesForVolunteer ──

    public function testGetLatestMessagesForVolunteer(): void
    {
        $full = $this->buildFullCampaign('msg-lastvol');

        $results = $this->repository->getLatestMessagesForVolunteer($full['volunteer']);

        $this->assertNotEmpty($results);
        $this->assertLessThanOrEqual(10, count($results));
    }

    // ── getUsedPrefixes ──

    public function testGetUsedPrefixes(): void
    {
        $full = $this->buildFullCampaign('msg-prefix');
        $volunteer = $full['volunteer'];

        $prefixes = $this->repository->getUsedPrefixes([$volunteer->getId()]);

        $this->assertArrayHasKey($volunteer->getId(), $prefixes);
        $this->assertNotEmpty($prefixes[$volunteer->getId()]);
    }

    // ── canUsePrefixesForEveryone ──

    public function testCanUsePrefixesForEveryoneReturnsTrueWhenEmpty(): void
    {
        $this->assertTrue($this->repository->canUsePrefixesForEveryone([]));
    }

    // ── updateMessageStatus ──

    public function testUpdateMessageStatus(): void
    {
        $full = $this->buildFullCampaign('msg-status');
        $message = $full['message'];

        $message->setMessageId('test-msg-id-123');
        $message->setSent(true);
        $this->repository->updateMessageStatus($message);

        $em = self::$container->get('doctrine.orm.entity_manager');
        $em->clear();
        $fresh = $this->repository->findOneByIdNoCache($message->getId());
        $this->assertSame('test-msg-id-123', $fresh->getMessageId());
        $this->assertTrue($fresh->isSent());
    }

    // ── refresh ──

    public function testRefresh(): void
    {
        $full = $this->buildFullCampaign('msg-refresh');
        $message = $full['message'];

        $refreshed = $this->repository->refresh($message);
        $this->assertNotNull($refreshed);
        $this->assertSame($message->getId(), $refreshed->getId());
    }
}
