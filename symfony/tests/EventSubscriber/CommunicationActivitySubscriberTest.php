<?php

namespace App\Tests\EventSubscriber;

use App\Entity\Answer;
use App\Entity\Campaign;
use App\Entity\Communication;
use App\Entity\Cost;
use App\Entity\Message;
use App\EventSubscriber\CommunicationActivitySubscriber;
use DateTime;
use PHPUnit\Framework\TestCase;

class CommunicationActivitySubscriberTest extends TestCase
{
    /** @var CommunicationActivitySubscriber */
    private $subscriber;

    protected function setUp() : void
    {
        $this->subscriber = new CommunicationActivitySubscriber();
    }

    private function createCommunication(?Campaign $campaign = null) : Communication
    {
        $communication = new Communication();
        $communication->setType(Communication::TYPE_SMS);
        $communication->setBody('Test body');
        $communication->setCreatedAt(new DateTime());

        if ($campaign) {
            $campaign->setExpiresAt(new DateTime());
            $campaign->addCommunication($communication);
        }

        return $communication;
    }

    private function pendingCampaignIds() : array
    {
        $property = new \ReflectionProperty(CommunicationActivitySubscriber::class, 'pendingCampaignIds');
        $property->setAccessible(true);

        return $property->getValue($this->subscriber);
    }

    // ──────────────────────────────────────────────
    // Regression: during campaign creation, a message can be flushed before
    // its communication is linked to a campaign. The subscriber must not
    // explode on the (then null) campaign — it used to call the non-nullable
    // Communication::getCampaign() and raise a TypeError.
    // ──────────────────────────────────────────────

    public function testOnChangeWithMessageOnUnlinkedCommunicationDoesNotThrow() : void
    {
        $communication = $this->createCommunication(); // no campaign yet
        $message       = new Message();
        $communication->addMessage($message);

        $this->subscriber->onChange($message);

        $this->assertSame([], $this->pendingCampaignIds(), 'No campaign should be tracked while the communication is unlinked');
    }

    public function testOnChangeWithMessageTracksLinkedCampaign() : void
    {
        $campaign = new Campaign();
        $campaign->setId(42);
        $communication = $this->createCommunication($campaign);
        $message       = new Message();
        $communication->addMessage($message);

        $this->subscriber->onChange($message);

        $this->assertSame([42], $this->pendingCampaignIds());
    }

    public function testOnChangeResetsCommunicationReport() : void
    {
        $communication = $this->createCommunication();
        $message       = new Message();
        $communication->addMessage($message);

        $this->subscriber->onChange($message);

        $this->assertNull($communication->getReport());
    }

    public function testOnChangeWithAnswerOnUnlinkedCommunicationDoesNotThrow() : void
    {
        $communication = $this->createCommunication(); // no campaign yet
        $message       = new Message();
        $communication->addMessage($message);

        $answer = new Answer();
        $answer->setMessage($message);

        $this->subscriber->onChange($answer);

        $this->assertSame([], $this->pendingCampaignIds());
    }

    public function testOnChangeWithCostTracksLinkedCampaign() : void
    {
        $campaign = new Campaign();
        $campaign->setId(7);
        $communication = $this->createCommunication($campaign);
        $message       = new Message();
        $communication->addMessage($message);

        $cost = new Cost();
        $cost->setMessage($message);

        $this->subscriber->onChange($cost);

        $this->assertSame([7], $this->pendingCampaignIds());
    }

    public function testOnChangeDoesNotTrackDuplicateCampaignIds() : void
    {
        $campaign = new Campaign();
        $campaign->setId(99);
        $communication = $this->createCommunication($campaign);

        $m1 = new Message();
        $m2 = new Message();
        $communication->addMessage($m1);
        $communication->addMessage($m2);

        $this->subscriber->onChange($m1);
        $this->subscriber->onChange($m2);

        $this->assertSame([99], $this->pendingCampaignIds());
    }

    public function testOnChangeIgnoresUnrelatedEntities() : void
    {
        $this->subscriber->onChange(new \stdClass());

        $this->assertSame([], $this->pendingCampaignIds());
    }
}
