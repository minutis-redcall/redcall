<?php

namespace App\Tests\Provider\Minutis;

use App\Provider\Minutis\Minutis;
use Bundles\SettingsBundle\Manager\SettingManager;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[AllowMockObjectsWithoutExpectations]
class MinutisTest extends TestCase
{
    /**
     * Builds a Minutis provider whose underlying HTTP client is backed by the
     * given queue of responses, and whose settings store already holds a valid
     * (non-expired) auth token so no real /api/auth round-trip is attempted.
     *
     * @param Response[] $responses
     */
    private function createMinutis(array $responses) : Minutis
    {
        $settingManager = $this->createMock(SettingManager::class);
        // A valid, non-expired serialized token short-circuits createToken().
        $settingManager->method('get')->willReturn($this->validSerializedToken());

        $minutis = new Minutis($settingManager, new NullLogger(), new NullLogger());

        $mock   = new MockHandler($responses);
        $client = new Client(['handler' => HandlerStack::create($mock)]);

        $property = new \ReflectionProperty(Minutis::class, 'client');
        $property->setAccessible(true);
        $property->setValue($minutis, $client);

        return $minutis;
    }

    private function validSerializedToken() : string
    {
        $reflection = new \ReflectionClass(\App\Model\MinutisToken::class);
        $token      = $reflection->newInstanceWithoutConstructor();
        $token->setAccessToken('test-token');
        $token->setAccessTokenExpiresAt(time() + 3600);

        return $token->serialize();
    }

    // ──────────────────────────────────────────────
    // Regression: a 403/404 (or any error response) from the Minutis resource
    // endpoint used to surface as an uncaught GuzzleHttp ClientException,
    // crashing the whole campaign creation. It must now degrade gracefully to
    // null (same as a "volunteer not found" empty result), so that
    // OperationManager::createOperation can simply skip the operation owner.
    // ──────────────────────────────────────────────

    public function testSearchForVolunteerReturnsNullOnForbidden() : void
    {
        $minutis = $this->createMinutis([
            new Response(403, [], json_encode(['status' => 403, 'error' => 'Forbidden'])),
        ]);

        $this->assertNull($minutis->searchForVolunteer('5169U'));
    }

    public function testSearchForVolunteerReturnsNullOnNotFound() : void
    {
        $minutis = $this->createMinutis([
            new Response(404, [], json_encode(['status' => 404, 'error' => 'Not Found'])),
        ]);

        $this->assertNull($minutis->searchForVolunteer('5169U'));
    }

    public function testSearchForVolunteerReturnsNullOnServerError() : void
    {
        $minutis = $this->createMinutis([
            new Response(500, [], json_encode(['status' => 500, 'error' => 'Internal Server Error'])),
        ]);

        $this->assertNull($minutis->searchForVolunteer('5169U'));
    }

    public function testSearchForVolunteerReturnsNullOnEmptyResult() : void
    {
        $minutis = $this->createMinutis([
            new Response(200, [], json_encode(['entities' => []])),
        ]);

        $this->assertNull($minutis->searchForVolunteer('5169U'));
    }

    public function testSearchForVolunteerReturnsFirstEntityOnSuccess() : void
    {
        $entity  = ['id' => 1, 'attributes' => ['mail' => ['value' => 'a@b.c']]];
        $minutis = $this->createMinutis([
            new Response(200, [], json_encode(['entities' => [$entity]])),
        ]);

        $this->assertSame($entity, $minutis->searchForVolunteer('5169U'));
    }
}
