<?php

namespace App\Tests\Manager;

use App\Manager\NivolManager;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class NivolManagerTest extends KernelTestCase
{
    private NivolManager $manager;
    private DataFixtures $fixtures;

    protected function setUp() : void
    {
        self::bootKernel();

        $container      = static::getContainer();
        $this->manager  = $container->get(NivolManager::class);
        $this->fixtures = new DataFixtures(
            $container->get('doctrine.orm.entity_manager'),
            $container->get('security.password_encoder')
        );
    }

    public function testGetUserByNivolReturnsUserForValidVolunteer()
    {
        // getUserByNivol strips leading zeros before lookup, so volunteer external ID
        // must be stored without leading zeros for the match to succeed.
        $user      = $this->fixtures->createRawUser('nivol_test@example.com');
        $volunteer = $this->fixtures->createVolunteer($user, '123456');

        $found = $this->manager->getUserByNivol('123456');

        $this->assertNotNull($found);
        $this->assertSame($user->getId(), $found->getId());
    }

    public function testGetUserByNivolStripsLeadingZeros()
    {
        $user      = $this->fixtures->createRawUser('nivol_zero@example.com');
        $volunteer = $this->fixtures->createVolunteer($user, '789012');

        // Searching with leading zeros should strip them, matching '789012'
        $found = $this->manager->getUserByNivol('000789012');

        $this->assertNotNull($found);
        $this->assertSame($user->getId(), $found->getId());
    }

    public function testGetUserByNivolReturnsNullForNonExistentNivol()
    {
        $found = $this->manager->getUserByNivol('999999999');

        $this->assertNull($found);
    }

    public function testGetUserByNivolReturnsNullForDisabledVolunteer()
    {
        $user      = $this->fixtures->createRawUser('nivol_disabled@example.com');
        $volunteer = $this->fixtures->createVolunteer($user, 'DISABLED001');

        $volunteer->setEnabled(false);
        $em = $this->fixtures->getEntityManager();
        $em->persist($volunteer);
        $em->flush();

        $found = $this->manager->getUserByNivol('DISABLED001');

        $this->assertNull($found);
    }

    public function testGetUserByNivolReturnsNullForVolunteerWithoutUser()
    {
        $volunteer = $this->fixtures->createStandaloneVolunteer('STANDALONE001');

        $found = $this->manager->getUserByNivol('STANDALONE001');

        $this->assertNull($found);
    }

    public function testSendEmailReturnsIdentifierForValidNivol()
    {
        $container = static::getContainer();

        $user      = $this->fixtures->createRawUser('nivol_email@example.com');
        $volunteer = $this->fixtures->createVolunteer($user, 'EMAIL001');

        // Push a request so that getMainRequest()->getLocale() works
        $requestStack = $container->get('request_stack');
        $request      = Request::create('/');
        $request->setLocale('fr');
        $requestStack->push($request);

        $identifier = $this->manager->sendEmail('EMAIL001');

        $this->assertNotNull($identifier);
        $this->assertIsString($identifier);
    }

    public function testSendEmailReturnsNullForInvalidNivol()
    {
        $identifier = $this->manager->sendEmail('NONEXISTENT999');

        $this->assertNull($identifier);
    }
}
