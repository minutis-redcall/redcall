<?php

namespace App\Tests\Sync\Dto;

use App\Sync\Dto\ActionRow;
use App\Sync\Dto\NominationRow;
use App\Sync\Dto\SkillRow;
use App\Sync\Dto\TrainingRow;
use App\Sync\Dto\VolunteerRow;
use PHPUnit\Framework\TestCase;

class VolunteerRowTest extends TestCase
{
    public function testIsMinorIsTrueWhenAgeUnder18()
    {
        $minor = new VolunteerRow(
            nivol: '00000000001A',
            lastName: 'DUPONT',
            firstName: 'Jean',
            age: 16,
            personalEmail: '',
            organizationEmail: '',
            phone: '',
            structureId: '980'
        );
        $adult = new VolunteerRow(
            nivol: '00000000002B',
            lastName: 'MARTIN',
            firstName: 'Marie',
            age: 30,
            personalEmail: '',
            organizationEmail: '',
            phone: '',
            structureId: '980'
        );

        $this->assertTrue($minor->isMinor());
        $this->assertFalse($adult->isMinor());
    }

    public function testIsMinorIsFalseWhenAgeUnknown()
    {
        $unknown = new VolunteerRow(
            nivol: '00000000001A',
            lastName: 'DUPONT',
            firstName: 'Jean',
            age: 0,
            personalEmail: '',
            organizationEmail: '',
            phone: '',
            structureId: '980'
        );

        $this->assertFalse($unknown->isMinor());
    }

    public function testToArrayFromArrayRoundtrip()
    {
        $original = new VolunteerRow(
            nivol: '01100999999X',
            lastName: 'DUPONT',
            firstName: 'Jean',
            age: 45,
            personalEmail: 'jean.dupont@example.test',
            organizationEmail: 'jean.dupont@example.org',
            phone: '+33600000001',
            structureId: '980',
            actions: [
                new ActionRow('980', '1', 'Urgence et Secourisme'),
                new ActionRow('980', '17', 'Formation'),
            ],
            trainings: [
                new TrainingRow(
                    formationId: '167',
                    code: 'PSE2',
                    label: 'PREMIERS SECOURS EN EQUIPE DE NIVEAU 2',
                    gotAt: new \DateTimeImmutable('2022-10-26T14:09:22'),
                    expiresAt: new \DateTimeImmutable('2027-12-31T14:09:22')
                ),
            ],
            skills: [new SkillRow('10', 'Chauffeur VPSP')],
            nominations: [
                new NominationRow(
                    nominationId: '533',
                    code: 'RTIN',
                    label: 'Referent Inclusion Numerique',
                    structureId: '980',
                    gotAt: new \DateTimeImmutable('2023-01-01')
                ),
            ]
        );

        $restored = VolunteerRow::fromArray($original->toArray());

        $this->assertEquals($original, $restored);
    }

    public function testFromArrayWithEmptyNestedDataIsSafe()
    {
        $row = VolunteerRow::fromArray([
            'nivol'             => '01',
            'lastName'          => 'X',
            'firstName'         => 'Y',
            'age'               => 25,
            'personalEmail'     => '',
            'organizationEmail' => '',
            'phone'             => '',
            'structureId'       => '',
        ]);

        $this->assertSame([], $row->actions);
        $this->assertSame([], $row->trainings);
        $this->assertSame([], $row->skills);
        $this->assertSame([], $row->nominations);
    }
}
