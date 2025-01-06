<?php

namespace App\Manager;

use App\Entity\User;
use App\Enum\Platform;
use App\Tools\Random;

class NivolManager
{
    private VolunteerManager $volunteerManager;
    private ExpirableManager $expirableManager;
    private MailManager      $mailManager;

    public function __construct(VolunteerManager $volunteerManager,
        ExpirableManager $expirableManager,
        MailManager $mailManager)
    {
        $this->volunteerManager = $volunteerManager;
        $this->expirableManager = $expirableManager;
        $this->mailManager      = $mailManager;
    }

    public function getUserByNivol(string $nivol) : ?User
    {
        $externalId = ltrim($nivol, '0');
        $volunteer  = $this->volunteerManager->findOneByExternalId(Platform::FR, $externalId);

        if (null === $volunteer) {
            return null;
        }

        if (!$volunteer->isEnabled()) {
            return null;
        }

        // Seek for a RedCall user attached to that volunteer
        $user = $volunteer->getUser();
        if (null === $user) {
            return null;
        }

        return $user;
    }

    public function sendEmail(string $nivol) : ?string
    {
        $user = $this->getUserByNivol($nivol);
        if (!$user) {
            return null;
        }

        $code = $this->createDigits();

        $identifier = $this->expirableManager->set([
            'user_id' => $user->getId(),
            'code'    => $code,
        ]);

        $this->mailManager->simple(
            $user->getUserIdentifier(),
            sprintf('%s est votre code de connexion à RedCall', $code),
            sprintf('Utilisez le code %s pour vous connecter à RedCall.', $code),
            sprintf('<p>Utilisez le code <strong>%s</strong> pour vous connecter à RedCall.</p>', $code),
            'fr'
        );

        return $identifier;
    }

    private function createDigits() : string
    {
        return Random::upperalphabetic(1)
               .Random::numeric(2)
               .Random::upperalphabetic(1)
               .Random::numeric(2);
    }
}