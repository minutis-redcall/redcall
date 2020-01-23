<?php

namespace App\Controller;

use App\Base\BaseController;
use App\Entity\Message;
use App\Repository\MessageRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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
     * @var MessageRepository
     */
    protected $messageRepository;

    public function __construct(MessageRepository $messageRepository)
    {
        $this->messageRepository = $messageRepository;
    }

    /**
     * @Route(path="{code}", name="open")
     * @Method({"GET"})
     *
     * @param string $code
     * @param int    $action
     *
     * @return Response
     */
    public function openAction(string $code)
    {
        $message = $this->getMessageByCode($code);

        return $this->render('message/index.html.twig', [
            'code'    => $code,
            'message' => $message,
        ]);
    }

    /**
     * @Route(path="{code}/{action}", name="action", requirements={"action" = "\d+"})
     * @Method({"GET"})
     *
     * @param string $code
     * @param int    $action
     *
     * @return Response
     */
    public function actionAction(string $code, int $action)
    {
        $message = $this->getMessageByCode($code);

        // If the action does not exist, throw an exception
        $choice = $message->getCommunication()->getChoiceByCode($action);
        if (null === $choice) {
            throw $this->createNotFoundException();
        }

        // If the selected action has not already been made, store it
        if (!$message->getAnswers()->contains($choice)) {
            $this->messageRepository->addAnswer($message, $action);
        }

        return $this->redirectToRoute('message_open', [
            'code' => $code,
        ]);
    }

    /**
     * @Route(path="{code}/annuler/{action}", name="cancel", requirements={"action" = "\d+"})
     * @Method({"GET"})
     *
     * @param string $code
     * @param int    $action
     *
     * @return Response
     */
    public function cancelAction(string $code, int $action)
    {
        $message = $this->getMessageByCode($code);

        // If the action does not exist, throw an exception
        $choice = $message->getCommunication()->getChoiceByCode($action);
        if (null === $choice) {
            throw $this->createNotFoundException();
        }

        // If the selected action has been made, cancel it
        if ($message->getAnswerByChoice($choice)) {
            $this->messageRepository->cancelAnswerByChoice($message, $choice);
        }

        return $this->redirectToRoute('message_open', [
            'code' => $code,
        ]);
    }

    /**
     * @param string $code
     *
     * @return Message
     *
     * @throws NotFoundHttpException
     */
    private function getMessageByCode(string $code): Message
    {
        $message = $this->messageRepository->findOneBy([
            'code' => $code,
        ]);

        if (null === $message) {
            throw $this->createNotFoundException();
        }

        return $message;
    }
}