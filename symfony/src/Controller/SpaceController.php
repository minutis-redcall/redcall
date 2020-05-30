<?php

namespace App\Controller;

use App\Base\BaseController;
use App\Entity\VolunteerSession;
use App\Manager\VolunteerManager;
use App\Manager\VolunteerSessionManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
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
     * @param VolunteerManager $volunteerManager
     */
    public function __construct(VolunteerManager $volunteerManager)
    {
        $this->volunteerManager = $volunteerManager;
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
        $form = $this->createFormBuilder($session->getVolunteer())
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
            $session->getVolunteer()->setPhoneNumberLocked(true);
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
     * @Route(path="/logout", name="logout")
     */
    public function logout(VolunteerSession $session)
    {
        $this->volunteerSessionManager->removeSession($session);

        return $this->redirectToRoute('home');
    }
}