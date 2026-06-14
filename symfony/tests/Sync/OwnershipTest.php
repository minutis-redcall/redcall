<?php

namespace App\Tests\Sync;

use App\Entity\Structure;
use App\Entity\Volunteer;
use App\Sync\Ownership;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class OwnershipTest extends TestCase
{
    #[DataProvider('volunteerCases')]
    public function testIsPegassVolunteer(?string $externalId, bool $pegass, bool $annuaire) : void
    {
        $this->assertSame($pegass, Ownership::isPegassVolunteer($externalId));
        $this->assertSame($annuaire, Ownership::isAnnuaireVolunteer($externalId));
    }

    public static function volunteerCases() : array
    {
        return [
            'plain NIVOL'                       => ['1106289L', true, false],
            'padded NIVOL'                      => ['00001106289L', true, false],
            'user-annu- prefix (UserExtract)'   => ['user-annu-philippe-testa-croix-rouge-fr', false, true],
            'annuaire- prefix (VolunteerExt.)'  => ['annuaire-53114b21', false, true],
            'deleted- prefix'                   => ['deleted-Jj6BSBDEPpgXQqz0', false, false],
            'null'                              => [null, false, false],
            'empty'                             => ['', false, false],
        ];
    }

    #[DataProvider('structureCases')]
    public function testIsPegassStructure(?string $externalId, bool $pegass) : void
    {
        $this->assertSame($pegass, Ownership::isPegassStructure($externalId));
    }

    public static function structureCases() : array
    {
        return [
            'numeric Pegass id'           => ['558', true],
            'longer numeric id'           => ['4424', true],
            'UUID (ANNUAIRE NATIONAL)'    => ['792319a4-2e05-4509-bc61-a407d4b70e23', false],
            'UUID (demo)'                 => ['e52fa885-0d28-4f4b-83ae-309f33900be1', false],
            'null'                        => [null, false],
            'empty'                       => ['', false],
            'something with a letter'     => ['558x', false],
        ];
    }

    public function testEntityWrappers() : void
    {
        $pegass = (new Volunteer())->setExternalId('1106289L');
        $annu   = (new Volunteer())->setExternalId('user-annu-foo');
        $this->assertTrue(Ownership::isPegassVolunteerEntity($pegass));
        $this->assertFalse(Ownership::isPegassVolunteerEntity($annu));

        $sPegass = (new Structure())->setExternalId('558');
        $sAnnu   = (new Structure())->setExternalId('792319a4-2e05-4509-bc61-a407d4b70e23');
        $this->assertTrue(Ownership::isPegassStructureEntity($sPegass));
        $this->assertFalse(Ownership::isPegassStructureEntity($sAnnu));
    }
}
