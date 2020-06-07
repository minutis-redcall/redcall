<?php

namespace App\Controller;

use App\Base\BaseController;
use App\Entity\Message;
use App\Entity\VolunteerSession;
use App\Manager\MessageManager;
use App\Manager\VolunteerManager;
use App\Manager\VolunteerSessionManager;
use App\Tools\PhoneNumberParser;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="/space/{sessionId}", name="space_")
 * @IsGranted("VOLUNTEER_SESSION", subject="session")
 */
class SpaceController extends BaseController
{
    /**
     * @var VolunteerSessionManager
     */
    private $volunteerSessionManager;

    /**
     * @var VolunteerManager
     */
    private $volunteerManager;

    /**
     * @var MessageManager
     */
    private $messageManager;

    /**
     * @param VolunteerSessionManager $volunteerSessionManager
     * @param VolunteerManager        $volunteerManager
     * @param MessageManager          $messageManager
     */
    public function __construct(VolunteerSessionManager $volunteerSessionManager, VolunteerManager $volunteerManager, MessageManager $messageManager)
    {
        $this->volunteerSessionManager = $volunteerSessionManager;
        $this->volunteerManager = $volunteerManager;
        $this->messageManager = $messageManager;
    }

    /**
     * @Route(path="/", name="home")
     */
    public function home(VolunteerSession $session)
    {
        return $this->render('space/index.html.twig', [
            'session' => $session,
        ]);
    }

    /**
     * @Route(path="/infos", name="infos")
     */
    public function infos(VolunteerSession $session)
    {
        return $this->render('space/infos.html.twig', [
            'session' => $session,
        ]);
    }

    /**
     * @Route(path="/phone", name="phone")
     */
    public function phone(VolunteerSession $session, Request $request)
    {
        $volunteer = $session->getVolunteer();

        $form = $this->createFormBuilder($volunteer)
            ->add('phoneNumber', TextType::class, [
                'label' => 'manage_volunteers.form.phone_number',
                'required' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'base.button.save',
            ])
            ->getForm()
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($volunteer->getPhoneNumber()) {
                $volunteer->setPhoneNumber(
                    PhoneNumberParser::parse($volunteer->getPhoneNumber())
                );
            }
            $volunteer->setPhoneNumberLocked(true);

            $this->volunteerManager->save($session->getVolunteer());

            return $this->redirectToRoute('space_home', [
                'sessionId' => $session->getSessionId(),
            ]);
        }

        return $this->render('space/phone.html.twig', [
            'session' => $session,
            'form' => $form->createView(),
            'from' => getenv('TWILIO_NUMBER'),
        ]);
    }

    /**
     * @Route(path="/email", name="email")
     */
    public function email(VolunteerSession $session, Request $request)
    {
        $volunteer = $session->getVolunteer();

        $form = $this->createFormBuilder($volunteer)
            ->add('email', EmailType::class, [
                'label' => 'manage_volunteers.form.email',
                'required' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'base.button.save',
            ])
            ->getForm()
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $session->getVolunteer()->setEmailLocked(true);

            $this->volunteerManager->save($volunteer);

            return $this->redirectToRoute('space_home', [
                'sessionId' => $session->getSessionId(),
            ]);
        }

        return $this->render('space/email.html.twig', [
            'session' => $session,
            'form' => $form->createView(),
            'from' => getenv('MAILER_FROM'),
        ]);
    }

    /**
     * @Route(path="/enabled", name="enabled")
     */
    public function enabled(VolunteerSession $session, Request $request)
    {
        $volunteer = $session->getVolunteer();

        $form = $this->createFormBuilder($volunteer)
            ->add('phoneNumberOptin', CheckboxType::class, [
                'label' => 'manage_volunteers.form.phone_number_optin',
                'required' => false,
            ])
            ->add('emailOptin', CheckboxType::class, [
                'label' => 'manage_volunteers.form.email_optin',
                'required' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'base.button.save',
            ])
            ->getForm()
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->volunteerManager->save($volunteer);

            return $this->redirectToRoute('space_home', [
                'sessionId' => $session->getSessionId(),
            ]);
        }

        return $this->render('space/enabled.html.twig', [
            'session' => $session,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route(path="/consult-data", name="consult_data")
     */
    public function consultData(VolunteerSession $session, Request $request)
    {
        $communications = [];
        foreach ($session->getVolunteer()->getMessages() as $message) {
            /** @var Message $message */
            if (!isset($communications[$message->getCommunication()->getId()])) {
                $communications[$message->getCommunication()->getId()] = [];
            }

            $communications[$message->getCommunication()->getId()][] = $message;
        }

        return $this->render('space/consult_data.html.twig', [
            'session' => $session,
            'communications' => $communications,
        ]);
    }

    /**
     * @Route(path="/logout", name="logout")
     */
    public function logout(VolunteerSession $session)
    {
        $this->volunteerSessionManager->removeSession($session);

        return $this->redirectToRoute('home');
    }
}