<?php

namespace Bundles\SandboxBundle\Manager;

use App\Entity\Volunteer;
use Bundles\SandboxBundle\Entity\FakeSms;
use Bundles\SandboxBundle\Repository\FakeSmsRepository;

class FakeSmsManager
{
    /**
     * @var FakeSmsRepository
     */
    private $fakeSmsRepository;

    /**
     * @param FakeSmsRepository $fakeSmsRepository
     */
    public function __construct(FakeSmsRepository $fakeSmsRepository)
    {
        $this->fakeSmsRepository = $fakeSmsRepository;
    }

    /**
     * @return array
     */
    public function findAllPhones(): array
    {
        return $this->fakeSmsRepository->findAllPhones();
    }

    public function truncate()
    {
        return $this->fakeSmsRepository->truncate();
    }

    /**
     * @param string $phoneNumber
     *
     * @return FakeSms[]
     */
    public function findMessagesForPhoneNumber(string $phoneNumber): array
    {
        return $this->fakeSmsRepository->findMessagesForPhoneNumber($phoneNumber);
    }

    /**
     * @param Volunteer $volunteer
     * @param string    $content
     * @param string    $direction
     */
    public function save(Volunteer $volunteer, string $content, string $direction)
    {
        $this->fakeSmsRepository->save($volunteer, $content, $direction);
    }

    /**
     * @param $phoneNumber
     * @param $lastMessageId
     *
     * @return array
     */
    public function findMessagesHavingIdGreaterThan($phoneNumber, $lastMessageId): array
    {
        return $this->fakeSmsRepository->findMessagesHavingIdGreaterThan($phoneNumber, $lastMessageId);
    }
}