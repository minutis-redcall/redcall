<?php

namespace App\Controller;

use App\Base\BaseController;
use App\Entity\Message;
use App\Manager\MessageManager;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Length;

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
     * @Route(path="{code}", name="open", methods={"GET", "POST"})
     */
    public function openAction(Request $request, Message $message)
    {
        $form = $this->createFreeAnswerForm($request);
        if ($form->isSubmitted() && $form->isValid() && $answer = $form->get('freeAnswer')->getData()) {
            $this->messageManager->addAnswer($message, $answer);

            return $this->redirectToRoute('message_open', [
                'code' => $message->getCode(),
            ]);
        }

        return $this->render('message/index.html.twig', [
            'campaign'      => $message->getCommunication()->getCampaign(),
            'communication' => $message->getCommunication(),
            'message'       => $message,
            'website_url'   => getenv('WEBSITE_URL'),
            'form'          => $form->createView(),
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

    private function createFreeAnswerForm(Request $request) : FormInterface
    {
        return $this
            ->createFormBuilder()
            ->add('freeAnswer', TextType::class, [
                'label'       => 'campaign_status.free_answer',
                'constraints' => [
                    new Length(['max' => 1024]),
                ],
                'required'    => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'base.button.submit',
            ])
            ->getForm()
            ->handleRequest($request);
    }

    private function checkSignature(Message $message, string $signature)
    {
        if ($message->getSignature() !== $signature) {
            throw $this->createNotFoundException();
        }
    }
}