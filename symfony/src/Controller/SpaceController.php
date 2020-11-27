<?php

namespace App\Controller;

use App\Base\BaseController;
use App\Component\HttpFoundation\MpdfResponse;
use App\Entity\Message;
use App\Entity\VolunteerSession;
use App\Form\Type\PhonesType;
use App\Manager\MessageManager;
use App\Manager\VolunteerManager;
use App\Manager\VolunteerSessionManager;
use App\Tools\PhoneNumber;
use App\Tools\PhoneNumberParser;
use Mpdf\Mpdf;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
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
    public function __construct(VolunteerSessionManager $volunteerSessionManager,
        VolunteerManager $volunteerManager,
        MessageManager $messageManager)
    {
        $this->volunteerSessionManager = $volunteerSessionManager;
        $this->volunteerManager        = $volunteerManager;
        $this->messageManager          = $messageManager;
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
                     ->add('phones', PhonesType::class, [
                         'label' => false,
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
            'form'    => $form->createView(),
            'from'    => implode(' / ', PhoneNumber::listAllNumbers()),
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
                         'label'    => 'manage_volunteers.form.email',
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
            'form'    => $form->createView(),
            'from'    => getenv('MAILER_FROM'),
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
                         'label'    => 'manage_volunteers.form.phone_number_optin',
                         'required' => false,
                     ])
                     ->add('emailOptin', CheckboxType::class, [
                         'label'    => 'manage_volunteers.form.email_optin',
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
            'form'    => $form->createView(),
        ]);
    }

    /**
     * @Route(path="/consult-data", name="consult_data")
     */
    public function consultData(VolunteerSession $session)
    {
        return $this->render('space/consult_data.html.twig', [
            'session'        => $session,
            'communications' => $this->getSessionCommunications($session),
        ]);
    }

    /**
     * @Route(path="/download-data", name="download_data")
     */
    public function downloadData(VolunteerSession $session)
    {
        $mpdf = new Mpdf([
            'tempDir'       => sys_get_temp_dir(),
            'margin_bottom' => 25,
        ]);

        $mpdf->WriteHTML($this->renderView('space/data.html.twig', [
            'session'        => $session,
            'communications' => $this->getSessionCommunications($session),
        ]));

        return new MpdfResponse(
            $mpdf,
            sprintf('data-%s-%s.pdf', $session->getVolunteer()->getNivol(), date('Y-m-d'))
        );
    }

    /**
     * @Route(path="/delete-data", name="delete_data")
     */
    public function deleteData(VolunteerSession $session, Request $request)
    {
        throw $this->createNotFoundException();

        $form = $this->createFormBuilder()
                     ->add('cancel', SubmitType::class, [
                         'label' => 'space.delete_data.cancel',
                         'attr'  => [
                             'class' => 'btn btn-success',
                         ],
                     ])
                     ->add('confirm', SubmitType::class, [
                         'label' => 'space.delete_data.confirm',
                         'attr'  => [
                             'class' => 'btn btn-danger',
                         ],
                     ])
                     ->getForm()
                     ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->volunteerManager->anonymize($session->getVolunteer());

            return $this->redirectToRoute('home');
        }

        return $this->render('space/delete_data.html.twig', [
            'form'    => $form->createView(),
            'session' => $session,
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

    private function getSessionCommunications(VolunteerSession $session) : array
    {
        $communications = [];
        foreach ($session->getVolunteer()->getMessages() as $message) {
            /** @var Message $message */
            if (!isset($communications[$message->getCommunication()->getId()])) {
                $communications[$message->getCommunication()->getId()] = [];
            }

            $communications[$message->getCommunication()->getId()][] = $message;
        }

        return $communications;
    }
}