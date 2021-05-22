<?php

namespace Bundles\SandboxBundle\Manager;

use Bundles\SandboxBundle\Entity\FakeEmail;
use Bundles\SandboxBundle\Repository\FakeEmailRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;

class FakeEmailManager
{
    /**
     * @var FakeEmailRepository
     */
    private $fakeEmailRepository;

    /**
     * @param FakeEmailRepository $fakeEmailRepository
     */
    public function __construct(FakeEmailRepository $fakeEmailRepository)
    {
        $this->fakeEmailRepository = $fakeEmailRepository;
    }

    /**
     * @param string $to
     * @param string $subject
     * @param string $body
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function store(string $to, string $subject, string $body)
    {
        $this->fakeEmailRepository->store($to, $subject, $body);
    }

    /**
     * @return array
     */
    public function findAllEmails() : array
    {
        return $this->fakeEmailRepository->findAllEmails();
    }

    /**
     * @param string $phoneNumber
     *
     * @return FakeEmail[]
     */
    public function findMessagesForEmail(string $email) : array
    {
        return $this->fakeEmailRepository->findMessagesForEmail($email);
    }

    public function truncate()
    {
        $this->fakeEmailRepository->truncate();
    }
}