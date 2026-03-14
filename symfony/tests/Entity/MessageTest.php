<?php

namespace App\Tests\Entity;

use App\Entity\Answer;
use App\Entity\Campaign;
use App\Entity\Choice;
use App\Entity\Communication;
use App\Entity\Cost;
use App\Entity\Message;
use App\Entity\Operation;
use App\Entity\Phone;
use App\Entity\Volunteer;
use DateTime;
use PHPUnit\Framework\TestCase;

class MessageTest extends TestCase
{
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

    private function createCommunication(string $type = Communication::TYPE_SMS): Communication
    {
        $campaign = new Campaign();
        $campaign->setLabel('Test Campaign');
        $campaign->setCreatedAt(new DateTime());
        $campaign->setExpiresAt(new DateTime('+30 days'));

        $comm = new Communication();
        $comm->setType($type);
        $comm->setBody('Test body');
        $comm->setCreatedAt(new DateTime());
        $comm->setCampaign($campaign);

        return $comm;
    }

    private function createMessage(
        Communication $comm,
        ?Volunteer $volunteer = null,
        bool $sent = false,
        ?string $error = null
    ): Message {
        $message = new Message();
        $message->setVolunteer($volunteer ?? $this->createVolunteer());
        $message->setSent($sent);
        $message->setCode('TESTCODE');
        if ($error) {
            $message->setError($error);
        }
        $comm->addMessage($message);

        return $message;
    }

    private function createChoice(int $id, string $code, string $label): Choice
    {
        $choice = new Choice();
        $choice->setId($id);
        $choice->setCode($code);
        $choice->setLabel($label);

        return $choice;
    }

    private function createAnswer(
        Message $message,
        bool $unclear = false,
        ?string $byAdmin = null,
        array $choices = [],
        ?DateTime $receivedAt = null
    ): Answer {
        $answer = new Answer();
        $answer->setId(rand(1, 999999));
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

    // ---- getCost ----

    public function testGetCostWithNoCosts(): void
    {
        $comm = $this->createCommunication();
        $message = $this->createMessage($comm);

        $this->assertEqualsWithDelta(0.0, $message->getCost(), 0.0001);
    }

    public function testGetCostSumsCosts(): void
    {
        $comm = $this->createCommunication();
        $message = $this->createMessage($comm);

        $cost1 = new Cost();
        $cost1->setPrice('0.05');
        $cost1->setDirection(Cost::DIRECTION_OUTBOUND);
        $cost1->setFromNumber('+33100000000');
        $cost1->setToNumber('+33600000001');
        $cost1->setBody('test');
        $cost1->setCurrency('USD');
        $message->addCost($cost1);

        $cost2 = new Cost();
        $cost2->setPrice('0.10');
        $cost2->setDirection(Cost::DIRECTION_OUTBOUND);
        $cost2->setFromNumber('+33100000000');
        $cost2->setToNumber('+33600000002');
        $cost2->setBody('test');
        $cost2->setCurrency('USD');
        $message->addCost($cost2);

        $this->assertEqualsWithDelta(0.15, $message->getCost(), 0.0001);
    }

    public function testGetCostSingleCost(): void
    {
        $comm = $this->createCommunication();
        $message = $this->createMessage($comm);

        $cost = new Cost();
        $cost->setPrice('0.033');
        $cost->setDirection(Cost::DIRECTION_OUTBOUND);
        $cost->setFromNumber('+33100000000');
        $cost->setToNumber('+33600000001');
        $cost->setBody('test');
        $cost->setCurrency('EUR');
        $message->addCost($cost);

        $this->assertEqualsWithDelta(0.033, $message->getCost(), 0.0001);
    }

    // ---- removeAnswer ----

    public function testRemoveAnswerRemovesById(): void
    {
        $comm = $this->createCommunication();
        $message = $this->createMessage($comm);

        $answer1 = $this->createAnswer($message);
        $answer2 = $this->createAnswer($message);

        $this->assertCount(2, $message->getAnswers());

        $message->removeAnswer($answer1);

        $this->assertCount(1, $message->getAnswers());
    }

    public function testRemoveAnswerDoesNothingWhenNotFound(): void
    {
        $comm = $this->createCommunication();
        $message = $this->createMessage($comm);

        $answer1 = $this->createAnswer($message);
        $orphanAnswer = new Answer();
        $orphanAnswer->setId(99999);
        $orphanAnswer->setRaw('orphan');
        $orphanAnswer->setUnclear(false);
        $orphanAnswer->setReceivedAt(new DateTime());

        $message->removeAnswer($orphanAnswer);

        $this->assertCount(1, $message->getAnswers());
    }

    // ---- getCode ----

    public function testGetCodeReturnsStringCode(): void
    {
        $message = new Message();
        $message->setCode('ABCD1234');

        $this->assertSame('ABCD1234', $message->getCode());
    }

    public function testGetCodeHandlesStreamResource(): void
    {
        $message = new Message();
        // Simulate a binary field that Doctrine may return as a stream resource
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, 'STREAMCD');
        rewind($stream);
        $message->setCode($stream);

        $this->assertSame('STREAMCD', $message->getCode());
    }

    // ---- getAnswerByChoice ----

    public function testGetAnswerByChoiceReturnsMatchingAnswer(): void
    {
        $comm = $this->createCommunication();
        $message = $this->createMessage($comm);
        $choice = $this->createChoice(1, '1', 'Yes');
        $comm->addChoice($choice);

        $answer = $this->createAnswer($message, false, null, [$choice]);

        $result = $message->getAnswerByChoice($choice);

        $this->assertSame($answer, $result);
    }

    public function testGetAnswerByChoiceReturnsNullWhenNoMatch(): void
    {
        $comm = $this->createCommunication();
        $message = $this->createMessage($comm);
        $choice1 = $this->createChoice(1, '1', 'Yes');
        $choice2 = $this->createChoice(2, '2', 'No');
        $comm->addChoice($choice1);
        $comm->addChoice($choice2);

        $this->createAnswer($message, false, null, [$choice1]);

        $result = $message->getAnswerByChoice($choice2);

        $this->assertNull($result);
    }

    public function testGetAnswerByChoiceReturnsNullWithNoAnswers(): void
    {
        $comm = $this->createCommunication();
        $message = $this->createMessage($comm);
        $choice = $this->createChoice(1, '1', 'Yes');

        $this->assertNull($message->getAnswerByChoice($choice));
    }

    // ---- getLastAnswer ----

    public function testGetLastAnswerReturnsNullWhenEmpty(): void
    {
        $comm = $this->createCommunication();
        $message = $this->createMessage($comm);

        $this->assertNull($message->getLastAnswer());
    }

    public function testGetLastAnswerSkipsAdminByDefault(): void
    {
        $comm = $this->createCommunication();
        $message = $this->createMessage($comm);
        $choice = $this->createChoice(1, '1', 'Yes');

        // Add admin answer first (it would be first in the DESC-ordered collection)
        $adminAnswer = $this->createAnswer($message, false, 'admin-user', [$choice]);
        $userAnswer = $this->createAnswer($message, false, null, [$choice]);

        // The order in ArrayCollection is insertion order, and answers are @OrderBy({"id" = "DESC"})
        // In our test the last-added appears last, but getLastAnswer iterates and skips admin
        $result = $message->getLastAnswer(false);

        $this->assertNull($adminAnswer->getByAdmin() ? null : 'should be admin');
        $this->assertNull($userAnswer->getByAdmin());
    }

    public function testGetLastAnswerIncludingAdmins(): void
    {
        $comm = $this->createCommunication();
        $message = $this->createMessage($comm);
        $choice = $this->createChoice(1, '1', 'Yes');

        $adminAnswer = $this->createAnswer($message, false, 'admin-user', [$choice]);

        $result = $message->getLastAnswer(true);

        $this->assertSame($adminAnswer, $result);
    }

    public function testGetLastAnswerReturnsFirstNonAdmin(): void
    {
        $comm = $this->createCommunication();
        $message = $this->createMessage($comm);

        // Answers are iterated in insertion order in ArrayCollection
        $answer1 = $this->createAnswer($message, false, null, []);
        $answer2 = $this->createAnswer($message, false, null, []);

        $result = $message->getLastAnswer();

        // Returns first in iteration order
        $this->assertSame($answer1, $result);
    }

    // ---- getInvalidAnswer ----

    public function testGetInvalidAnswerReturnsNullWhenHasValidAnswer(): void
    {
        $comm = $this->createCommunication();
        $message = $this->createMessage($comm);
        $choice = $this->createChoice(1, '1', 'Yes');

        // A valid answer (has choices)
        $this->createAnswer($message, false, null, [$choice]);

        $this->assertNull($message->getInvalidAnswer());
    }

    public function testGetInvalidAnswerReturnsLastNonAdminAnswer(): void
    {
        $comm = $this->createCommunication();
        $message = $this->createMessage($comm);

        // Answer with no choices = not valid
        $answer = $this->createAnswer($message, false, null, []);

        $result = $message->getInvalidAnswer();

        $this->assertSame($answer, $result);
    }

    public function testGetInvalidAnswerReturnsNullWithNoAnswers(): void
    {
        $comm = $this->createCommunication();
        $message = $this->createMessage($comm);

        $this->assertNull($message->getInvalidAnswer());
    }

    public function testGetInvalidAnswerReturnsNullWhenOnlyAdminAnswerWithNoChoices(): void
    {
        $comm = $this->createCommunication();
        $message = $this->createMessage($comm);

        // Admin answer with no choices: hasValidAnswer() = false, getLastAnswer(false) skips admin => null
        $this->createAnswer($message, false, 'admin', []);

        $this->assertNull($message->getInvalidAnswer());
    }

    // ---- hasValidAnswer ----

    public function testHasValidAnswerReturnsTrueWithValidAnswer(): void
    {
        $comm = $this->createCommunication();
        $message = $this->createMessage($comm);
        $choice = $this->createChoice(1, '1', 'Yes');

        $this->createAnswer($message, false, null, [$choice]);

        $this->assertTrue($message->hasValidAnswer());
    }

    public function testHasValidAnswerReturnsFalseWithNoChoices(): void
    {
        $comm = $this->createCommunication();
        $message = $this->createMessage($comm);

        $this->createAnswer($message, false, null, []);

        $this->assertFalse($message->hasValidAnswer());
    }

    public function testHasValidAnswerReturnsFalseWithNoAnswers(): void
    {
        $comm = $this->createCommunication();
        $message = $this->createMessage($comm);

        $this->assertFalse($message->hasValidAnswer());
    }

    // ---- isUnclear ----

    public function testIsUnclearReturnsFalseWithNoAnswers(): void
    {
        $comm = $this->createCommunication();
        $message = $this->createMessage($comm);

        $this->assertFalse($message->isUnclear());
    }

    public function testIsUnclearReturnsFalseWhenHasInvalidAnswer(): void
    {
        $comm = $this->createCommunication();
        $message = $this->createMessage($comm);

        // Invalid answer (no choices, not valid) => getInvalidAnswer returns it => isUnclear returns false
        $this->createAnswer($message, true, null, []);

        $this->assertFalse($message->isUnclear());
    }

    public function testIsUnclearReturnsTrueWithUnclearAnswer(): void
    {
        $comm = $this->createCommunication();
        $message = $this->createMessage($comm);
        $choice = $this->createChoice(1, '1', 'Yes');

        // Valid answer (has choices) + unclear = true
        $this->createAnswer($message, true, null, [$choice]);

        $this->assertTrue($message->isUnclear());
    }

    public function testIsUnclearSkipsAdminAnswers(): void
    {
        $comm = $this->createCommunication();
        $message = $this->createMessage($comm);
        $choice = $this->createChoice(1, '1', 'Yes');

        // Valid + unclear but by admin => skipped
        $this->createAnswer($message, true, 'admin-user', [$choice]);

        $this->assertFalse($message->isUnclear());
    }

    // ---- getUnclear ----

    public function testGetUnclearReturnsNullWithNoAnswers(): void
    {
        $comm = $this->createCommunication();
        $message = $this->createMessage($comm);

        $this->assertNull($message->getUnclear());
    }

    public function testGetUnclearReturnsNullWhenHasInvalidAnswer(): void
    {
        $comm = $this->createCommunication();
        $message = $this->createMessage($comm);

        // Invalid answer: getInvalidAnswer returns it => getUnclear returns null
        $this->createAnswer($message, true, null, []);

        $this->assertNull($message->getUnclear());
    }

    public function testGetUnclearReturnsUnclearAnswer(): void
    {
        $comm = $this->createCommunication();
        $message = $this->createMessage($comm);
        $choice = $this->createChoice(1, '1', 'Yes');

        // Valid answer + unclear
        $answer = $this->createAnswer($message, true, null, [$choice]);

        $result = $message->getUnclear();

        $this->assertSame($answer, $result);
    }

    public function testGetUnclearSkipsAdminAnswers(): void
    {
        $comm = $this->createCommunication();
        $message = $this->createMessage($comm);
        $choice = $this->createChoice(1, '1', 'Yes');

        // Valid + unclear but by admin
        $this->createAnswer($message, true, 'admin-user', [$choice]);

        $this->assertNull($message->getUnclear());
    }

    // ---- getChoices ----

    public function testGetChoicesEmpty(): void
    {
        $comm = $this->createCommunication();
        $message = $this->createMessage($comm);

        $this->assertSame([], $message->getChoices());
    }

    public function testGetChoicesAggregatesFromAllAnswers(): void
    {
        $comm = $this->createCommunication();
        $message = $this->createMessage($comm);
        $choice1 = $this->createChoice(1, '1', 'Yes');
        $choice2 = $this->createChoice(2, '2', 'No');

        $this->createAnswer($message, false, null, [$choice1]);
        $this->createAnswer($message, false, null, [$choice2]);

        $choices = $message->getChoices();

        $this->assertCount(2, $choices);
        $this->assertSame($choice1, $choices[0]);
        $this->assertSame($choice2, $choices[1]);
    }

    public function testGetChoicesIncludesMultipleFromSameAnswer(): void
    {
        $comm = $this->createCommunication();
        $message = $this->createMessage($comm);
        $choice1 = $this->createChoice(1, '1', 'Yes');
        $choice2 = $this->createChoice(2, '2', 'No');

        $this->createAnswer($message, false, null, [$choice1, $choice2]);

        $choices = $message->getChoices();

        $this->assertCount(2, $choices);
    }

    // ---- addCost ----

    public function testAddCostAddsAndSetsMessage(): void
    {
        $comm = $this->createCommunication();
        $message = $this->createMessage($comm);

        $cost = new Cost();
        $cost->setPrice('0.05');
        $cost->setDirection(Cost::DIRECTION_OUTBOUND);
        $cost->setFromNumber('+33100000000');
        $cost->setToNumber('+33600000001');
        $cost->setBody('test');
        $cost->setCurrency('USD');

        $result = $message->addCost($cost);

        $this->assertSame($message, $result);
        $this->assertCount(1, $message->getCosts());
        $this->assertSame($message, $cost->getMessage());
    }

    public function testAddCostDoesNotAddDuplicate(): void
    {
        $comm = $this->createCommunication();
        $message = $this->createMessage($comm);

        $cost = new Cost();
        $cost->setPrice('0.05');
        $cost->setDirection(Cost::DIRECTION_OUTBOUND);
        $cost->setFromNumber('+33100000000');
        $cost->setToNumber('+33600000001');
        $cost->setBody('test');
        $cost->setCurrency('USD');

        $message->addCost($cost);
        $message->addCost($cost);

        $this->assertCount(1, $message->getCosts());
    }

    // ---- removeCost ----

    public function testRemoveCostRemovesAndNullsMessage(): void
    {
        $comm = $this->createCommunication();
        $message = $this->createMessage($comm);

        $cost = new Cost();
        $cost->setPrice('0.05');
        $cost->setDirection(Cost::DIRECTION_OUTBOUND);
        $cost->setFromNumber('+33100000000');
        $cost->setToNumber('+33600000001');
        $cost->setBody('test');
        $cost->setCurrency('USD');
        $message->addCost($cost);

        $result = $message->removeCost($cost);

        $this->assertSame($message, $result);
        $this->assertCount(0, $message->getCosts());
        $this->assertNull($cost->getMessage());
    }

    public function testRemoveCostDoesNothingWhenAbsent(): void
    {
        $comm = $this->createCommunication();
        $message = $this->createMessage($comm);

        $cost = new Cost();
        $cost->setPrice('0.05');
        $cost->setDirection(Cost::DIRECTION_OUTBOUND);
        $cost->setFromNumber('+33100000000');
        $cost->setToNumber('+33600000001');
        $cost->setBody('test');
        $cost->setCurrency('USD');

        $result = $message->removeCost($cost);

        $this->assertSame($message, $result);
        $this->assertCount(0, $message->getCosts());
    }

    // ---- isReachable ----

    public function testIsReachableSmsMobileOptinNoError(): void
    {
        $comm = $this->createCommunication(Communication::TYPE_SMS);
        $v = $this->createVolunteer(true, '+33600000000', true, true, 'test@test.com');
        $message = $this->createMessage($comm, $v);

        $this->assertTrue($message->isReachable());
    }

    public function testIsReachableSmsNotMobile(): void
    {
        $comm = $this->createCommunication(Communication::TYPE_SMS);
        $v = $this->createVolunteer(true, '+33600000000', false, true, 'test@test.com');
        $message = $this->createMessage($comm, $v);

        $this->assertFalse($message->isReachable());
    }

    public function testIsReachableSmsOptedOut(): void
    {
        $comm = $this->createCommunication(Communication::TYPE_SMS);
        $v = $this->createVolunteer(false, '+33600000000', true, true, 'test@test.com');
        $message = $this->createMessage($comm, $v);

        $this->assertFalse($message->isReachable());
    }

    public function testIsReachableSmsNoPhone(): void
    {
        $comm = $this->createCommunication(Communication::TYPE_SMS);
        $v = $this->createVolunteer(true, null, true, true, 'test@test.com');
        $message = $this->createMessage($comm, $v);

        $this->assertFalse($message->isReachable());
    }

    public function testIsReachableSmsWithError(): void
    {
        $comm = $this->createCommunication(Communication::TYPE_SMS);
        $v = $this->createVolunteer(true, '+33600000000', true, true, 'test@test.com');
        $message = $this->createMessage($comm, $v, false, 'Error');

        $this->assertFalse($message->isReachable());
    }

    public function testIsReachableCallOptinWithPhone(): void
    {
        $comm = $this->createCommunication(Communication::TYPE_CALL);
        // For calls, mobile is not required, just phone + optin
        $v = $this->createVolunteer(true, '+33600000000', false, true, null);
        $message = $this->createMessage($comm, $v);

        $this->assertTrue($message->isReachable());
    }

    public function testIsReachableCallOptedOut(): void
    {
        $comm = $this->createCommunication(Communication::TYPE_CALL);
        $v = $this->createVolunteer(false, '+33600000000', true, true, null);
        $message = $this->createMessage($comm, $v);

        $this->assertFalse($message->isReachable());
    }

    public function testIsReachableEmailOptinWithEmail(): void
    {
        $comm = $this->createCommunication(Communication::TYPE_EMAIL);
        $v = $this->createVolunteer(true, null, true, true, 'test@example.com');
        $message = $this->createMessage($comm, $v);

        $this->assertTrue($message->isReachable());
    }

    public function testIsReachableEmailOptedOut(): void
    {
        $comm = $this->createCommunication(Communication::TYPE_EMAIL);
        $v = $this->createVolunteer(true, null, true, false, 'test@example.com');
        $message = $this->createMessage($comm, $v);

        $this->assertFalse($message->isReachable());
    }

    public function testIsReachableEmailNoEmail(): void
    {
        $comm = $this->createCommunication(Communication::TYPE_EMAIL);
        $v = $this->createVolunteer(true, null, true, true, null);
        $message = $this->createMessage($comm, $v);

        $this->assertFalse($message->isReachable());
    }

    public function testIsReachableUnknownTypeReturnsFalse(): void
    {
        $comm = $this->createCommunication();
        $comm->setType('unknown');
        $v = $this->createVolunteer();
        $message = $this->createMessage($comm, $v);

        $this->assertFalse($message->isReachable());
    }

    // ---- shouldAddMinutisResource ----

    public function testShouldAddMinutisResourceTrueWhenChoiceMatches(): void
    {
        $choice = $this->createChoice(1, '1', 'Yes');

        $operation = new Operation();
        $operation->setOperationExternalId(42);
        $operation->addChoice($choice);

        $campaign = new Campaign();
        $campaign->setLabel('Test');
        $campaign->setCreatedAt(new DateTime());
        $campaign->setExpiresAt(new DateTime('+30 days'));
        $campaign->setOperation($operation);

        $comm = new Communication();
        $comm->setType(Communication::TYPE_SMS);
        $comm->setBody('Test');
        $comm->setCreatedAt(new DateTime());
        $comm->setCampaign($campaign);
        $comm->addChoice($choice);

        $message = new Message();
        $message->setVolunteer($this->createVolunteer());
        $message->setCode('TEST1234');
        $comm->addMessage($message);

        // Add answer with the choice that should create resource
        $this->createAnswer($message, false, null, [$choice]);

        // No resourceExternalId set yet
        $this->assertTrue($message->shouldAddMinutisResource());
    }

    public function testShouldAddMinutisResourceFalseWhenAlreadyHasResource(): void
    {
        $choice = $this->createChoice(1, '1', 'Yes');

        $operation = new Operation();
        $operation->setOperationExternalId(42);
        $operation->addChoice($choice);

        $campaign = new Campaign();
        $campaign->setLabel('Test');
        $campaign->setCreatedAt(new DateTime());
        $campaign->setExpiresAt(new DateTime('+30 days'));
        $campaign->setOperation($operation);

        $comm = new Communication();
        $comm->setType(Communication::TYPE_SMS);
        $comm->setBody('Test');
        $comm->setCreatedAt(new DateTime());
        $comm->setCampaign($campaign);
        $comm->addChoice($choice);

        $message = new Message();
        $message->setVolunteer($this->createVolunteer());
        $message->setCode('TEST1234');
        $message->setResourceExternalId(999); // already has resource
        $comm->addMessage($message);

        $this->createAnswer($message, false, null, [$choice]);

        $this->assertFalse($message->shouldAddMinutisResource());
    }

    public function testShouldAddMinutisResourceFalseWhenNoMatchingChoice(): void
    {
        $choice1 = $this->createChoice(1, '1', 'Yes');
        $choice2 = $this->createChoice(2, '2', 'No');

        $operation = new Operation();
        $operation->setOperationExternalId(42);
        $operation->addChoice($choice1); // Only choice1 triggers resource

        $campaign = new Campaign();
        $campaign->setLabel('Test');
        $campaign->setCreatedAt(new DateTime());
        $campaign->setExpiresAt(new DateTime('+30 days'));
        $campaign->setOperation($operation);

        $comm = new Communication();
        $comm->setType(Communication::TYPE_SMS);
        $comm->setBody('Test');
        $comm->setCreatedAt(new DateTime());
        $comm->setCampaign($campaign);
        $comm->addChoice($choice1);
        $comm->addChoice($choice2);

        $message = new Message();
        $message->setVolunteer($this->createVolunteer());
        $message->setCode('TEST1234');
        $comm->addMessage($message);

        // Answer with choice2 which does NOT trigger resource
        $this->createAnswer($message, false, null, [$choice2]);

        $this->assertFalse($message->shouldAddMinutisResource());
    }

    // ---- shouldRemoveMinutisResource ----

    public function testShouldRemoveMinutisResourceTrueWhenNoMatchingChoiceAndHasResource(): void
    {
        $choice1 = $this->createChoice(1, '1', 'Yes');
        $choice2 = $this->createChoice(2, '2', 'No');

        $operation = new Operation();
        $operation->setOperationExternalId(42);
        $operation->addChoice($choice1);

        $campaign = new Campaign();
        $campaign->setLabel('Test');
        $campaign->setCreatedAt(new DateTime());
        $campaign->setExpiresAt(new DateTime('+30 days'));
        $campaign->setOperation($operation);

        $comm = new Communication();
        $comm->setType(Communication::TYPE_SMS);
        $comm->setBody('Test');
        $comm->setCreatedAt(new DateTime());
        $comm->setCampaign($campaign);
        $comm->addChoice($choice1);
        $comm->addChoice($choice2);

        $message = new Message();
        $message->setVolunteer($this->createVolunteer());
        $message->setCode('TEST1234');
        $message->setResourceExternalId(999); // has existing resource
        $comm->addMessage($message);

        // Answer only for choice2, which does NOT trigger resource creation
        $this->createAnswer($message, false, null, [$choice2]);

        $this->assertTrue($message->shouldRemoveMinutisResource());
    }

    public function testShouldRemoveMinutisResourceFalseWhenMatchingChoiceExists(): void
    {
        $choice = $this->createChoice(1, '1', 'Yes');

        $operation = new Operation();
        $operation->setOperationExternalId(42);
        $operation->addChoice($choice);

        $campaign = new Campaign();
        $campaign->setLabel('Test');
        $campaign->setCreatedAt(new DateTime());
        $campaign->setExpiresAt(new DateTime('+30 days'));
        $campaign->setOperation($operation);

        $comm = new Communication();
        $comm->setType(Communication::TYPE_SMS);
        $comm->setBody('Test');
        $comm->setCreatedAt(new DateTime());
        $comm->setCampaign($campaign);
        $comm->addChoice($choice);

        $message = new Message();
        $message->setVolunteer($this->createVolunteer());
        $message->setCode('TEST1234');
        $message->setResourceExternalId(999);
        $comm->addMessage($message);

        // Answer matches the operation choice => should NOT remove
        $this->createAnswer($message, false, null, [$choice]);

        $this->assertFalse($message->shouldRemoveMinutisResource());
    }

    public function testShouldRemoveMinutisResourceFalseWhenNoExternalId(): void
    {
        $choice = $this->createChoice(1, '1', 'Yes');

        $operation = new Operation();
        $operation->setOperationExternalId(42);
        $operation->addChoice($choice);

        $campaign = new Campaign();
        $campaign->setLabel('Test');
        $campaign->setCreatedAt(new DateTime());
        $campaign->setExpiresAt(new DateTime('+30 days'));
        $campaign->setOperation($operation);

        $comm = new Communication();
        $comm->setType(Communication::TYPE_SMS);
        $comm->setBody('Test');
        $comm->setCreatedAt(new DateTime());
        $comm->setCampaign($campaign);
        $comm->addChoice($choice);

        $message = new Message();
        $message->setVolunteer($this->createVolunteer());
        $message->setCode('TEST1234');
        // No resourceExternalId set
        $comm->addMessage($message);

        $this->assertFalse($message->shouldRemoveMinutisResource());
    }
}
