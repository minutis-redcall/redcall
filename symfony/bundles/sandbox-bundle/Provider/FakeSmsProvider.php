<?php

namespace Bundles\SandboxBundle\Provider;

use App\Entity\Phone;
use App\Provider\SMS\SMSProvider;
use App\Tools\Random;
use Bundles\SandboxBundle\Entity\FakeSms;
use Doctrine\Bundle\DoctrineBundle\Registry;
use LogicException;

class FakeSmsProvider implements SMSProvider
{
    private $phoneRepository;
    private $fakeSmsRepository;

    public function __construct(Registry $registry)
    {
        $this->phoneRepository   = $registry->getRepository(Phone::class);
        $this->fakeSmsRepository = $registry->getRepository(FakeSms::class);
    }

    public function send(string $from, string $to, string $message, array $context = []) : ?string
    {
        $phone = $this->phoneRepository->findOneByPhoneNumber($to);
        if (!$phone) {
            throw new LogicException('Cannot send fake SMS to unknown volunteer.');
        }

        $volunteer = $phone->getVolunteer();
        $this->fakeSmsRepository->save($volunteer, $message, FakeSms::DIRECTION_RECEIVED);

        return sprintf('FAKE-%s', Random::generate(15));
    }

    public function getProviderCode() : string
    {
        return 'fake';
    }
}