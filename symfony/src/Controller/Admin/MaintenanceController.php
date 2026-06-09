<?php

namespace App\Controller\Admin;

use App\Base\BaseController;
use App\Manager\MaintenanceManager;
use App\Settings;
use Bundles\SettingsBundle\Manager\SettingManager;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: "admin/maintenance", name: "admin_maintenance_")]
#[IsGranted("ROLE_ROOT")]
class MaintenanceController extends BaseController
{
    private MaintenanceManager $maintenanceManager;
    private SettingManager $settingManager;
    private TranslatorInterface $translator;

    public function __construct(
        MaintenanceManager $maintenanceManager,
        SettingManager $settingManager,
        TranslatorInterface $translator
    ) {
        $this->maintenanceManager = $maintenanceManager;
        $this->settingManager     = $settingManager;
        $this->translator         = $translator;
    }

    #[Route(name: "index", path: "/")]
    public function index()
    {
        return $this->render('admin/maintenance/index.html.twig');
    }

    #[Route(name: "data_sync", path: "/data-sync")]
    public function dataSync()
    {
        $this->maintenanceManager->dataSync();

        $this->addFlash('success', $this->translator->trans('maintenance.data_sync_started'));

        return $this->redirectToRoute('admin_maintenance_index');
    }

    #[Route(name: "annuaire_national", path: "/annuaire-national")]
    public function annuaireNational()
    {
        $this->maintenanceManager->annuaireNational();

        $this->addFlash('success', $this->translator->trans('maintenance.annuaire_national_started'));

        return $this->redirectToRoute('admin_maintenance_index');
    }

    #[Route(name: "message", path: "/message")]
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
                    new Choice(choices: $types),
                ],
            ])
            ->add('content', TextareaType::class, [
                'label'       => 'maintenance.message.content',
                'attr'        => [
                    'rows' => 3,
                ],
                'required'    => false,
                'constraints' => [
                    new Length(max: 1024),
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
