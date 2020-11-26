<?php

namespace App\Manager;

use App\Repository\PhoneRepository;

class PhoneManager
{
    /**
     * @var PhoneRepository
     */
    private $phoneRepository;

    public function __construct(PhoneRepository $phoneRepository)
    {
        $this->phoneRepository = $phoneRepository;
    }

    public function findOneByPhoneNumber(string $phoneNumber)
    {
        return $this->phoneRepository->findOneByE164($phoneNumber);
    }

}