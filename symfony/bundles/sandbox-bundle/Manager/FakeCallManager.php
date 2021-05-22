<?php

namespace Bundles\SandboxBundle\Manager;

use Bundles\SandboxBundle\Entity\FakeCall;
use Bundles\SandboxBundle\Repository\FakeCallRepository;

class FakeCallManager
{
    /**
     * @var FakeCallRepository
     */
    private $fakeCallRepository;

    /**
     * @param FakeCallRepository $fakeCallRepository
     */
    public function __construct(FakeCallRepository $fakeCallRepository)
    {
        $this->fakeCallRepository = $fakeCallRepository;
    }

    public function save(FakeCall $fakeCall)
    {
        $this->fakeCallRepository->save($fakeCall);
    }

    public function findAllPhones() : array
    {
        return $this->fakeCallRepository->findAllPhones();
    }

    public function findMessagesForPhone(string $phoneNumber) : array
    {
        return $this->fakeCallRepository->findMessagesForPhone($phoneNumber);
    }

    public function truncate()
    {
        $this->fakeCallRepository->truncate();
    }
}