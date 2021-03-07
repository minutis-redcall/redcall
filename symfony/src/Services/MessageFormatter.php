<?php

namespace App\Services;

use App\Entity\Communication;
use App\Entity\Message;
use App\Entity\Volunteer;
use App\Manager\PhoneConfigManager;
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
        RouterInterface $router,
        TranslatorInterface $translator,
        Environment $templating)
    {
        $this->phoneConfigManager = $phoneConfigManager;
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
        if ($country = $this->phoneConfigManager->getPhoneConfig($message->getVolunteer())) {
            $this->phoneConfigManager->applyContext($country);
        }

        $contentParts  = [];
        $communication = $message->getCommunication();

        $contentParts[] = $this->translator->trans('message.sms.announcement', [
            '%brand%' => mb_strtoupper(getenv('BRAND')),
            '%hours%' => date('H'),
            '%mins%'  => date('i'),
        ]);

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
                if ($country && $country->isInboundSmsEnabled()) {
                    $contentParts[] = sprintf('%s%s: %s', $message->getPrefix(), $choice->getCode(), $choice->getLabel());
                } elseif ($country && $country->isInboundCallEnabled()) {
                    $contentParts[] = sprintf('%s: %s', $choice->getCode(), $choice->getLabel());
                } else {
                    $contentParts[] = sprintf('- %s', $choice->getLabel());
                }
            }

            if ($country && $country->isInboundSmsEnabled()) {
                if (!$message->getCommunication()->isMultipleAnswer()) {
                    $contentParts[] = $this->translator->trans('message.sms.how_to_answer_simple');
                } else {
                    $contentParts[] = $this->translator->trans('message.sms.how_to_answer_multiple');
                }
            } elseif ($country && $country->isInboundCallEnabled()) {
                $contentParts[] = $this->translator->trans('message.sms.how_to_answer_url', [
                    '%url%'    => trim(getenv('WEBSITE_URL'), '/').$this->router->generate('message_open', ['code' => $message->getCode()]),
                    '%number%' => $country->getInboundCallNumber(),
                ]);
            } else {
                $contentParts[] = $this->translator->trans('message.sms.how_to_answer_url_only', [
                    '%url%' => trim(getenv('WEBSITE_URL'), '/').$this->router->generate('message_open', ['code' => $message->getCode()]),
                ]);
            }
        }

        // Enabled geo location
        if ($message->getCommunication()->hasGeoLocation()) {
            $contentParts[] = $this->translator->trans('message.sms.geo_location', [
                '%url%' => trim(getenv('WEBSITE_URL'), '/').$this->router->generate('geo_open', ['code' => $message->getCode()]),
            ]);
        }

        $this->phoneConfigManager->restoreContext();

        return GSM::enforceGSMAlphabet(implode("\n", $contentParts));
    }

    public function formatSimpleSMSContent(Volunteer $volunteer, string $content) : string
    {
        if ($country = $this->phoneConfigManager->getPhoneConfig($volunteer)) {
            $this->phoneConfigManager->applyContext($country);
        }

        $contentParts[] = $this->translator->trans('message.sms.announcement', [
            '%brand%' => mb_strtoupper(getenv('BRAND')),
            '%hours%' => date('H'),
            '%mins%'  => date('i'),
        ]);

        $contentParts[] = $content;

        $this->phoneConfigManager->restoreContext();

        return GSM::enforceGSMAlphabet(implode("\n", $contentParts));
    }

    public function formatCallContent(Message $message, bool $withChoices = true) : string
    {
        if ($country = $this->phoneConfigManager->getPhoneConfig($message->getVolunteer())) {
            $this->phoneConfigManager->applyContext($country);
        }

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
            '%brand%' => mb_strtoupper(getenv('BRAND')),
            '%hours%' => $hours,
            '%mins%'  => $mins,
        ]);

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

        $choices = $communication->getChoices();
        if (is_object($choices)) {
            $choices = $communication->getChoices()->toArray();
        }

        if ($choices) {
            foreach ($choices as $choice) {
                $contentParts[] = $this->translator->trans('message.call.choices', [
                    '%answer%' => $choice->getLabel(),
                    '%code%'   => $choice->getCode(),
                ]);
            }
        }

        $contentParts[] = $this->translator->trans('message.call.repeat');

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

        if ($country = $this->phoneConfigManager->getPhoneConfig($message->getVolunteer())) {
            $this->phoneConfigManager->applyContext($country);
        }

        $contentParts[] = $this->translator->trans('message.email.announcement', [
            '%brand%' => mb_strtoupper(getenv('BRAND')),
            '%day%'   => date('d'),
            '%month%' => date('m'),
            '%year%'  => date('Y'),
            '%hours%' => date('H'),
            '%mins%'  => date('i'),
        ]);

        $contentParts[] = '';

        $contentParts[] = strip_tags($communication->getBody());
        $contentParts[] = '';

        $choices = $communication->getChoices();
        if (is_object($choices)) {
            $choices = $communication->getChoices()->toArray();
        }
        if ($choices) {

            $contentParts[] = $this->translator->trans('message.email.possible_answers');
            foreach ($choices as $choice) {
                $contentParts[] = sprintf('%s: %s', $choice->getCode(), $choice->getLabel());
            }
            $contentParts[] = '';

            $url = trim(getenv('WEBSITE_URL'), '/').$this->router->generate('message_open', ['code' => $message->getCode()]);
            if (!$communication->isMultipleAnswer()) {
                $contentParts[] = $this->translator->trans('message.email.how_to_answer_simple', [
                    '%url%' => $url,
                ]);
            } else {
                $contentParts[] = $this->translator->trans('message.email.how_to_answer_multiple', [
                    '%url%' => $url,
                ]);
            }
            $contentParts[] = '';
        }

        $this->phoneConfigManager->restoreContext();

        return implode("\n", $contentParts);
    }

    public function formatHtmlEmailContent(Message $message) : string
    {
        if ($country = $this->phoneConfigManager->getPhoneConfig($message->getVolunteer())) {
            $this->phoneConfigManager->applyContext($country);
        }

        $content = $this->templating->render('message/email.html.twig', [
            'website_url'   => getenv('WEBSITE_URL'),
            'message'       => $message,
            'communication' => $message->getCommunication(),
        ]);

        $this->phoneConfigManager->restoreContext();

        return $content;
    }
}