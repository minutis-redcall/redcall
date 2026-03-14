<?php

namespace App\Tests\Security\Voter;

use App\Entity\VolunteerSession;
use App\Security\Voter\VolunteerSessionVoter;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class VolunteerSessionVoterTest extends KernelTestCase
{
    private VolunteerSessionVoter $voter;
    private DataFixtures $fixtures;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = static::getContainer();

        $this->voter = $container->get(VolunteerSessionVoter::class);
        $this->fixtures = new DataFixtures(
            $container->get('doctrine.orm.entity_manager'),
            $container->get('security.password_encoder')
        );
    }

    private function createAnonymousToken(): UsernamePasswordToken
    {
        return $this->createMock(UsernamePasswordToken::class);
    }

    private function setHttpSessionValue(string $key, $value): void
    {
        static::getContainer()->get('session')->set($key, $value);
    }

    // ────────────────────────────────────────────────────────
    // supports()
    // ────────────────────────────────────────────────────────

    public function testSupportsReturnsTrueForVolunteerSessionSubject(): void
    {
        $setup = $this->fixtures->createVolunteerWithSession('VOL-VSV-SUP');
        $sessionId = $setup['session']->getSessionId();

        $this->setHttpSessionValue('volunteer-session', $sessionId);
        $token = $this->createAnonymousToken();

        $result = $this->voter->vote($token, $setup['session'], ['VOLUNTEER_SESSION']);
        $this->assertNotEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testSupportsReturnsFalseForNonVolunteerSessionSubject(): void
    {
        $token = $this->createAnonymousToken();
        $badge = $this->fixtures->createBadge('Not Session', 'NOT-SESS-001');

        $result = $this->voter->vote($token, $badge, ['VOLUNTEER_SESSION']);
        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testSupportsReturnsFalseForNullSubject(): void
    {
        $token = $this->createAnonymousToken();

        $result = $this->voter->vote($token, null, ['VOLUNTEER_SESSION']);
        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    // ────────────────────────────────────────────────────────
    // Session ID matching
    // ────────────────────────────────────────────────────────

    public function testGrantedWhenSessionIdMatches(): void
    {
        $setup = $this->fixtures->createVolunteerWithSession('VOL-VSV-MATCH');
        $sessionId = $setup['session']->getSessionId();

        $this->setHttpSessionValue('volunteer-session', $sessionId);
        $token = $this->createAnonymousToken();

        $result = $this->voter->vote($token, $setup['session'], ['VOLUNTEER_SESSION']);
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testDeniedWhenSessionIdDoesNotMatch(): void
    {
        $setup = $this->fixtures->createVolunteerWithSession('VOL-VSV-NOMATCH');

        $this->setHttpSessionValue('volunteer-session', 'wrong-session-id');
        $token = $this->createAnonymousToken();

        $result = $this->voter->vote($token, $setup['session'], ['VOLUNTEER_SESSION']);
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testDeniedWhenNoSessionValueSet(): void
    {
        $setup = $this->fixtures->createVolunteerWithSession('VOL-VSV-NOSESS');

        // Ensure the session key is not set
        $session = static::getContainer()->get('session');
        $session->remove('volunteer-session');

        $token = $this->createAnonymousToken();

        $result = $this->voter->vote($token, $setup['session'], ['VOLUNTEER_SESSION']);
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testDeniedWhenSessionValueIsNull(): void
    {
        $setup = $this->fixtures->createVolunteerWithSession('VOL-VSV-NULLS');

        $this->setHttpSessionValue('volunteer-session', null);
        $token = $this->createAnonymousToken();

        // VolunteerSession's sessionId is never null (set by createVolunteerSession),
        // so null !== 'some-id' -> denied
        $result = $this->voter->vote($token, $setup['session'], ['VOLUNTEER_SESSION']);
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testTwoSessionsOnlyMatchingOneIsGranted(): void
    {
        $setup1 = $this->fixtures->createVolunteerWithSession('VOL-VSV-TWO1', 'two1@test.com');
        $setup2 = $this->fixtures->createVolunteerWithSession('VOL-VSV-TWO2', 'two2@test.com');

        // Set HTTP session to match session 1
        $this->setHttpSessionValue('volunteer-session', $setup1['session']->getSessionId());
        $token = $this->createAnonymousToken();

        $this->assertEquals(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, $setup1['session'], ['VOLUNTEER_SESSION'])
        );
        $this->assertEquals(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, $setup2['session'], ['VOLUNTEER_SESSION'])
        );
    }

    public function testAnyAttributeIsAcceptedWhenSubjectIsVolunteerSession(): void
    {
        $setup = $this->fixtures->createVolunteerWithSession('VOL-VSV-ATTR');
        $this->setHttpSessionValue('volunteer-session', $setup['session']->getSessionId());
        $token = $this->createAnonymousToken();

        // Any attribute works as long as subject is a VolunteerSession
        $result = $this->voter->vote($token, $setup['session'], ['RANDOM_ATTR']);
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testVoterDoesNotRequireAuthentication(): void
    {
        // This voter does not check user authentication at all,
        // it only checks the HTTP session value
        $setup = $this->fixtures->createVolunteerWithSession('VOL-VSV-NOAUTH');
        $this->setHttpSessionValue('volunteer-session', $setup['session']->getSessionId());

        // Token has no user
        $token = $this->createAnonymousToken();

        $result = $this->voter->vote($token, $setup['session'], ['VOLUNTEER_SESSION']);
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }
}
