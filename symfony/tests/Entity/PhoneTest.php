<?php

namespace App\Tests\Entity;

use App\Entity\Phone;
use App\Entity\Volunteer;
use PHPUnit\Framework\TestCase;

class PhoneTest extends TestCase
{
    public function testAddVolunteer(): void
    {
        $phone = new Phone();
        $volunteer = new Volunteer();

        $result = $phone->addVolunteer($volunteer);

        $this->assertSame($phone, $result);
        $this->assertCount(1, $phone->getVolunteers());
        $this->assertTrue($phone->getVolunteers()->contains($volunteer));
    }

    public function testAddVolunteerDoesNotDuplicate(): void
    {
        $phone = new Phone();
        $volunteer = new Volunteer();

        $phone->addVolunteer($volunteer);
        $phone->addVolunteer($volunteer);

        $this->assertCount(1, $phone->getVolunteers());
    }

    public function testAddMultipleVolunteers(): void
    {
        $phone = new Phone();
        $v1 = new Volunteer();
        $v2 = new Volunteer();

        $phone->addVolunteer($v1);
        $phone->addVolunteer($v2);

        $this->assertCount(2, $phone->getVolunteers());
    }

    public function testRemoveVolunteer(): void
    {
        $phone = new Phone();
        $volunteer = new Volunteer();

        $phone->addVolunteer($volunteer);
        $result = $phone->removeVolunteer($volunteer);

        $this->assertSame($phone, $result);
        $this->assertCount(0, $phone->getVolunteers());
    }

    public function testRemoveVolunteerThatDoesNotExistIsNoOp(): void
    {
        $phone = new Phone();
        $volunteer = new Volunteer();

        $result = $phone->removeVolunteer($volunteer);

        $this->assertSame($phone, $result);
        $this->assertCount(0, $phone->getVolunteers());
    }

    public function testGetHiddenReturnsNullWhenNoNational(): void
    {
        $phone = new Phone();

        $this->assertNull($phone->getHidden());
    }

    public function testGetHiddenMasksMiddleDigits(): void
    {
        $phone = new Phone();
        $phone->setNational('06 12 34 56 78');

        $hidden = $phone->getHidden();

        // national = "06 12 34 56 78" (14 chars)
        // first 4: "06 1", last 4: "6 78", middle 6: "******"
        $this->assertSame('06 1******6 78', $hidden);
        $this->assertSame(strlen('06 12 34 56 78'), strlen($hidden));
    }

    public function testGetHiddenWithShortNumber(): void
    {
        $phone = new Phone();
        // Exactly 8 characters: first 4 + 0 stars + last 4
        $phone->setNational('12345678');

        $hidden = $phone->getHidden();

        $this->assertSame('12345678', $hidden);
    }

    public function testPopulateFromE164WithFrenchMobileNumber(): void
    {
        $phone = new Phone();
        $phone->setE164('+33612345678');

        $phone->populateFromE164();

        $this->assertSame('FR', $phone->getCountryCode());
        $this->assertSame(33, $phone->getPrefix());
        $this->assertNotEmpty($phone->getNational());
        $this->assertNotEmpty($phone->getInternational());
        $this->assertTrue($phone->isMobile());
    }

    public function testPopulateFromE164WithFrenchLandlineNumber(): void
    {
        $phone = new Phone();
        $phone->setE164('+33144001234');

        $phone->populateFromE164();

        $this->assertSame('FR', $phone->getCountryCode());
        $this->assertSame(33, $phone->getPrefix());
        $this->assertFalse($phone->isMobile());
    }

    public function testPopulateFromE164DoesNothingWhenNoE164(): void
    {
        $phone = new Phone();

        $phone->populateFromE164();

        $this->assertNull($phone->getCountryCode());
        $this->assertNull($phone->getPrefix());
        $this->assertNull($phone->getNational());
    }

    public function testPopulateFromE164WithInternationalNumber(): void
    {
        $phone = new Phone();
        $phone->setE164('+447911123456');

        $phone->populateFromE164();

        $this->assertSame('GB', $phone->getCountryCode());
        $this->assertSame(44, $phone->getPrefix());
        $this->assertTrue($phone->isMobile());
    }
}
