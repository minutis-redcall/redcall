<?php

namespace App\Controller;

use App\Base\BaseController;
use App\Entity\Message;
use App\Manager\MessageManager;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class MessageController
 *
 * WARNING: this controller is OUT of the security firewall.
 *
 * @Route(path="msg/", name="message_")
 */
class MessageController extends BaseController
{
    /**
     * @var MessageManager
     */
    protected $messageManager;

    public function __construct(MessageManager $messageRepository)
    {
        $this->messageManager = $messageRepository;
    }

    /**
     * @Route(path="{code}", name="open", methods={"GET"})
     */
    public function openAction(Message $message)
    {
        return $this->render('message/index.html.twig', [
            'campaign'      => $message->getCommunication()->getCampaign(),
            'communication' => $message->getCommunication(),
            'message'       => $message,
            'website_url'   => getenv('WEBSITE_URL'),
        ]);
    }

    /**
     * @Route(path="{code}/{signature}/{action}", name="action", requirements={"action" = "\d+"}, methods={"GET"})
     */
    public function actionAction(Message $message, int $action, string $signature)
    {
        $this->checkSignature($message, $signature);

        // If the action does not exist, throw an exception
        $choice = $message->getCommunication()->getChoiceByCode($message->getPrefix(), sprintf('%s%s', $message->getPrefix(), $action));
        if (null === $choice) {
            throw $this->createNotFoundException();
        }

        // If the selected action has not already been made, store it
        if (!$message->getAnswers()->contains($choice)) {
            $this->messageManager->addAnswer($message, sprintf('%s%s', $message->getPrefix(), $action));
        }

        return $this->redirectToRoute('message_open', [
            'code' => $message->getCode(),
        ]);
    }

    /**
     * @Route(path="{code}/annuler/{signature}/{action}", name="cancel", requirements={"action" = "\d+"},
     *                                                    methods={"GET"})
     */
    public function cancelAction(Message $message, int $action, string $signature)
    {
        $this->checkSignature($message, $signature);

        // If the action does not exist, throw an exception
        $choice = $message->getCommunication()->getChoiceByCode($message->getPrefix(), sprintf('%s%s', $message->getPrefix(), $action));
        if (null === $choice) {
            throw $this->createNotFoundException();
        }

        // If the selected action has been made, cancel it
        if ($message->getAnswerByChoice($choice)) {
            $this->messageManager->cancelAnswerByChoice($message, $choice);
        }

        return $this->redirectToRoute('message_open', [
            'code' => $message->getCode(),
        ]);
    }

    /**
     * @param Message $message
     *
     * @param string  $signature
     */
    private function checkSignature(Message $message, string $signature)
    {
        // Smouth rollout: do not invalidate links in emails sent recently.
        // TODO: remove this on 01/05/2020
        return;

        if ($message->getSignature() !== $signature) {
            throw $this->createNotFoundException();
        }
    }
}