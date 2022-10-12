<?php

namespace App\Controller;

use App\Base\BaseController;
use App\Entity\Message;
use App\Manager\LanguageConfigManager;
use App\Manager\MessageManager;
use App\Manager\VolunteerManager;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Length;

/**
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

    /**
     * @var LanguageConfigManager
     */
    protected $languageManager;

    /**
     * @var VolunteerManager
     */
    protected $volunteerManager;

    public function __construct(MessageManager $messageManager,
        LanguageConfigManager $languageManager,
        VolunteerManager $volunteerManager)
    {
        $this->messageManager   = $messageManager;
        $this->languageManager  = $languageManager;
        $this->volunteerManager = $volunteerManager;
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

        $language = $this->languageManager->getLanguageConfigForCommunication(
            $message->getCommunication()
        );

        return $this->render('message/index.html.twig', [
            'campaign'      => $message->getCommunication()->getCampaign(),
            'communication' => $message->getCommunication(),
            'message'       => $message,
            'website_url'   => getenv('WEBSITE_URL'),
            'form'          => $form->createView(),
            'language'      => $language,
        ]);
    }

    /**
     * @Route(path="/optout/{code}", name="optout", methods={"GET", "POST"})
     */
    public function optoutAction(Request $request, Message $message)
    {
        $form = $this->createFormBuilder()
                     ->add('cancel', SubmitType::class, [
                         'label' => 'campaign_status.optout.cancel',
                         'attr'  => [
                             'class' => 'btn btn-success',
                         ],
                     ])
                     ->add('confirm', SubmitType::class, [
                         'label' => 'campaign_status.optout.confirm',
                         'attr'  => [
                             'class' => 'btn btn-danger',
                         ],
                     ])
                     ->getForm()
                     ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('confirm')->isClicked()) {
                $message->getVolunteer()->setEmailOptin(false);
                $this->volunteerManager->save($message->getVolunteer());

                $this->addFlash('success', 'campaign_status.optout.saved');
            }

            return $this->redirectToRoute('message_open', [
                'code' => $message->getCode(),
            ]);
        }

        return $this->render('message/optout.html.twig', [
            'message' => $message,
            'form'    => $form->createView(),
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
                'constraints' => [
                    new Length(['max' => 1024]),
                ],
                'required'    => false,
            ])
            ->add('submit', SubmitType::class)
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