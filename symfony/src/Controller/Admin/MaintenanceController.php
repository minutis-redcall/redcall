<?php

namespace App\Controller\Admin;

use App\Base\BaseController;
use App\Form\Type\VolunteerWidgetType;
use App\Manager\MaintenanceManager;
use App\Manager\VolunteerManager;
use App\Settings;
use Bundles\PegassCrawlerBundle\Entity\Pegass;
use Bundles\PegassCrawlerBundle\Manager\PegassManager;
use Bundles\SettingsBundle\Manager\SettingManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route(path="admin/maintenance", name="admin_maintenance_")
 * @IsGranted("ROLE_DEVELOPER")
 */
class MaintenanceController extends BaseController
{
    /**
     * @var MaintenanceManager
     */
    private $maintenanceManager;

    /**
     * @var SettingManager
     */
    private $settingManager;

    /**
     * @var VolunteerManager
     */
    private $volunteerManager;

    /**
     * @var PegassManager
     */
    private $pegassManager;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param MaintenanceManager  $maintenanceManager
     * @param SettingManager      $settingManager
     * @param VolunteerManager    $volunteerManager
     * @param PegassManager       $pegassManager
     * @param TranslatorInterface $translator
     */
    public function __construct(MaintenanceManager $maintenanceManager, SettingManager $settingManager, VolunteerManager $volunteerManager, PegassManager $pegassManager, TranslatorInterface $translator)
    {
        $this->maintenanceManager = $maintenanceManager;
        $this->settingManager = $settingManager;
        $this->volunteerManager = $volunteerManager;
        $this->pegassManager = $pegassManager;
        $this->translator = $translator;
    }

    /**
     * @Route(name="index", path="/")
     */
    public function index()
    {
        return $this->render('admin/maintenance/index.html.twig');
    }

    /**
     * @Route(name="refresh", path="/refresh")
     */
    public function refresh()
    {
        if ($this->maintenanceManager->refresh()) {
            $this->success('maintenance.refresh_started');
        } else {
            $this->alert('maintenance.refresh_error');
        }

        return $this->redirectToRoute('admin_maintenance_index');
    }

    /**
     * @Route(name="refresh_all", path="/refresh-all")
     */
    public function refreshAll()
    {
        if ($this->maintenanceManager->refreshAll()) {
            $this->success('maintenance.refresh_started');
        } else {
            $this->alert('maintenance.refresh_error');
        }

        return $this->redirectToRoute('admin_maintenance_index');
    }

    /**
     * @Route(name="search", path="/search")
     */
    public function search(Request $request)
    {
        return $this->render('admin/maintenance/search.html.twig', [
            'form' => $this->createSearchForm($request)->createView(),
        ]);
    }

    /**
     * @Route(name="search_change_nivol", path="/search/change-nivol")
     */
    public function searchChangeNivol(Request $request)
    {
        return new JsonResponse([
            'content' => htmlentities($this->getPegassEntity($request)->getXml()),
        ]);
    }

    /**
     * @Route(name="search_change_expression", path="/search/change-expression")
     */
    public function searchChangeExpression(Request $request)
    {
        $entity = $this->getPegassEntity($request);
        $expression = $this->createSearchForm($request)->get('expression')->getData();

        try {
            if ($data = $entity->xpath($expression)) {
                return new JsonResponse([
                    'content' => $this->translator->trans('maintenance.search.match', [
                        '%data%' => json_encode($data),
                    ])
                ]);
            } else {
                return new JsonResponse([
                    'content' => $this->translator->trans('maintenance.search.notmatch')
                ]);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                'content' => $this->translator->trans('maintenance.search.invalid', [
                    '%error%' => $e->getMessage(),
                ])
            ]);
        }
    }

    private function getPegassEntity(Request $request): Pegass
    {
        $nivol = $this->createSearchForm($request)->get('nivol')->getData();
        if (!$nivol) {
            return new Response('', Response::HTTP_BAD_REQUEST);
        }

        $volunteer = $this->volunteerManager->findOneByNivol($nivol);
        if (!$volunteer) {
            throw $this->createNotFoundException();
        }

        $entity = $this->pegassManager->getEntity(Pegass::TYPE_VOLUNTEER, $volunteer->getIdentifier());
        if (!$entity) {
            throw $this->createNotFoundException();
        }

        return $entity;
    }

    private function createSearchForm(Request $request)
    {
        return $this->createFormBuilder()
            ->add('nivol', VolunteerWidgetType::class, [
                'label' => 'maintenance.search.nivol',
            ])
            ->add('expression', TextareaType::class, [
                'label' => 'maintenance.search.expression',
                'attr' => [
                    'rows' => '10',
                    'placeholder' => '/volunteer/nominations/libelleCourt[text()="DLUS"]',
                ],
            ])
            ->getForm()
            ->handleRequest($request);
    }

    /**
     * @Route(name="message", path="/message")
     */
    public function message(Request $request)
    {
        $types = [
            'maintenance.message.type.success' => 'success',
            'maintenance.message.type.info'    => 'info',
            'maintenance.message.type.alert'   => 'warning',
            'maintenance.message.type.danger'  => 'danger',
        ];

        $content = $this->settingManager->get(Settings::MAINTENANCE_MESSAGE_CONTENT);
        if ($content) {
            $content = mb_substr($content, 18);
        }

        $form = $this
            ->createFormBuilder([
                'type'    => $this->settingManager->get(Settings::MAINTENANCE_MESSAGE_TYPE),
                'content' => $content,
            ])
            ->add('type', ChoiceType::class, [
                'label'       => 'maintenance.message.type.label',
                'choices'     => $types,
                'constraints' => [
                    new NotBlank(),
                    new Choice(['choices' => $types]),
                ],
            ])
            ->add('content', TextareaType::class, [
                'label'       => 'maintenance.message.content',
                'attr'        => [
                    'rows' => 3,
                ],
                'required'    => false,
                'constraints' => [
                    new Length(['max' => 1024]),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'base.button.save',
            ])
            ->getForm()
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->settingManager->set(Settings::MAINTENANCE_MESSAGE_TYPE, $form['type']->getData());

            $content = $form['content']->getData();
            if ($content) {
                $this->settingManager->set(Settings::MAINTENANCE_MESSAGE_CONTENT, sprintf('%s: %s', date('d/m/Y H:i'), $content));
            } else {
                $this->settingManager->remove(Settings::MAINTENANCE_MESSAGE_CONTENT);
            }

            return $this->redirectToRoute('admin_maintenance_message');
        }

        return $this->render('admin/maintenance/message.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
