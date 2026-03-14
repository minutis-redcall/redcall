<?php

namespace App\Tests\Entity;

use App\Entity\Volunteer;
use App\Entity\VolunteerList;
use PHPUnit\Framework\TestCase;

class VolunteerListTest extends TestCase
{
    public function testAddVolunteer(): void
    {
        $list = new VolunteerList();
        $volunteer = new Volunteer();

        $result = $list->addVolunteer($volunteer);

        $this->assertSame($list, $result);
        $this->assertCount(1, $list->getVolunteers());
        $this->assertTrue($list->hasVolunteer($volunteer));
    }

    public function testAddVolunteerDoesNotDuplicate(): void
    {
        $list = new VolunteerList();
        $volunteer = new Volunteer();

        $list->addVolunteer($volunteer);
        $list->addVolunteer($volunteer);

        $this->assertCount(1, $list->getVolunteers());
    }

    public function testAddMultipleVolunteers(): void
    {
        $list = new VolunteerList();
        $v1 = new Volunteer();
        $v2 = new Volunteer();
        $v3 = new Volunteer();

        $list->addVolunteer($v1);
        $list->addVolunteer($v2);
        $list->addVolunteer($v3);

        $this->assertCount(3, $list->getVolunteers());
        $this->assertTrue($list->hasVolunteer($v1));
        $this->assertTrue($list->hasVolunteer($v2));
        $this->assertTrue($list->hasVolunteer($v3));
    }

    public function testRemoveVolunteer(): void
    {
        $list = new VolunteerList();
        $volunteer = new Volunteer();

        $list->addVolunteer($volunteer);
        $list->removeVolunteer($volunteer);

        $this->assertCount(0, $list->getVolunteers());
        $this->assertFalse($list->hasVolunteer($volunteer));
    }

    public function testRemoveVolunteerThatDoesNotExistIsNoOp(): void
    {
        $list = new VolunteerList();
        $volunteer = new Volunteer();

        $result = $list->removeVolunteer($volunteer);

        $this->assertSame($list, $result);
        $this->assertCount(0, $list->getVolunteers());
    }
}
