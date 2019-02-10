<?php

namespace Bundles\SandboxBundle\SMS;

use App\Entity\Volunteer;
use App\Services\Random;
use App\SMS\SMSProvider;
use App\SMS\SMSSent;
use Bundles\SandboxBundle\Entity\FakeSms;
use Doctrine\Bundle\DoctrineBundle\Registry;

class Fake implements SMSProvider
{
    private $volunteerRepository;
    private $fakeSmsRepository;

    public function __construct(Registry $registry)
    {
        $this->volunteerRepository = $registry->getRepository(Volunteer::class);
        $this->fakeSmsRepository   = $registry->getRepository(FakeSms::class);
    }

    public function send(string $message, string $phoneNumber): SMSSent
    {
        $volunteer = $this->volunteerRepository->findOneByPhoneNumber($phoneNumber);

        if (!$volunteer) {
            throw new \LogicException('Cannot send fake SMS to unknown volunteer.');
        }

        $this->fakeSmsRepository->save($volunteer, $message, FakeSms::DIRECTION_RECEIVED);

        return new SMSSent(sprintf('FAKE-%s', Random::generate(15)), 0.0);
    }

    public function getProviderCode(): string
    {
        return 'fake';
    }
}