<?php

namespace App\Tests\Entity;

use App\Entity\Campaign;
use App\Entity\Choice;
use App\Entity\Communication;
use App\Entity\Cost;
use App\Entity\Message;
use App\Entity\Report;
use App\Entity\Volunteer;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

class CampaignTest extends TestCase
{
    private function createCampaign(): Campaign
    {
        $campaign = new Campaign();
        $campaign->setLabel('Test Campaign');
        $campaign->setCreatedAt(new \DateTime());
        $campaign->setExpiresAt(new \DateTime('+30 days'));
        $campaign->setCommunications(new ArrayCollection());

        return $campaign;
    }

    private function createCommunication(string $type, ?string $shortcut = null): Communication
    {
        $communication = new Communication();
        $communication->setType($type);
        $communication->setBody('Test body');
        $communication->setCreatedAt(new \DateTime());
        $communication->setLanguage('fr');
        if ($shortcut) {
            $communication->setShortcut($shortcut);
        }

        return $communication;
    }

    // --- getCode ---

    public function testGetCodeReturnsNull(): void
    {
        $campaign = $this->createCampaign();
        $this->assertNull($campaign->getCode());
    }

    public function testGetCodeReturnsString(): void
    {
        $campaign = $this->createCampaign();
        $campaign->setCode('ABCD1234');

        $this->assertSame('ABCD1234', $campaign->getCode());
    }

    public function testGetCodeHandlesResource(): void
    {
        $campaign = $this->createCampaign();

        // Simulate a stream resource as Doctrine returns for binary columns
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, 'STREAM01');
        rewind($stream);

        // Use reflection to set the private property directly
        $ref = new \ReflectionProperty(Campaign::class, 'code');
        $ref->setAccessible(true);
        $ref->setValue($campaign, $stream);

        $this->assertSame('STREAM01', $campaign->getCode());

        // Second call should still work (no longer a resource)
        $this->assertSame('STREAM01', $campaign->getCode());
    }

    // --- getCommunicationByType ---

    public function testGetCommunicationByTypeFindsMatch(): void
    {
        $campaign = $this->createCampaign();
        $sms = $this->createCommunication(Communication::TYPE_SMS);
        $email = $this->createCommunication(Communication::TYPE_EMAIL);

        $campaign->setCommunications(new ArrayCollection([$sms, $email]));

        $this->assertSame($sms, $campaign->getCommunicationByType(Communication::TYPE_SMS));
        $this->assertSame($email, $campaign->getCommunicationByType(Communication::TYPE_EMAIL));
    }

    public function testGetCommunicationByTypeReturnsNullWhenNotFound(): void
    {
        $campaign = $this->createCampaign();
        $sms = $this->createCommunication(Communication::TYPE_SMS);
        $campaign->setCommunications(new ArrayCollection([$sms]));

        $this->assertNull($campaign->getCommunicationByType(Communication::TYPE_CALL));
    }

    public function testGetCommunicationByTypeReturnsFirstMatch(): void
    {
        $campaign = $this->createCampaign();
        $sms1 = $this->createCommunication(Communication::TYPE_SMS);
        $sms1->setLabel('First SMS');
        $sms2 = $this->createCommunication(Communication::TYPE_SMS);
        $sms2->setLabel('Second SMS');

        $campaign->setCommunications(new ArrayCollection([$sms1, $sms2]));

        $result = $campaign->getCommunicationByType(Communication::TYPE_SMS);
        $this->assertSame($sms1, $result);
    }

    // --- addCommunication ---

    public function testAddCommunicationAppendsToCampaign(): void
    {
        $campaign = $this->createCampaign();
        $communication = $this->createCommunication(Communication::TYPE_SMS);

        $campaign->addCommunication($communication);

        $this->assertCount(1, $campaign->getCommunications());
        $this->assertSame($campaign, $communication->getCampaign());
    }

    public function testAddCommunicationExtendsExpirationWhenNeeded(): void
    {
        $campaign = $this->createCampaign();
        // Set expiration to now (less than 7 days from now)
        $campaign->setExpiresAt(new \DateTime());
        $communication = $this->createCommunication(Communication::TYPE_SMS);

        $campaign->addCommunication($communication);

        $expectedMin = time() + Campaign::DEFAULT_EXPIRATION - 5; // 5 second tolerance
        $this->assertGreaterThan($expectedMin, $campaign->getExpiresAt()->getTimestamp());
    }

    public function testAddCommunicationKeepsExpirationWhenFarEnough(): void
    {
        $campaign = $this->createCampaign();
        $farFuture = new \DateTime('+30 days');
        $campaign->setExpiresAt($farFuture);

        $originalTimestamp = $farFuture->getTimestamp();
        $communication = $this->createCommunication(Communication::TYPE_EMAIL);

        $campaign->addCommunication($communication);

        // expiresAt should not be changed since it is already far in the future
        $this->assertSame($originalTimestamp, $campaign->getExpiresAt()->getTimestamp());
    }

    // --- getCampaignProgression ---

    public function testGetCampaignProgressionEmpty(): void
    {
        $campaign = $this->createCampaign();
        $this->assertSame([], $campaign->getCampaignProgression());
    }

    public function testGetCampaignProgressionWithCommunications(): void
    {
        $campaign = $this->createCampaign();
        $sms = $this->createCommunication(Communication::TYPE_SMS);

        $ref = new \ReflectionProperty(Communication::class, 'id');
        $ref->setAccessible(true);
        $ref->setValue($sms, 10);

        $campaign->setCommunications(new ArrayCollection([$sms]));

        $progression = $campaign->getCampaignProgression();

        $this->assertArrayHasKey(10, $progression);
        $this->assertIsArray($progression[10]);
        $this->assertArrayHasKey('sent', $progression[10]);
        $this->assertArrayHasKey('total', $progression[10]);
    }

    // --- getCost ---

    public function testGetCostEmpty(): void
    {
        $campaign = $this->createCampaign();
        $this->assertSame(0.0, $campaign->getCost());
    }

    public function testGetCostSumsAcrossCommunications(): void
    {
        $campaign = $this->createCampaign();

        // Create communications with messages that have costs
        $sms = $this->createCommunication(Communication::TYPE_SMS);
        $email = $this->createCommunication(Communication::TYPE_EMAIL);

        // Create messages with costs for sms
        $msg1 = new Message();
        $msg1->setVolunteer($this->createVolunteer());
        $cost1 = new Cost();
        $cost1->setPrice('0.05');
        $cost1->setCurrency('EUR');
        $cost1->setDirection(Cost::DIRECTION_OUTBOUND);
        $cost1->setFromNumber('+33600000000');
        $cost1->setToNumber('+33600000001');
        $cost1->setBody('test');
        $msg1->addCost($cost1);
        $sms->addMessage($msg1);

        // Create messages with costs for email
        $msg2 = new Message();
        $msg2->setVolunteer($this->createVolunteer());
        $cost2 = new Cost();
        $cost2->setPrice('0.01');
        $cost2->setCurrency('EUR');
        $cost2->setDirection(Cost::DIRECTION_OUTBOUND);
        $cost2->setFromNumber('from@test.com');
        $cost2->setToNumber('to@test.com');
        $cost2->setBody('test');
        $msg2->addCost($cost2);
        $email->addMessage($msg2);

        $campaign->setCommunications(new ArrayCollection([$sms, $email]));

        $this->assertEqualsWithDelta(0.06, $campaign->getCost(), 0.001);
    }

    // --- isReportReady ---

    public function testIsReportReadyWhenAllHaveReports(): void
    {
        $campaign = $this->createCampaign();
        $sms = $this->createCommunication(Communication::TYPE_SMS);
        $report = new Report();
        $report->setType(Communication::TYPE_SMS);
        $sms->setReport($report);

        $campaign->setCommunications(new ArrayCollection([$sms]));

        $this->assertTrue($campaign->isReportReady());
    }

    public function testIsReportReadyWhenSomeMissingReports(): void
    {
        $campaign = $this->createCampaign();
        $sms = $this->createCommunication(Communication::TYPE_SMS);
        $report = new Report();
        $report->setType(Communication::TYPE_SMS);
        $sms->setReport($report);

        $email = $this->createCommunication(Communication::TYPE_EMAIL);
        // email has no report

        $campaign->setCommunications(new ArrayCollection([$sms, $email]));

        $this->assertFalse($campaign->isReportReady());
    }

    public function testIsReportReadyWhenNoCommunications(): void
    {
        $campaign = $this->createCampaign();
        $this->assertTrue($campaign->isReportReady());
    }

    // --- hasChoices ---

    public function testHasChoicesReturnsTrueWhenChoicesExist(): void
    {
        $campaign = $this->createCampaign();
        $sms = $this->createCommunication(Communication::TYPE_SMS);
        $choice = new Choice();
        $choice->setCode('1');
        $choice->setLabel('Yes');
        $sms->addChoice($choice);

        $campaign->setCommunications(new ArrayCollection([$sms]));

        $this->assertTrue($campaign->hasChoices());
    }

    public function testHasChoicesReturnsFalseWhenNoChoices(): void
    {
        $campaign = $this->createCampaign();
        $sms = $this->createCommunication(Communication::TYPE_SMS);

        $campaign->setCommunications(new ArrayCollection([$sms]));

        $this->assertFalse($campaign->hasChoices());
    }

    public function testHasChoicesReturnsFalseWhenNoCommunications(): void
    {
        $campaign = $this->createCampaign();
        $this->assertFalse($campaign->hasChoices());
    }

    public function testHasChoicesTrueIfAnyCommHasChoices(): void
    {
        $campaign = $this->createCampaign();
        $sms = $this->createCommunication(Communication::TYPE_SMS);
        // sms has no choices

        $email = $this->createCommunication(Communication::TYPE_EMAIL);
        $choice = new Choice();
        $choice->setCode('1');
        $choice->setLabel('Confirm');
        $email->addChoice($choice);

        $campaign->setCommunications(new ArrayCollection([$sms, $email]));

        $this->assertTrue($campaign->hasChoices());
    }

    // --- getRenderedShortcuts ---

    public function testGetRenderedShortcutsEmpty(): void
    {
        $campaign = $this->createCampaign();
        $this->assertSame('', $campaign->getRenderedShortcuts());
    }

    public function testGetRenderedShortcutsWithNullShortcuts(): void
    {
        $campaign = $this->createCampaign();
        $sms = $this->createCommunication(Communication::TYPE_SMS);
        // shortcut is null by default

        $campaign->setCommunications(new ArrayCollection([$sms]));

        $this->assertSame('', $campaign->getRenderedShortcuts());
    }

    public function testGetRenderedShortcutsSingle(): void
    {
        $campaign = $this->createCampaign();
        $sms = $this->createCommunication(Communication::TYPE_SMS, 'AB');

        $campaign->setCommunications(new ArrayCollection([$sms]));

        $this->assertSame('(AB)', $campaign->getRenderedShortcuts());
    }

    public function testGetRenderedShortcutsMultipleUnique(): void
    {
        $campaign = $this->createCampaign();
        $sms = $this->createCommunication(Communication::TYPE_SMS, 'AB');
        $email = $this->createCommunication(Communication::TYPE_EMAIL, 'CD');

        $campaign->setCommunications(new ArrayCollection([$sms, $email]));

        $result = $campaign->getRenderedShortcuts();
        $this->assertSame('(AB, CD)', $result);
    }

    public function testGetRenderedShortcutsDeduplicates(): void
    {
        $campaign = $this->createCampaign();
        $sms1 = $this->createCommunication(Communication::TYPE_SMS, 'AB');
        $sms2 = $this->createCommunication(Communication::TYPE_SMS, 'AB');

        $campaign->setCommunications(new ArrayCollection([$sms1, $sms2]));

        $this->assertSame('(AB)', $campaign->getRenderedShortcuts());
    }

    public function testGetRenderedShortcutsMixedNullAndValues(): void
    {
        $campaign = $this->createCampaign();
        $sms = $this->createCommunication(Communication::TYPE_SMS, 'AB');
        $email = $this->createCommunication(Communication::TYPE_EMAIL); // null shortcut

        $campaign->setCommunications(new ArrayCollection([$sms, $email]));

        $this->assertSame('(AB)', $campaign->getRenderedShortcuts());
    }

    private function createVolunteer(): Volunteer
    {
        $volunteer = new Volunteer();
        $volunteer->setExternalId('vol-' . random_int(1, 999999));
        $volunteer->setEnabled(true);

        return $volunteer;
    }
}
