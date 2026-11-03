<?php

namespace App\Manager;

use App\Entity\User;
use App\Tools\Random;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class NivolManager
{
    // Login codes are short (~22.7 bits): keep them short-lived and cap the
    // number of guesses so they cannot be brute-forced.
    public const CODE_TTL     = 'PT10M';
    public const MAX_ATTEMPTS = 10;

    private VolunteerManager    $volunteerManager;
    private UserManager         $userManager;
    private ExpirableManager    $expirableManager;
    private MailManager         $mailManager;
    private TranslatorInterface $translator;
    private RequestStack        $requestStack;

    public function __construct(VolunteerManager $volunteerManager,
        UserManager $userManager,
        ExpirableManager $expirableManager,
        MailManager $mailManager,
        TranslatorInterface $translator,
        RequestStack $requestStack)
    {
        $this->volunteerManager = $volunteerManager;
        $this->userManager      = $userManager;
        $this->expirableManager = $expirableManager;
        $this->mailManager      = $mailManager;
        $this->translator       = $translator;
        $this->requestStack     = $requestStack;
    }

    public function getUserByNivol(string $nivol) : ?User
    {
        $externalId = ltrim($nivol, '0');
        $volunteer  = $this->volunteerManager->findOneByExternalId($externalId);

        if (null === $volunteer) {
            return null;
        }

        if (!$volunteer->isEnabled()) {
            return null;
        }

        // Seek for a RedCall user sharing that volunteer's NIVOL. The enabled
        // volunteer gate above is what makes the OTP-email destination known.
        return $this->userManager->findOneTrustedByExternalId($externalId);
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
        ], (new \DateTime())->add(new \DateInterval(self::CODE_TTL)));

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