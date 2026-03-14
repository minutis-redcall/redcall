<?php

namespace App\Tests\Manager;

use App\Entity\Phone;
use App\Entity\Volunteer;
use App\Manager\PhoneConfigManager;
use App\Model\PhoneConfig;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PhoneConfigManagerTest extends KernelTestCase
{
    /** @var PhoneConfigManager */
    private $phoneConfigManager;

    protected function setUp() : void
    {
        self::bootKernel();

        $this->phoneConfigManager = self::$container->get(PhoneConfigManager::class);
    }

    protected function tearDown() : void
    {
        // Restore context after tests that may change timezone
        date_default_timezone_set('Europe/Paris');
        parent::tearDown();
    }

    public function testGetPhoneConfigForFrance()
    {
        $config = $this->phoneConfigManager->getPhoneConfig('fr');

        $this->assertNotNull($config);
        $this->assertInstanceOf(PhoneConfig::class, $config);
        $this->assertSame('Europe/Paris', $config->getTimezone());
        $this->assertTrue($config->isOutboundCallEnabled());
        $this->assertTrue($config->isOutboundSmsEnabled());
        $this->assertTrue($config->isInboundCallEnabled());
        $this->assertTrue($config->isInboundSmsEnabled());
    }

    public function testGetPhoneConfigForBelgium()
    {
        $config = $this->phoneConfigManager->getPhoneConfig('be');

        $this->assertNotNull($config);
        $this->assertSame('Europe/Brussels', $config->getTimezone());
        $this->assertTrue($config->isOutboundCallEnabled());
        $this->assertTrue($config->isOutboundSmsEnabled());
    }

    public function testGetPhoneConfigCaseInsensitive()
    {
        $configLower = $this->phoneConfigManager->getPhoneConfig('fr');
        $configUpper = $this->phoneConfigManager->getPhoneConfig('FR');

        $this->assertNotNull($configLower);
        $this->assertNotNull($configUpper);
        $this->assertSame($configLower->getTimezone(), $configUpper->getTimezone());
    }

    public function testGetPhoneConfigForUnknownCountry()
    {
        $config = $this->phoneConfigManager->getPhoneConfig('xx');

        $this->assertNull($config);
    }

    public function testGetPhoneConfigForVolunteerWithPhone()
    {
        $volunteer = new Volunteer();
        $phone = new Phone();
        $phone->setE164('+33612345678');
        $phone->setPreferred(true);
        $phone->setCountryCode('FR');
        $phone->setPrefix(33);
        $phone->setNational('06 12 34 56 78');
        $phone->setInternational('+33 6 12 34 56 78');
        $volunteer->addPhone($phone);

        $config = $this->phoneConfigManager->getPhoneConfigForVolunteer($volunteer);

        $this->assertNotNull($config);
        $this->assertSame('Europe/Paris', $config->getTimezone());
    }

    public function testGetPhoneConfigForVolunteerWithoutPhone()
    {
        $volunteer = new Volunteer();

        $config = $this->phoneConfigManager->getPhoneConfigForVolunteer($volunteer);

        $this->assertNull($config);
    }

    public function testGetPhoneConfigForVolunteerWithPhoneNoCountryCode()
    {
        $volunteer = new Volunteer();
        $phone = new Phone();
        $phone->setE164('+33612345678');
        $phone->setPreferred(true);
        $phone->setCountryCode('');
        $phone->setPrefix(33);
        $phone->setNational('06 12 34 56 78');
        $phone->setInternational('+33 6 12 34 56 78');
        $volunteer->addPhone($phone);

        $config = $this->phoneConfigManager->getPhoneConfigForVolunteer($volunteer);

        $this->assertNull($config);
    }

    public function testIsSMSTransmittableWithFrenchPhone()
    {
        $volunteer = new Volunteer();
        $phone = new Phone();
        $phone->setE164('+33612345678');
        $phone->setPreferred(true);
        $phone->setCountryCode('FR');
        $phone->setPrefix(33);
        $phone->setNational('06 12 34 56 78');
        $phone->setInternational('+33 6 12 34 56 78');
        $volunteer->addPhone($phone);

        $this->assertTrue($this->phoneConfigManager->isSMSTransmittable($volunteer));
    }

    public function testIsSMSTransmittableWithoutPhone()
    {
        $volunteer = new Volunteer();

        $this->assertFalse($this->phoneConfigManager->isSMSTransmittable($volunteer));
    }

    public function testIsVoiceCallTransmittableWithFrenchPhone()
    {
        $volunteer = new Volunteer();
        $phone = new Phone();
        $phone->setE164('+33612345678');
        $phone->setPreferred(true);
        $phone->setCountryCode('FR');
        $phone->setPrefix(33);
        $phone->setNational('06 12 34 56 78');
        $phone->setInternational('+33 6 12 34 56 78');
        $volunteer->addPhone($phone);

        $this->assertTrue($this->phoneConfigManager->isVoiceCallTransmittable($volunteer));
    }

    public function testIsVoiceCallTransmittableWithoutPhone()
    {
        $volunteer = new Volunteer();

        $this->assertFalse($this->phoneConfigManager->isVoiceCallTransmittable($volunteer));
    }

    public function testIsVoiceCallTransmittableDisabledCountry()
    {
        // Saint-Barthelemy (bl) has outbound_call_enabled: false
        $volunteer = new Volunteer();
        $phone = new Phone();
        $phone->setE164('+590123456');
        $phone->setPreferred(true);
        $phone->setCountryCode('BL');
        $phone->setPrefix(590);
        $phone->setNational('123456');
        $phone->setInternational('+590 123456');
        $volunteer->addPhone($phone);

        $this->assertFalse($this->phoneConfigManager->isVoiceCallTransmittable($volunteer));
    }

    public function testRestoreContext()
    {
        date_default_timezone_set('UTC');

        $this->phoneConfigManager->restoreContext();

        $this->assertSame('Europe/Paris', date_default_timezone_get());
    }

    public function testApplyContext()
    {
        $config = $this->phoneConfigManager->getPhoneConfig('gp');
        $this->assertNotNull($config);

        $this->phoneConfigManager->applyContext($config);

        $this->assertSame('America/Guadeloupe', date_default_timezone_get());

        // Restore
        $this->phoneConfigManager->restoreContext();
    }
}
