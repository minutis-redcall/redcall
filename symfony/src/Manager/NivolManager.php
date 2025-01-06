<?php

namespace App\Manager;

use App\Entity\User;
use App\Enum\Platform;
use App\Tools\Random;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class NivolManager
{
    private VolunteerManager    $volunteerManager;
    private ExpirableManager    $expirableManager;
    private MailManager         $mailManager;
    private TranslatorInterface $translator;
    private RequestStack        $requestStack;

    public function __construct(VolunteerManager $volunteerManager,
        ExpirableManager $expirableManager,
        MailManager $mailManager,
        TranslatorInterface $translator,
        RequestStack $requestStack)
    {
        $this->volunteerManager = $volunteerManager;
        $this->expirableManager = $expirableManager;
        $this->mailManager      = $mailManager;
        $this->translator       = $translator;
        $this->requestStack     = $requestStack;
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
            $this->translator->trans('nivol_auth.email.subject', ['%code%' => $code]),
            $this->translator->trans('nivol_auth.email.content', ['%code%' => $code]),
            $this->translator->trans('nivol_auth.email.content_html', ['%code%' => $code]),
            $this->requestStack->getMainRequest()->getLocale()
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