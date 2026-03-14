<?php

namespace App\Tests\Entity;

use App\Entity\Answer;
use App\Entity\Campaign;
use App\Entity\Choice;
use App\Entity\Communication;
use App\Entity\Cost;
use App\Entity\Media;
use App\Entity\Message;
use App\Entity\Phone;
use App\Entity\Volunteer;
use App\Task\SendCallTask;
use App\Task\SendEmailTask;
use App\Task\SendSmsTask;
use DateTime;
use PHPUnit\Framework\TestCase;

class CommunicationTest extends TestCase
{
    private function createCommunication(string $type = Communication::TYPE_SMS): Communication
    {
        $comm = new Communication();
        $comm->setType($type);
        $comm->setBody('Test body');
        $comm->setCreatedAt(new DateTime());

        return $comm;
    }

    private function createChoice(int $id, string $code, string $label): Choice
    {
        $choice = new Choice();
        $choice->setId($id);
        $choice->setCode($code);
        $choice->setLabel($label);

        return $choice;
    }

    private function createVolunteer(
        bool $phoneOptin = true,
        ?string $phoneE164 = '+33600000000',
        bool $phoneMobile = true,
        bool $emailOptin = true,
        ?string $email = 'test@example.com'
    ): Volunteer {
        $volunteer = new Volunteer();
        $volunteer->setPhoneNumberOptin($phoneOptin);
        $volunteer->setEmailOptin($emailOptin);

        if ($email) {
            $volunteer->setEmail($email);
        }

        if ($phoneE164) {
            $phone = new Phone();
            $phone->setE164($phoneE164);
            $phone->setPreferred(true);
            $phone->setMobile($phoneMobile);
            $phone->setCountryCode('FR');
            $phone->setPrefix(33);
            $phone->setNational('06 00 00 00 00');
            $phone->setInternational('+33 6 00 00 00 00');
            $volunteer->addPhone($phone);
        }

        return $volunteer;
    }

    private function createMessage(
        Communication $comm,
        Volunteer $volunteer,
        bool $sent = false,
        ?string $error = null
    ): Message {
        $message = new Message();
        $message->setVolunteer($volunteer);
        $message->setSent($sent);
        if ($error) {
            $message->setError($error);
        }
        $comm->addMessage($message);

        return $message;
    }

    private function createAnswer(
        Message $message,
        bool $unclear = false,
        ?string $byAdmin = null,
        array $choices = [],
        ?DateTime $receivedAt = null
    ): Answer {
        $answer = new Answer();
        $answer->setRaw('raw answer');
        $answer->setUnclear($unclear);
        $answer->setReceivedAt($receivedAt ?? new DateTime());
        $answer->setUpdatedAt(new DateTime());
        if ($byAdmin) {
            $answer->setByAdmin($byAdmin);
        }
        foreach ($choices as $choice) {
            $answer->addChoice($choice);
        }
        $answer->setMessage($message);
        $message->addAnswser($answer);

        return $answer;
    }

    // ---- getLimitedBody ----

    public function testGetLimitedBodyReturnsFullBodyWhenShort(): void
    {
        $comm = $this->createCommunication();
        $comm->setBody('Short body');

        $this->assertSame('Short body', $comm->getLimitedBody());
    }

    public function testGetLimitedBodyTruncatesLongBody(): void
    {
        $comm = $this->createCommunication();
        $body = str_repeat('A', 400);
        $comm->setBody($body);

        $result = $comm->getLimitedBody();

        $this->assertSame(300, mb_strlen($result));
        $this->assertStringEndsWith('...', $result);
        $this->assertStringStartsWith(str_repeat('A', 297), $result);
    }

    public function testGetLimitedBodyExactBoundary(): void
    {
        $comm = $this->createCommunication();
        $body = str_repeat('B', 300);
        $comm->setBody($body);

        $this->assertSame($body, $comm->getLimitedBody());
    }

    public function testGetLimitedBodyCustomLimit(): void
    {
        $comm = $this->createCommunication();
        $comm->setBody(str_repeat('C', 100));

        $result = $comm->getLimitedBody(50);

        $this->assertSame(50, mb_strlen($result));
        $this->assertStringEndsWith('...', $result);
    }

    public function testGetLimitedBodyMultibyteCharacters(): void
    {
        $comm = $this->createCommunication();
        $body = str_repeat("\u{00E9}", 301); // e-acute, multibyte
        $comm->setBody($body);

        $result = $comm->getLimitedBody();

        $this->assertSame(300, mb_strlen($result));
        $this->assertStringEndsWith('...', $result);
    }

    // ---- addMessage ----

    public function testAddMessageAddsAndSetsCommunication(): void
    {
        $comm = $this->createCommunication();
        $message = new Message();
        $message->setVolunteer($this->createVolunteer());

        $result = $comm->addMessage($message);

        $this->assertSame($comm, $result);
        $this->assertCount(1, $comm->getMessages());
        $this->assertSame($comm, $message->getCommunication());
    }

    public function testAddMultipleMessages(): void
    {
        $comm = $this->createCommunication();
        $m1 = new Message();
        $m1->setVolunteer($this->createVolunteer());
        $m2 = new Message();
        $m2->setVolunteer($this->createVolunteer());

        $comm->addMessage($m1);
        $comm->addMessage($m2);

        $this->assertCount(2, $comm->getMessages());
    }

    // ---- addChoice ----

    public function testAddChoiceAddsAndSetsCommunication(): void
    {
        $comm = $this->createCommunication();
        $choice = $this->createChoice(1, '1', 'Yes');

        $result = $comm->addChoice($choice);

        $this->assertSame($comm, $result);
        $this->assertCount(1, $comm->getChoices());
        $this->assertSame($comm, $choice->getCommunication());
    }

    // ---- getChoiceByCode ----

    public function testGetChoiceByCodeDirectLabelMatch(): void
    {
        $comm = $this->createCommunication();
        $choice = $this->createChoice(1, '1', 'Yes');
        $comm->addChoice($choice);

        $result = $comm->getChoiceByCode('AB', 'yes');

        $this->assertSame($choice, $result);
    }

    public function testGetChoiceByCodeDirectLabelMatchCaseInsensitive(): void
    {
        $comm = $this->createCommunication();
        $choice = $this->createChoice(1, '1', 'Available');
        $comm->addChoice($choice);

        $result = $comm->getChoiceByCode('AB', 'available');

        $this->assertSame($choice, $result);
    }

    public function testGetChoiceByCodeWithValidPrefixAndCode(): void
    {
        $comm = $this->createCommunication();
        $choice = $this->createChoice(1, '1', 'Yes');
        $comm->addChoice($choice);

        $result = $comm->getChoiceByCode('AB', 'AB1');

        $this->assertSame($choice, $result);
    }

    public function testGetChoiceByCodeWithSpaceBetweenPrefixAndCode(): void
    {
        $comm = $this->createCommunication();
        $choice = $this->createChoice(1, '1', 'Yes');
        $comm->addChoice($choice);

        // Regex collapses "AB 1" -> "AB1"
        $result = $comm->getChoiceByCode('AB', 'AB 1');

        $this->assertSame($choice, $result);
    }

    public function testGetChoiceByCodeReturnsNullForInvalidPrefix(): void
    {
        $comm = $this->createCommunication();
        $choice = $this->createChoice(1, '1', 'Yes');
        $comm->addChoice($choice);

        $result = $comm->getChoiceByCode('AB', 'XY1');

        $this->assertNull($result);
    }

    public function testGetChoiceByCodeReturnsNullForInvalidCode(): void
    {
        $comm = $this->createCommunication();
        $choice = $this->createChoice(1, '1', 'Yes');
        $comm->addChoice($choice);

        $result = $comm->getChoiceByCode('AB', 'AB9');

        $this->assertNull($result);
    }

    public function testGetChoiceByCodeReturnsNullWhenPrefixIsNull(): void
    {
        $comm = $this->createCommunication();
        $choice = $this->createChoice(1, '1', 'Yes');
        $comm->addChoice($choice);

        // With null prefix and a non-label code, should return null
        $result = $comm->getChoiceByCode(null, 'AB1');

        $this->assertNull($result);
    }

    public function testGetChoiceByCodeMultipleWordsPicksCorrectOne(): void
    {
        $comm = $this->createCommunication();
        $choice1 = $this->createChoice(1, '1', 'Yes');
        $choice2 = $this->createChoice(2, '2', 'No');
        $comm->addChoice($choice1);
        $comm->addChoice($choice2);

        $result = $comm->getChoiceByCode('AB', 'AB2');

        $this->assertSame($choice2, $result);
    }

    // ---- getAllChoicesInText ----

    public function testGetAllChoicesInTextReturnsEmptyWhenNullPrefix(): void
    {
        $comm = $this->createCommunication();
        $choice = $this->createChoice(1, '1', 'Yes');
        $comm->addChoice($choice);

        $result = $comm->getAllChoicesInText(null, 'AB1 AB2');

        $this->assertSame([], $result);
    }

    public function testGetAllChoicesInTextReturnsMultipleChoices(): void
    {
        $comm = $this->createCommunication();
        $choice1 = $this->createChoice(1, '1', 'Yes');
        $choice2 = $this->createChoice(2, '2', 'No');
        $comm->addChoice($choice1);
        $comm->addChoice($choice2);

        $result = $comm->getAllChoicesInText('AB', 'AB1 AB2');

        $this->assertCount(2, $result);
        $this->assertSame($choice1, $result[0]);
        $this->assertSame($choice2, $result[1]);
    }

    public function testGetAllChoicesInTextFiltersInvalid(): void
    {
        $comm = $this->createCommunication();
        $choice1 = $this->createChoice(1, '1', 'Yes');
        $comm->addChoice($choice1);

        $result = $comm->getAllChoicesInText('AB', 'AB1 XY9');

        $this->assertCount(1, $result);
        $this->assertSame($choice1, array_values($result)[0]);
    }

    // ---- getFirstChoice ----

    public function testGetFirstChoiceReturnsFirstChoice(): void
    {
        $comm = $this->createCommunication();
        $choice1 = $this->createChoice(1, '1', 'Yes');
        $choice2 = $this->createChoice(2, '2', 'No');
        $comm->addChoice($choice1);
        $comm->addChoice($choice2);

        $this->assertSame($choice1, $comm->getFirstChoice());
    }

    public function testGetFirstChoiceReturnsNullWhenEmpty(): void
    {
        $comm = $this->createCommunication();

        $this->assertNull($comm->getFirstChoice());
    }

    // ---- getChoiceByLabel ----

    public function testGetChoiceByLabelReturnsMatchingChoice(): void
    {
        $comm = $this->createCommunication();
        $choice = $this->createChoice(1, '1', 'Available');
        $comm->addChoice($choice);

        $this->assertSame($choice, $comm->getChoiceByLabel('Available'));
    }

    public function testGetChoiceByLabelReturnsNullWhenNoMatch(): void
    {
        $comm = $this->createCommunication();
        $choice = $this->createChoice(1, '1', 'Available');
        $comm->addChoice($choice);

        $this->assertNull($comm->getChoiceByLabel('Unavailable'));
    }

    public function testGetChoiceByLabelIsCaseSensitive(): void
    {
        $comm = $this->createCommunication();
        $choice = $this->createChoice(1, '1', 'Available');
        $comm->addChoice($choice);

        $this->assertNull($comm->getChoiceByLabel('available'));
    }

    // ---- isUnclear ----

    public function testIsUnclearReturnsFalseWhenNullPrefix(): void
    {
        $comm = $this->createCommunication();

        $this->assertFalse($comm->isUnclear(null, 'AB1'));
    }

    public function testIsUnclearReturnsFalseForValidSingleAnswer(): void
    {
        $comm = $this->createCommunication();
        $choice = $this->createChoice(1, '1', 'Yes');
        $comm->addChoice($choice);

        $this->assertFalse($comm->isUnclear('AB', 'AB1'));
    }

    public function testIsUnclearReturnsTrueWhenNoPrefix(): void
    {
        $comm = $this->createCommunication();
        $choice = $this->createChoice(1, '1', 'Yes');
        $comm->addChoice($choice);

        // A word without matching prefix-digit pattern
        $this->assertTrue($comm->isUnclear('AB', 'hello'));
    }

    public function testIsUnclearReturnsTrueForWrongPrefix(): void
    {
        $comm = $this->createCommunication();
        $choice = $this->createChoice(1, '1', 'Yes');
        $comm->addChoice($choice);

        $this->assertTrue($comm->isUnclear('AB', 'XY1'));
    }

    public function testIsUnclearReturnsTrueForInvalidChoiceCode(): void
    {
        $comm = $this->createCommunication();
        $choice = $this->createChoice(1, '1', 'Yes');
        $comm->addChoice($choice);

        $this->assertTrue($comm->isUnclear('AB', 'AB9'));
    }

    public function testIsUnclearReturnsTrueForRepeatedAnswer(): void
    {
        $comm = $this->createCommunication();
        $comm->setMultipleAnswer(true);
        $choice = $this->createChoice(1, '1', 'Yes');
        $comm->addChoice($choice);

        $this->assertTrue($comm->isUnclear('AB', 'AB1 AB1'));
    }

    // ---- getEstimatedCost ----

    public function testGetEstimatedCostSms(): void
    {
        $comm = $this->createCommunication(Communication::TYPE_SMS);
        $this->createMessage($comm, $this->createVolunteer());
        $this->createMessage($comm, $this->createVolunteer());

        // Short body = 1 SMS part
        $cost = $comm->getEstimatedCost('Hello');

        $this->assertEqualsWithDelta(2 * Message::SMS_COST, $cost, 0.0001);
    }

    public function testGetEstimatedCostCall(): void
    {
        $comm = $this->createCommunication(Communication::TYPE_CALL);
        $this->createMessage($comm, $this->createVolunteer());
        $this->createMessage($comm, $this->createVolunteer());
        $this->createMessage($comm, $this->createVolunteer());

        $cost = $comm->getEstimatedCost('Hello');

        $this->assertEqualsWithDelta(3 * Message::CALL_COST, $cost, 0.0001);
    }

    public function testGetEstimatedCostEmail(): void
    {
        $comm = $this->createCommunication(Communication::TYPE_EMAIL);
        $this->createMessage($comm, $this->createVolunteer());

        $cost = $comm->getEstimatedCost('Hello');

        $this->assertEqualsWithDelta(1 * Message::EMAIL_COST, $cost, 0.0001);
    }

    public function testGetEstimatedCostSmsMultipleParts(): void
    {
        $comm = $this->createCommunication(Communication::TYPE_SMS);
        $this->createMessage($comm, $this->createVolunteer());

        // 200 ASCII chars => 2 parts (>160 => multipart, ~153 chars per part)
        $body = str_repeat('A', 200);
        $cost = $comm->getEstimatedCost($body);

        $this->assertEqualsWithDelta(2 * 1 * Message::SMS_COST, $cost, 0.0001);
    }

    // ---- getPartitionedMessages ----

    public function testGetPartitionedMessagesWithAnswers(): void
    {
        $comm = $this->createCommunication();
        $choice = $this->createChoice(1, '1', 'Yes');
        $comm->addChoice($choice);

        $v1 = $this->createVolunteer();
        $v2 = $this->createVolunteer();

        $m1 = $this->createMessage($comm, $v1);
        $m2 = $this->createMessage($comm, $v2);

        // m1 has an answer, m2 does not
        $this->createAnswer($m1, false, null, [$choice]);

        $result = $comm->getPartitionedMessages();

        $this->assertCount(1, $result['active']);
        $this->assertCount(1, $result['pending']);
        $this->assertSame($m1, $result['active'][0]);
        $this->assertSame($m2, $result['pending'][0]);
    }

    public function testGetPartitionedMessagesWithError(): void
    {
        $comm = $this->createCommunication();
        $v = $this->createVolunteer();
        $m = $this->createMessage($comm, $v, false, 'Error');

        $result = $comm->getPartitionedMessages();

        $this->assertCount(1, $result['active']);
        $this->assertCount(0, $result['pending']);
    }

    public function testGetPartitionedMessagesAllPending(): void
    {
        $comm = $this->createCommunication();
        $this->createMessage($comm, $this->createVolunteer());
        $this->createMessage($comm, $this->createVolunteer());

        $result = $comm->getPartitionedMessages();

        $this->assertCount(0, $result['active']);
        $this->assertCount(2, $result['pending']);
    }

    // ---- computeChoiceCounts ----

    public function testComputeChoiceCountsEmpty(): void
    {
        $comm = $this->createCommunication();
        $choice = $this->createChoice(1, '1', 'Yes');
        $comm->addChoice($choice);

        $counts = $comm->computeChoiceCounts();

        $this->assertSame([1 => 0], $counts);
    }

    public function testComputeChoiceCountsWithAnswers(): void
    {
        $comm = $this->createCommunication();
        $choice1 = $this->createChoice(1, '1', 'Yes');
        $choice2 = $this->createChoice(2, '2', 'No');
        $comm->addChoice($choice1);
        $comm->addChoice($choice2);

        $v1 = $this->createVolunteer();
        $v2 = $this->createVolunteer();
        $v3 = $this->createVolunteer();

        $m1 = $this->createMessage($comm, $v1);
        $m2 = $this->createMessage($comm, $v2);
        $m3 = $this->createMessage($comm, $v3);

        $this->createAnswer($m1, false, null, [$choice1]);
        $this->createAnswer($m2, false, null, [$choice1]);
        $this->createAnswer($m3, false, null, [$choice2]);

        $counts = $comm->computeChoiceCounts();

        $this->assertSame(2, $counts[1]);
        $this->assertSame(1, $counts[2]);
    }

    public function testComputeChoiceCountsIsCached(): void
    {
        $comm = $this->createCommunication();
        $choice = $this->createChoice(1, '1', 'Yes');
        $comm->addChoice($choice);

        $counts1 = $comm->computeChoiceCounts();
        $counts2 = $comm->computeChoiceCounts();

        $this->assertSame($counts1, $counts2);
    }

    // ---- getInvalidAnswersCount ----

    public function testGetInvalidAnswersCountZero(): void
    {
        $comm = $this->createCommunication();
        $choice = $this->createChoice(1, '1', 'Yes');
        $comm->addChoice($choice);

        $m = $this->createMessage($comm, $this->createVolunteer());
        // Valid answer (has choice)
        $this->createAnswer($m, false, null, [$choice]);

        $this->assertSame(0, $comm->getInvalidAnswersCount());
    }

    public function testGetInvalidAnswersCountWithInvalidAnswer(): void
    {
        $comm = $this->createCommunication();

        $m = $this->createMessage($comm, $this->createVolunteer());
        // Invalid answer: no choice attached
        $this->createAnswer($m, false, null, []);

        $this->assertSame(1, $comm->getInvalidAnswersCount());
    }

    public function testGetInvalidAnswersCountMultiple(): void
    {
        $comm = $this->createCommunication();

        $m1 = $this->createMessage($comm, $this->createVolunteer());
        $m2 = $this->createMessage($comm, $this->createVolunteer());
        $m3 = $this->createMessage($comm, $this->createVolunteer());

        // m1: invalid (no choices), m2: invalid, m3: no answer
        $this->createAnswer($m1, false, null, []);
        $this->createAnswer($m2, false, null, []);

        $this->assertSame(2, $comm->getInvalidAnswersCount());
    }

    // ---- noAnswersCount ----

    public function testNoAnswersCountAllNoAnswers(): void
    {
        $comm = $this->createCommunication();
        $this->createMessage($comm, $this->createVolunteer());
        $this->createMessage($comm, $this->createVolunteer());

        $this->assertSame(2, $comm->noAnswersCount());
    }

    public function testNoAnswersCountMixed(): void
    {
        $comm = $this->createCommunication();
        $choice = $this->createChoice(1, '1', 'Yes');
        $comm->addChoice($choice);

        $m1 = $this->createMessage($comm, $this->createVolunteer());
        $m2 = $this->createMessage($comm, $this->createVolunteer());

        // m1 has a non-admin answer
        $this->createAnswer($m1, false, null, [$choice]);

        $this->assertSame(1, $comm->noAnswersCount());
    }

    public function testNoAnswersCountIgnoresAdminAnswers(): void
    {
        $comm = $this->createCommunication();
        $choice = $this->createChoice(1, '1', 'Yes');
        $comm->addChoice($choice);

        $m = $this->createMessage($comm, $this->createVolunteer());
        // Only admin answer: hasAnswer(false) will return false because getLastAnswer skips admin
        $this->createAnswer($m, false, 'admin-user', [$choice]);

        $this->assertSame(1, $comm->noAnswersCount());
    }

    // ---- countReachables ----

    public function testCountReachablesSmsWithOptinAndPhone(): void
    {
        $comm = $this->createCommunication(Communication::TYPE_SMS);

        $v = $this->createVolunteer(true, '+33600000000', true, true, 'test@test.com');
        $this->createMessage($comm, $v, false);

        $this->assertSame(1, $comm->countReachables());
    }

    public function testCountReachablesSmsWithOptedOutVolunteer(): void
    {
        $comm = $this->createCommunication(Communication::TYPE_SMS);

        $v = $this->createVolunteer(false, '+33600000000', true, true, 'test@test.com');
        $this->createMessage($comm, $v, false);

        $this->assertSame(0, $comm->countReachables());
    }

    public function testCountReachablesSmsWithNoPhone(): void
    {
        $comm = $this->createCommunication(Communication::TYPE_SMS);

        $v = $this->createVolunteer(true, null, true, true, 'test@test.com');
        $this->createMessage($comm, $v, false);

        $this->assertSame(0, $comm->countReachables());
    }

    public function testCountReachablesSmsWithError(): void
    {
        $comm = $this->createCommunication(Communication::TYPE_SMS);

        $v = $this->createVolunteer(true, '+33600000000', true, true, 'test@test.com');
        $this->createMessage($comm, $v, false, 'Delivery failed');

        $this->assertSame(0, $comm->countReachables());
    }

    public function testCountReachablesEmailWithOptinAndEmail(): void
    {
        $comm = $this->createCommunication(Communication::TYPE_EMAIL);

        $v = $this->createVolunteer(true, null, true, true, 'test@test.com');
        $this->createMessage($comm, $v, false);

        $this->assertSame(1, $comm->countReachables());
    }

    public function testCountReachablesEmailOptedOut(): void
    {
        $comm = $this->createCommunication(Communication::TYPE_EMAIL);

        $v = $this->createVolunteer(true, null, true, false, 'test@test.com');
        $this->createMessage($comm, $v, false);

        $this->assertSame(0, $comm->countReachables());
    }

    public function testCountReachablesEmailWithNoEmail(): void
    {
        $comm = $this->createCommunication(Communication::TYPE_EMAIL);

        $v = $this->createVolunteer(true, null, true, true, null);
        $this->createMessage($comm, $v, false);

        $this->assertSame(0, $comm->countReachables());
    }

    public function testCountReachablesCallReachable(): void
    {
        $comm = $this->createCommunication(Communication::TYPE_CALL);

        $v = $this->createVolunteer(true, '+33600000000', true, true, null);
        $this->createMessage($comm, $v, false);

        $this->assertSame(1, $comm->countReachables());
    }

    // ---- getSendTaskName ----

    public function testGetSendTaskNameSms(): void
    {
        $comm = $this->createCommunication(Communication::TYPE_SMS);

        $this->assertSame(SendSmsTask::class, $comm->getSendTaskName());
    }

    public function testGetSendTaskNameCall(): void
    {
        $comm = $this->createCommunication(Communication::TYPE_CALL);

        $this->assertSame(SendCallTask::class, $comm->getSendTaskName());
    }

    public function testGetSendTaskNameEmail(): void
    {
        $comm = $this->createCommunication(Communication::TYPE_EMAIL);

        $this->assertSame(SendEmailTask::class, $comm->getSendTaskName());
    }

    public function testGetSendTaskNameThrowsOnInvalidType(): void
    {
        $comm = $this->createCommunication();
        $comm->setType('invalid');

        $this->expectException(\LogicException::class);
        $comm->getSendTaskName();
    }

    // ---- getProgression ----

    public function testGetProgressionEmpty(): void
    {
        $comm = $this->createCommunication(Communication::TYPE_SMS);

        $progress = $comm->getProgression();

        $this->assertSame(0, $progress['sent']);
        $this->assertSame(0, $progress['total']);
        $this->assertSame(0, $progress['reachable']);
        $this->assertEquals(0, $progress['percent']);
        $this->assertSame(0, $progress['replies']);
        $this->assertEquals(0, $progress['replies-percent']);
        $this->assertSame(Communication::TYPE_SMS, $progress['type']);
    }

    public function testGetProgressionWithSentAndReplied(): void
    {
        $comm = $this->createCommunication(Communication::TYPE_SMS);
        $choice = $this->createChoice(1, '1', 'Yes');
        $comm->addChoice($choice);

        $v1 = $this->createVolunteer();
        $v2 = $this->createVolunteer();
        $v3 = $this->createVolunteer();

        $m1 = $this->createMessage($comm, $v1, true);
        $m2 = $this->createMessage($comm, $v2, true);
        $m3 = $this->createMessage($comm, $v3, false);

        // m1 has an answer
        $this->createAnswer($m1, false, null, [$choice]);

        $progress = $comm->getProgression();

        $this->assertSame(2, $progress['sent']);
        $this->assertSame(3, $progress['total']);
        $this->assertSame(3, $progress['reachable']);
        $this->assertEqualsWithDelta(66.67, $progress['percent'], 0.01);
        $this->assertSame(1, $progress['replies']);
        $this->assertEqualsWithDelta(50.0, $progress['replies-percent'], 0.01);
    }

    // ---- addImage / removeImage ----

    public function testAddImageAddsAndSetsCommunication(): void
    {
        $comm = $this->createCommunication();
        $media = new Media();
        $media->setUuid('test-uuid');

        $result = $comm->addImage($media);

        $this->assertSame($comm, $result);
        $this->assertCount(1, $comm->getImages());
        $this->assertSame($comm, $media->getCommunication());
    }

    public function testAddImageDoesNotAddDuplicate(): void
    {
        $comm = $this->createCommunication();
        $media = new Media();
        $media->setUuid('test-uuid');

        $comm->addImage($media);
        $comm->addImage($media);

        $this->assertCount(1, $comm->getImages());
    }

    public function testRemoveImageRemovesAndNullsCommunication(): void
    {
        $comm = $this->createCommunication();
        $media = new Media();
        $media->setUuid('test-uuid');
        $comm->addImage($media);

        $result = $comm->removeImage($media);

        $this->assertSame($comm, $result);
        $this->assertCount(0, $comm->getImages());
        $this->assertNull($media->getCommunication());
    }

    public function testRemoveImageDoesNothingWhenAbsent(): void
    {
        $comm = $this->createCommunication();
        $media = new Media();
        $media->setUuid('test-uuid');

        $result = $comm->removeImage($media);

        $this->assertSame($comm, $result);
        $this->assertCount(0, $comm->getImages());
    }

    // ---- getLastAnswerTime ----

    public function testGetLastAnswerTimeReturnsDefaultWhenNoAnswers(): void
    {
        $comm = $this->createCommunication();
        $this->createMessage($comm, $this->createVolunteer());

        $this->assertSame('--:--', $comm->getLastAnswerTime());
    }

    public function testGetLastAnswerTimeWithChoiceFilter(): void
    {
        $comm = $this->createCommunication();
        $choice = $this->createChoice(1, '1', 'Yes');
        $comm->addChoice($choice);

        $m = $this->createMessage($comm, $this->createVolunteer());
        $date = new DateTime('2024-03-15 14:30:00');
        $this->createAnswer($m, false, null, [$choice], $date);

        $result = $comm->getLastAnswerTime($choice);

        $this->assertSame('15/03 14:30', $result);
    }

    public function testGetLastAnswerTimeWithoutChoiceFiltersValidAnswers(): void
    {
        $comm = $this->createCommunication();
        $choice = $this->createChoice(1, '1', 'Yes');
        $comm->addChoice($choice);

        $m = $this->createMessage($comm, $this->createVolunteer());

        // A valid answer (has choices) is skipped when no choice filter given
        $date1 = new DateTime('2024-03-15 14:30:00');
        $this->createAnswer($m, false, null, [$choice], $date1);

        $result = $comm->getLastAnswerTime();

        // The valid answer should be skipped (answer->isValid() returns true)
        $this->assertSame('--:--', $result);
    }

    public function testGetLastAnswerTimePicksLatestAnswer(): void
    {
        $comm = $this->createCommunication();
        $choice = $this->createChoice(1, '1', 'Yes');
        $comm->addChoice($choice);

        $m1 = $this->createMessage($comm, $this->createVolunteer());
        $m2 = $this->createMessage($comm, $this->createVolunteer());

        $date1 = new DateTime('2024-03-15 14:30:00');
        $date2 = new DateTime('2024-03-15 16:00:00');

        $this->createAnswer($m1, false, null, [$choice], $date1);
        $this->createAnswer($m2, false, null, [$choice], $date2);

        $result = $comm->getLastAnswerTime($choice);

        $this->assertSame('15/03 16:00', $result);
    }

    public function testGetLastAnswerTimeNoChoiceInvalidAnswer(): void
    {
        $comm = $this->createCommunication();

        $m = $this->createMessage($comm, $this->createVolunteer());
        $date = new DateTime('2024-06-01 09:15:00');
        // Invalid answer (no choices) => isValid() returns false => picked by getLastAnswerTime(null)
        $this->createAnswer($m, false, null, [], $date);

        $result = $comm->getLastAnswerTime();

        $this->assertSame('01/06 09:15', $result);
    }

    // ---- getCost ----

    public function testGetCostEmpty(): void
    {
        $comm = $this->createCommunication();

        $this->assertEqualsWithDelta(0.0, $comm->getCost(), 0.0001);
    }

    public function testGetCostSumsMessageCosts(): void
    {
        $comm = $this->createCommunication();

        $m1 = $this->createMessage($comm, $this->createVolunteer());
        $m2 = $this->createMessage($comm, $this->createVolunteer());

        $cost1 = new Cost();
        $cost1->setPrice('0.05');
        $cost1->setDirection(Cost::DIRECTION_OUTBOUND);
        $cost1->setFromNumber('+33100000000');
        $cost1->setToNumber('+33600000001');
        $cost1->setBody('test');
        $cost1->setCurrency('USD');
        $m1->addCost($cost1);

        $cost2 = new Cost();
        $cost2->setPrice('0.10');
        $cost2->setDirection(Cost::DIRECTION_OUTBOUND);
        $cost2->setFromNumber('+33100000000');
        $cost2->setToNumber('+33600000002');
        $cost2->setBody('test');
        $cost2->setCurrency('USD');
        $m2->addCost($cost2);

        $this->assertEqualsWithDelta(0.15, $comm->getCost(), 0.0001);
    }
}
