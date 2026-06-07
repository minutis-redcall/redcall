<?php

namespace App\Tests\Sync\Importer;

use App\Entity\Phone;
use App\Entity\Volunteer;
use App\Manager\VolunteerManager;
use App\Sync\Dto\ActionRow;
use App\Sync\Dto\NominationRow;
use App\Sync\Dto\SkillRow;
use App\Sync\Dto\TrainingRow;
use App\Sync\Dto\VolunteerRow;
use App\Sync\Importer\VolunteerImporter;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class VolunteerImporterTest extends KernelTestCase
{
    private VolunteerImporter $importer;
    private VolunteerManager $volunteerManager;
    private \Doctrine\ORM\EntityManagerInterface $em;
    private DataFixtures $fixtures;

    protected function setUp() : void
    {
        self::bootKernel();

        $this->importer         = self::getContainer()->get(VolunteerImporter::class);
        $this->volunteerManager = self::getContainer()->get(VolunteerManager::class);
        $this->em               = self::getContainer()->get('doctrine.orm.entity_manager');
        $this->fixtures         = new DataFixtures(
            $this->em,
            self::getContainer()->get('security.password_hasher')
        );
    }

    private function row(array $overrides = []) : VolunteerRow
    {
        $defaults = [
            'nivol'             => '01100999999X',
            'lastName'          => 'DUPONT',
            'firstName'         => 'Jean',
            'age'               => 45,
            'personalEmail'     => 'jean.dupont@example.test',
            'organizationEmail' => 'jean.dupont@example.org',
            'phone'             => '+33600000001',
            'structureId'       => '980',
            'actions'           => [],
            'trainings'         => [],
            'skills'            => [],
            'nominations'       => [],
        ];

        return VolunteerRow::fromArray(array_merge($defaults, $overrides));
    }

    public function testCreatesVolunteerWithBasicFields()
    {
        $this->fixtures->createStructure('UL 980', '980');

        $this->importer->import($this->row());
        $this->em->clear();

        $volunteer = $this->volunteerManager->findOneByExternalId('1100999999X');
        $this->assertNotNull($volunteer);
        $this->assertSame('Jean', $volunteer->getFirstName());
        $this->assertSame('Dupont', $volunteer->getLastName());
        $this->assertSame('jean.dupont@example.test', $volunteer->getEmail());
        $this->assertSame('jean.dupont@example.org', $volunteer->getInternalEmail());
        $this->assertTrue($volunteer->isEnabled());
        $this->assertFalse($volunteer->isMinor());
    }

    public function testPse2TrainingBadgeIsSaved()
    {
        // The regression that triggered this whole rewrite
        $this->fixtures->createStructure('UL 980', '980');

        $row = $this->row([
            'trainings' => [
                [
                    'formationId' => '167',
                    'code'        => 'PSE2',
                    'label'       => 'PREMIERS SECOURS EN EQUIPE DE NIVEAU 2',
                    'gotAt'       => '2022-10-26T14:09:22+00:00',
                    'expiresAt'   => '2027-12-31T14:09:22+00:00',
                ],
            ],
        ]);

        $this->importer->import($row);
        $this->em->clear();

        $volunteer = $this->volunteerManager->findOneByExternalId('1100999999X');
        $this->assertNotNull($volunteer);

        $byExternalId = [];
        foreach ($volunteer->getBadges(false) as $badge) {
            $byExternalId[$badge->getExternalId()] = $badge;
        }

        $this->assertArrayHasKey('training-167', $byExternalId);
        $pse2 = $byExternalId['training-167'];
        $this->assertSame('PSE2', $pse2->getName());
        $this->assertSame('2027-12-31', $pse2->getExpiresAt()->format('Y-m-d'));
    }

    public function testExpiredTrainingIsSkipped()
    {
        $this->fixtures->createStructure('UL 980', '980');

        $row = $this->row([
            'trainings' => [
                [
                    'formationId' => '999',
                    'code'        => 'OLD',
                    'label'       => 'OLD CERT',
                    'gotAt'       => '2010-01-01T00:00:00+00:00',
                    'expiresAt'   => '2020-01-01T00:00:00+00:00',
                ],
            ],
        ]);

        $this->importer->import($row);
        $this->em->clear();

        $volunteer = $this->volunteerManager->findOneByExternalId('1100999999X');

        $externalIds = [];
        foreach ($volunteer->getBadges(false) as $badge) {
            $externalIds[] = $badge->getExternalId();
        }

        $this->assertNotContains('training-999', $externalIds);
    }

    public function testLockedVolunteerKeepsContactOverrides()
    {
        $this->fixtures->createStructure('UL 980', '980');

        // Pre-create a locked volunteer with manual overrides
        $volunteer = $this->fixtures->createStandaloneVolunteer('1100999999X', 'manual@override.test');
        $volunteer->setLocked(true);
        $volunteer->setEmailLocked(true);
        $this->em->persist($volunteer);
        $this->em->flush();

        $row = $this->row(['personalEmail' => 'should-not-overwrite@csv.test']);
        $this->importer->import($row);
        $this->em->clear();

        $reloaded = $this->volunteerManager->findOneByExternalId('1100999999X');
        $this->assertSame('manual@override.test', $reloaded->getEmail(), 'Locked volunteer email must not be touched by sync');
    }

    public function testEmailFallbackToOrganizationWhenNoPersonal()
    {
        $this->fixtures->createStructure('UL 980', '980');

        $this->importer->import($this->row([
            'personalEmail'     => '',
            'organizationEmail' => 'work@example.org',
        ]));
        $this->em->clear();

        $volunteer = $this->volunteerManager->findOneByExternalId('1100999999X');
        $this->assertSame('work@example.org', $volunteer->getEmail());
        $this->assertSame('work@example.org', $volunteer->getInternalEmail());
    }

    public function testMinorFlagFollowsAge()
    {
        $this->fixtures->createStructure('UL 980', '980');

        $this->importer->import($this->row(['age' => 16]));
        $this->em->clear();

        $volunteer = $this->volunteerManager->findOneByExternalId('1100999999X');
        $this->assertTrue($volunteer->isMinor());
    }

    public function testEmptyFirstNameOrLastNameSkipsImport()
    {
        $this->fixtures->createStructure('UL 980', '980');

        $this->importer->import($this->row(['firstName' => '']));
        $this->em->clear();

        $this->assertNull($this->volunteerManager->findOneByExternalId('1100999999X'));
    }

    public function testStructuresAreSyncedFromCsv()
    {
        $primary = $this->fixtures->createStructure('UL 980', '980');
        $other   = $this->fixtures->createStructure('UL 4242', '4242');

        $row = $this->row([
            'actions' => [
                ['structureId' => '4242', 'groupActionId' => '1', 'groupActionLabel' => 'Urgence et Secourisme'],
            ],
        ]);
        $this->importer->import($row);
        $this->em->clear();

        $volunteer = $this->volunteerManager->findOneByExternalId('1100999999X');
        $externalIds = array_map(fn ($s) => $s->getExternalId(), $volunteer->getStructures(false)->toArray());

        $this->assertContains('980', $externalIds);
        $this->assertContains('4242', $externalIds);
    }

    public function testPhoneIsNormalizedToE164()
    {
        $this->fixtures->createStructure('UL 980', '980');

        $this->importer->import($this->row(['phone' => '+330600000001']));
        $this->em->clear();

        $volunteer = $this->volunteerManager->findOneByExternalId('1100999999X');
        $this->assertSame('+33600000001', $volunteer->getPhoneNumber());
    }

    public function testPhoneLockedIsRespected()
    {
        $this->fixtures->createStructure('UL 980', '980');

        $volunteer = $this->fixtures->createStandaloneVolunteer('1100999999X', 'old@example.test');
        $volunteer->setLastName('Dupont');
        $volunteer->setFirstName('Jean');
        $phone = new Phone();
        $phone->setPreferred(true);
        $phone->setE164('+33611111111');
        $volunteer->addPhone($phone);
        $volunteer->setPhoneNumberLocked(true);
        $this->em->persist($volunteer);
        $this->em->flush();

        $this->importer->import($this->row(['phone' => '+33699999999']));
        $this->em->clear();

        $reloaded = $this->volunteerManager->findOneByExternalId('1100999999X');
        $this->assertSame('+33611111111', $reloaded->getPhoneNumber(), 'Locked phone must not be overwritten');
    }

    public function testGroupActionBadgesAreCreated()
    {
        $this->fixtures->createStructure('UL 980', '980');

        $row = $this->row([
            'actions' => [
                ['structureId' => '980', 'groupActionId' => '1', 'groupActionLabel' => 'Urgence et Secourisme'],
                ['structureId' => '980', 'groupActionId' => '17', 'groupActionLabel' => 'Formation'],
            ],
        ]);
        $this->importer->import($row);
        $this->em->clear();

        $volunteer    = $this->volunteerManager->findOneByExternalId('1100999999X');
        $externalIds = array_map(fn ($b) => $b->getExternalId(), $volunteer->getBadges(false)->toArray());

        $this->assertContains('groupeAction-1', $externalIds);
        $this->assertContains('groupeAction-17', $externalIds);
    }
}
