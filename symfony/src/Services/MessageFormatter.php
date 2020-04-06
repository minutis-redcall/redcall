<?php

namespace App\Services;

use App\Entity\Communication;
use App\Entity\Message;
use App\Tools\GSM;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Twig\Environment;

class MessageFormatter
{
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

    /**
     * @param RouterInterface     $router
     * @param TranslatorInterface $translator
     * @param Environment         $templating
     */
    public function __construct(RouterInterface $router, TranslatorInterface $translator, Environment $templating)
    {
        $this->router = $router;
        $this->translator = $translator;
        $this->templating = $templating;
    }

    /**
     * Used for preview only.
     *
     * @param Message $message
     *
     * @return string
     */
    public function formatMessageContent(Message $message): string
    {
        if ($message->getCommunication()->getType() === Communication::TYPE_SMS) {
            return $this->formatSMSContent($message);
        }

        return $this->formatTextEmailContent($message);
    }

    /**
     * @param Message $message
     *
     * @return string
     */
    public function formatSMSContent(Message $message): string
    {
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
                $contentParts[] = sprintf('%s%s: %s', $message->getPrefix(), $choice->getCode(), $choice->getLabel());
            }
            if (!$message->getCommunication()->isMultipleAnswer()) {
                $contentParts[] = $this->translator->trans('message.sms.how_to_answer_simple');
            } else {
                $contentParts[] = $this->translator->trans('message.sms.how_to_answer_multiple');
            }
        }

        // Enabled geo location
        if ($message->getCommunication()->hasGeoLocation()) {
            $contentParts[] = $this->translator->trans('message.sms.geo_location', [
                '%url%' => trim(getenv('WEBSITE_URL'), '/').$this->router->generate('geo_open', ['code' => $message->getCode()]),
            ]);
        }

        return GSM::enforceGSMAlphabet(implode("\n", $contentParts));
    }

    /**
     * @param Message $message
     *
     * @return string
     */
    public function formatTextEmailContent(Message $message): string
    {
        $contentParts  = [];
        $communication = $message->getCommunication();

        if ($communication->getSubject()) {
            $contentParts[] = $communication->getSubject();
            $contentParts[] = str_repeat('-', mb_strlen($communication->getSubject()));
            $contentParts[] = '';
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

        $contentParts[] = $communication->getBody();
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

        return implode("\n", $contentParts);
    }

    /**
     * @param Message $message
     *
     * @return string
     *
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function formatHtmlEmailContent(Message $message): string
    {
        return $this->templating->render('message/email.html.twig', [
            'website_url' => getenv('WEBSITE_URL'),
            'message' => $message,
            'communication' => $message->getCommunication(),
            'campaign' => $message->getCommunication()->getCampaign(),
        ]);
    }
}