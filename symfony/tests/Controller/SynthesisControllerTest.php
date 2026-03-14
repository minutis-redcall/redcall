<?php

namespace App\Tests\Controller;

use App\Tests\Base\BaseWebTestCase;
use App\Tests\Fixtures\DataFixtures;

class SynthesisControllerTest extends BaseWebTestCase
{
    private function getFixtures($container) : DataFixtures
    {
        return new DataFixtures(
            $container->get('doctrine.orm.entity_manager'),
            $container->get('security.password_encoder')
        );
    }

    public function testSynthesisIndex()
    {
        $client    = static::createClient();
        $container = $client->getContainer();
        $fixtures  = $this->getFixtures($container);

        $campaign = $fixtures->createCampaign('Synthesis Test Campaign');

        // Campaign needs a code for the synthesis route
        $code = bin2hex(random_bytes(4));
        $campaign->setCode($code);
        $container->get('doctrine.orm.entity_manager')->flush();

        $client->request('GET', sprintf('/syn/%s', $code));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.card');
        $this->assertSelectorTextContains('.card-header', 'Synthesis Test Campaign');
    }

    public function testSynthesisInvalidCode()
    {
        $client = static::createClient();

        $client->request('GET', '/syn/invalidcode123');

        $this->assertResponseStatusCodeSame(404);
    }
}
