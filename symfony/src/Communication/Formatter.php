<?php

namespace App\Communication;

use App\Entity\Communication;
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

        // Type "alert": volunteer can answer by SMS
        if ($message->getCommunication()->getType() === Communication::TYPE_ALERT) {
            if ($communication->getChoices() && $communication->getChoices()->toArray()) {
                foreach ($communication->getChoices() as $choice) {
                    $contentParts[] = sprintf('%s: %s', $choice->getCode(), $choice->getLabel());
                }
                $contentParts[] = $this->translator->trans('message.how_to_answer_alert');
            }
        }

        // Type "web": volunteer can click on a link to answer
        if ($message->getCommunication()->getType() === Communication::TYPE_WEB) {
            if ($communication->getChoices()) {
                $contentParts[] = $this->translator->trans('message.how_to_answer_web', [
                    '%url%' => trim(getenv('WEBSITE_URL'), '/').$this->router->generate('message_open', ['code' => $message->getWebCode()]),
                ]);
            }
        }

        // Enabled geo location
        if ($message->getCommunication()->getType() !== Communication::TYPE_WEB
            && $message->getCommunication()->hasGeoLocation()) {
            $contentParts[] = $this->translator->trans('message.geo_location', [
                '%url%' => trim(getenv('WEBSITE_URL'), '/').$this->router->generate('geo_open', ['code' => $message->getGeoCode()]),
            ]);
        }

        return GSM::enforceGSMAlphabet(implode("\n", $contentParts));
    }
}