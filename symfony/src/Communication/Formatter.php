<?php

namespace App\Communication;

use App\Entity\Message;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

class Formatter
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
     * Formatter constructor.
     *
     * @param RouterInterface     $router
     * @param TranslatorInterface $translator
     */
    public function __construct(RouterInterface $router, TranslatorInterface $translator)
    {
        $this->router     = $router;
        $this->translator = $translator;
    }

    /**
     * @param Message $message
     *
     * @return string
     */
    public function formatMessageContent(Message $message): string
    {
        $contentParts  = [];
        $communication = $message->getCommunication();
        $body          = $communication->getBody();

        $contentParts[] = $this->translator->trans('message.announcement', [
            '%hours%' => date('H'),
            '%mins%'  => date('i'),
        ]);

        $contentParts[] = $body;

        // Possible responses
        $choices = $communication->getChoices();
        if (is_object($choices)) {
            $choices = $communication->getChoices()->toArray();
        }

        if ($choices) {
            foreach ($choices as $choice) {
                $contentParts[] = sprintf('%s: %s', $choice->getCode(), $choice->getLabel());
            }
            if (!$message->getCommunication()->isMultipleAnswer()) {
                $contentParts[] = $this->translator->trans('message.how_to_answer_simple');
            } else {
                $contentParts[] = $this->translator->trans('message.how_to_answer_multiple');
            }
        }

        // Enabled geo location
        if ($message->getCommunication()->hasGeoLocation()) {
            $contentParts[] = $this->translator->trans('message.geo_location', [
                '%url%' => trim(getenv('WEBSITE_URL'), '/').$this->router->generate('geo_open', ['code' => $message->getWebCode()]),
            ]);
        }

        return GSM::enforceGSMAlphabet(implode("\n", $contentParts));
    }
}