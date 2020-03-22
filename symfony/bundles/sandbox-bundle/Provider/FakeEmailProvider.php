<?php

namespace Bundles\SandboxBundle\Provider;

use App\Provider\Email\EmailProvider;
use Bundles\SandboxBundle\Entity\FakeEmail;
use Doctrine\Bundle\DoctrineBundle\Registry;

class FakeEmailProvider implements EmailProvider
{
    private $fakeEmailRepository;

    public function __construct(Registry $registry)
    {
        $this->fakeEmailRepository = $registry->getRepository(FakeEmail::class);
    }

    public function send(string $to, string $subject, string $textBody, string $htmlBody)
    {
        $this->fakeEmailRepository->store($to, $subject, $textBody);
    }
}