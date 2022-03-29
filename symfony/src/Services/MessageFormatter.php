<?php

namespace App\Services;

use App\Entity\Communication;
use App\Entity\Message;
use App\Entity\Volunteer;
use App\Manager\LanguageConfigManager;
use App\Manager\PhoneConfigManager;
use App\Manager\PlatformConfigManager;
use App\Tools\GSM;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class MessageFormatter
{
    /**
     * @var PhoneConfigManager
     */
    private $phoneConfigManager;

    /**
     * @var LanguageConfigManager
     */
    private $languageManager;

    /**
     * @var PlatformConfigManager
     */
    private $platformManager;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var Environment
     */
    private $templating;

    public function __construct(PhoneConfigManager $phoneConfigManager,
        LanguageConfigManager $languageManager,
        PlatformConfigManager $platformManager,
        RouterInterface $router,
        TranslatorInterface $translator,
        Environment $templating)
    {
        $this->phoneConfigManager = $phoneConfigManager;
        $this->languageManager    = $languageManager;
        $this->platformManager    = $platformManager;
        $this->router             = $router;
        $this->translator         = $translator;
        $this->templating         = $templating;
    }

    public function formatMessageContent(Message $message) : string
    {
        switch ($message->getCommunication()->getType()) {
            case Communication::TYPE_SMS:
                return $this->formatSMSContent($message);
            case Communication::TYPE_CALL:
                return $this->formatCallContent($message);
            case Communication::TYPE_EMAIL:
                return $this->formatHtmlEmailContent($message);
        }
    }

    public function formatSMSContent(Message $message) : string
    {
        if ($country = $this->phoneConfigManager->getPhoneConfigForVolunteer($message->getVolunteer())) {
            $this->phoneConfigManager->applyContext($country);
        }

        $contentParts  = [];
        $communication = $message->getCommunication();

        $language = $this->languageManager->getLanguageConfigForCommunication(
            $message->getCommunication()
        );

        if ($message->getCommunication()->getShortcut() && $message->getVolunteer()->needsShortcutInMessages()) {
            $contentParts[] = $this->translator->trans('message.sms.announcement_with_shortcut', [
                '%brand%'    => mb_strtoupper($language->getBrand()),
                '%shortcut%' => $message->getCommunication()->getShortcut(),
                '%hours%'    => date('H'),
                '%mins%'     => date('i'),
            ], null, $language->getLocale());
        } else {
            $contentParts[] = $this->translator->trans('message.sms.announcement', [
                '%brand%' => mb_strtoupper($language->getBrand()),
                '%hours%' => date('H'),
                '%mins%'  => date('i'),
            ], null, $language->getLocale());
        }

        $contentParts[] = $communication->getBody();

        // Possible responses
        $choices = $communication->getChoices();
        if (is_object($choices)) {
            $choices = $communication->getChoices()->toArray();
        }

        if ($choices) {
            foreach ($choices as $choice) {
                // The way to answer depends on the volunteer's phone number because answering directly to sms and
                // incoming calls are not always supported, see config/countries.yaml for more details.
                if ($country && $country->isInboundSmsEnabled() && !$message->getVolunteer()->isOnlyOutboundSms()) {
                    $contentParts[] = sprintf('%s%s: %s', $message->getPrefix(), $choice->getCode(), $choice->getLabel());
                } elseif ($country && ($country->isInboundCallEnabled() || $message->getVolunteer()->isOnlyOutboundSms())) {
                    $contentParts[] = sprintf('%s: %s', $choice->getCode(), $choice->getLabel());
                } else {
                    $contentParts[] = sprintf('- %s', $choice->getLabel());
                }
            }

            if ($country && $country->isInboundSmsEnabled() && !$message->getVolunteer()->isOnlyOutboundSms()) {
                if (!$message->getCommunication()->isMultipleAnswer()) {
                    $contentParts[] = $this->translator->trans('message.sms.how_to_answer_simple', [], null, $language->getLocale());
                } else {
                    $contentParts[] = $this->translator->trans('message.sms.how_to_answer_multiple', [], null, $language->getLocale());
                }
            } elseif ($country && ($country->isInboundCallEnabled() || $message->getVolunteer()->isOnlyOutboundSms())) {
                $contentParts[] = $this->translator->trans('message.sms.how_to_answer_url', [
                    '%url%'    => trim(getenv('WEBSITE_URL'), '/').$this->router->generate('message_open', ['code' => $message->getCode()]),
                    '%number%' => $country->getInboundCallNumber(),
                ], null, $language->getLocale());
            } else {
                $contentParts[] = $this->translator->trans('message.sms.how_to_answer_url_only', [
                    '%url%' => trim(getenv('WEBSITE_URL'), '/').$this->router->generate('message_open', ['code' => $message->getCode()]),
                ], null, $language->getLocale());
            }
        }

        $this->phoneConfigManager->restoreContext();

        return GSM::enforceGSMAlphabet(implode("\n", $contentParts));
    }

    public function formatSimpleSMSContent(Volunteer $volunteer, string $content) : string
    {
        if ($country = $this->phoneConfigManager->getPhoneConfigForVolunteer($volunteer)) {
            $this->phoneConfigManager->applyContext($country);
        }

        $platform = $this->platformManager->getPlaform(
            $volunteer->getPlatform()
        );

        $contentParts[] = $this->translator->trans('message.sms.announcement', [
            '%brand%' => mb_strtoupper($platform->getDefaultLanguage()->getBrand()),
            '%hours%' => date('H'),
            '%mins%'  => date('i'),
        ], null, $platform->getDefaultLanguage()->getLocale());

        $contentParts[] = $content;

        $this->phoneConfigManager->restoreContext();

        return GSM::enforceGSMAlphabet(implode("\n", $contentParts));
    }

    public function formatCallContent(Message $message, bool $withChoices = true) : string
    {
        if ($country = $this->phoneConfigManager->getPhoneConfigForVolunteer($message->getVolunteer())) {
            $this->phoneConfigManager->applyContext($country);
        }

        $language = $this->languageManager->getLanguageConfigForCommunication(
            $message->getCommunication()
        );

        $communication = $message->getCommunication();

        $contentParts = [];

        $hours = ltrim($message->getCommunication()->getCreatedAt()->format('H'), 0);
        if (!$hours) {
            $hours = 0;
        }

        $mins = ltrim($message->getCommunication()->getCreatedAt()->format('i'), 0);
        if (!$mins) {
            $mins = 0;
        }

        $contentParts[] = $this->translator->trans('message.call.announcement', [
            '%brand%' => $language->getBrand(),
            '%hours%' => $hours,
            '%mins%'  => $mins,
        ], null, $language->getLocale());

        $contentParts[] = sprintf('%s.', $communication->getBody());

        if ($withChoices) {
            $contentParts[] = $this->formatCallChoicesContent($message);
        }

        $this->phoneConfigManager->restoreContext();

        return implode("\n", $contentParts);
    }

    public function formatCallChoicesContent(Message $message) : string
    {
        $contentParts = [];

        $communication = $message->getCommunication();

        $language = $this->languageManager->getLanguageConfigForCommunication(
            $message->getCommunication()
        );

        $choices = $communication->getChoices();
        if (is_object($choices)) {
            $choices = $communication->getChoices()->toArray();
        }

        if ($choices) {
            foreach ($choices as $choice) {
                $contentParts[] = $this->translator->trans('message.call.choices', [
                    '%answer%' => $choice->getLabel(),
                    '%code%'   => $choice->getCode(),
                ], null, $language->getLocale());
            }
        }

        $contentParts[] = $this->translator->trans('message.call.repeat', [], null, $language->getLocale());

        return implode("\n", $contentParts);
    }

    public function formatTextEmailContent(Message $message) : string
    {
        $contentParts  = [];
        $communication = $message->getCommunication();

        if ($communication->getSubject()) {
            $contentParts[] = $communication->getSubject();
            $contentParts[] = str_repeat('-', mb_strlen($communication->getSubject()));
            $contentParts[] = '';
        }

        if ($country = $this->phoneConfigManager->getPhoneConfigForVolunteer($message->getVolunteer())) {
            $this->phoneConfigManager->applyContext($country);
        }

        $language = $this->languageManager->getLanguageConfigForCommunication(
            $message->getCommunication()
        );

        $contentParts[] = $this->translator->trans('message.email.announcement', [
            '%brand%' => mb_strtoupper($language->getBrand()),
            '%day%'   => date('d'),
            '%month%' => date('m'),
            '%year%'  => date('Y'),
            '%hours%' => date('H'),
            '%mins%'  => date('i'),
        ], null, $language->getLocale());

        $contentParts[] = '';

        $contentParts[] = strip_tags($communication->getBody());
        $contentParts[] = '';

        $choices = $communication->getChoices();
        if (is_object($choices)) {
            $choices = $communication->getChoices()->toArray();
        }
        if ($choices) {

            $contentParts[] = $this->translator->trans('message.email.possible_answers', [], null, $language->getLocale());
            foreach ($choices as $choice) {
                $contentParts[] = sprintf('%s: %s', $choice->getCode(), $choice->getLabel());
            }
            $contentParts[] = '';

            $url = trim(getenv('WEBSITE_URL'), '/').$this->router->generate('message_open', ['code' => $message->getCode()]);
            if (!$communication->isMultipleAnswer()) {
                $contentParts[] = $this->translator->trans('message.email.how_to_answer_simple', [
                    '%url%' => $url,
                ], null, $language->getLocale());
            } else {
                $contentParts[] = $this->translator->trans('message.email.how_to_answer_multiple', [
                    '%url%' => $url,
                ], null, $language->getLocale());
            }
            $contentParts[] = '';
        }

        $this->phoneConfigManager->restoreContext();

        return implode("\n", $contentParts);
    }

    public function formatHtmlEmailContent(Message $message) : string
    {
        if ($country = $this->phoneConfigManager->getPhoneConfigForVolunteer($message->getVolunteer())) {
            $this->phoneConfigManager->applyContext($country);
        }

        $language = $this->languageManager->getLanguageConfigForCommunication(
            $message->getCommunication()
        );

        $content = $this->templating->render('message/email.html.twig', [
            'website_url'   => getenv('WEBSITE_URL'),
            'message'       => $message,
            'communication' => $message->getCommunication(),
            'language'      => $language,
        ]);

        $this->phoneConfigManager->restoreContext();

        return $content;
    }
}