<?php

namespace App\Tests\Repository;

use App\Entity\Phone;
use App\Repository\PhoneRepository;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PhoneRepositoryTest extends KernelTestCase
{
    /** @var PhoneRepository */
    private $repository;

    /** @var DataFixtures */
    private $fixtures;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->repository = self::$container->get('doctrine.orm.entity_manager')
            ->getRepository(Phone::class);

        $this->fixtures = new DataFixtures(
            self::$container->get('doctrine.orm.entity_manager'),
            self::$container->get('security.password_encoder')
        );
    }

    private function createPhoneForVolunteer(string $e164, string $volExtId, bool $preferred = true, bool $mobile = true): Phone
    {
        $volunteer = $this->fixtures->createStandaloneVolunteer($volExtId, $volExtId . '@test.com');

        $phone = new Phone();
        $phone->setE164($e164);
        $phone->setNational(str_replace('+33', '0', $e164));
        $phone->setInternational($e164);
        $phone->setPreferred($preferred);
        $phone->setMobile($mobile);
        $phone->setCountryCode('FR');
        $phone->addVolunteer($volunteer);

        $em = self::$container->get('doctrine.orm.entity_manager');
        $em->persist($phone);
        $em->persist($volunteer);
        $em->flush();

        return $phone;
    }

    // ── findOneByPhoneNumber ──

    public function testFindOneByPhoneNumber(): void
    {
        $this->createPhoneForVolunteer('+33612345001', 'PHONE-001');

        $found = $this->repository->findOneByPhoneNumber('+33612345001');
        $this->assertNotNull($found);
        $this->assertSame('+33612345001', $found->getE164());
    }

    public function testFindOneByPhoneNumberReturnsNullForNonexistent(): void
    {
        $this->assertNull($this->repository->findOneByPhoneNumber('+33999999999'));
    }

    public function testFindOneByPhoneNumberRequiresPreferred(): void
    {
        $this->createPhoneForVolunteer('+33612345002', 'PHONE-002', false);

        $found = $this->repository->findOneByPhoneNumber('+33612345002');
        $this->assertNull($found);
    }

    public function testFindOneByPhoneNumberRequiresEnabledVolunteer(): void
    {
        $volunteer = $this->fixtures->createStandaloneVolunteer('PHONE-003', 'phone003@test.com');
        $volunteer->setEnabled(false);

        $phone = new Phone();
        $phone->setE164('+33612345003');
        $phone->setNational('0612345003');
        $phone->setInternational('+33612345003');
        $phone->setPreferred(true);
        $phone->setMobile(true);
        $phone->setCountryCode('FR');
        $phone->addVolunteer($volunteer);

        $em = self::$container->get('doctrine.orm.entity_manager');
        $em->persist($volunteer);
        $em->persist($phone);
        $em->flush();

        $found = $this->repository->findOneByPhoneNumber('+33612345003');
        $this->assertNull($found);
    }

    // ── findOneByVolunteerAndE164 ──

    public function testFindOneByVolunteerAndE164(): void
    {
        $this->createPhoneForVolunteer('+33612345004', 'PHONE-004');

        $found = $this->repository->findOneByVolunteerAndE164('PHONE-004', '+33612345004');
        $this->assertNotNull($found);
        $this->assertSame('+33612345004', $found->getE164());
    }

    public function testFindOneByVolunteerAndE164ReturnsNullForWrongVolunteer(): void
    {
        $this->createPhoneForVolunteer('+33612345005', 'PHONE-005');

        $found = $this->repository->findOneByVolunteerAndE164('WRONG-VOL', '+33612345005');
        $this->assertNull($found);
    }

    public function testFindOneByVolunteerAndE164ReturnsNullForWrongNumber(): void
    {
        $this->createPhoneForVolunteer('+33612345006', 'PHONE-006');

        $found = $this->repository->findOneByVolunteerAndE164('PHONE-006', '+33600000000');
        $this->assertNull($found);
    }
}
