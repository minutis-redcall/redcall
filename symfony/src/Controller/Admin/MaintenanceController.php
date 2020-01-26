<?php

namespace App\Controller\Admin;

use App\Base\BaseController;
use App\Manager\MaintenanceManager;
use App\Settings;
use Bundles\SettingsBundle\Manager\SettingManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @Route(path="admin/maintenance/", name="admin_maintenance_")
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
     * @param MaintenanceManager $maintenanceManager
     * @param SettingManager     $settingManager
     */
    public function __construct(MaintenanceManager $maintenanceManager, SettingManager $settingManager)
    {
        $this->maintenanceManager = $maintenanceManager;
        $this->settingManager     = $settingManager;
    }

    /**
     * @Route(name="index")
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
     * @Route(name="message", path="/message")
     */
    public function message(Request $request)
    {
        $types = [
            'maintenance.message.type.success' => 'success',
            'maintenance.message.type.info'    => 'info',
            'maintenance.message.type.alert'   => 'alert',
            'maintenance.message.type.danger'  => 'danger',
        ];

        $form = $this
            ->createFormBuilder([
                'type'    => $this->settingManager->get(Settings::MAINTENANCE_MESSAGE_TYPE),
                'content' => $this->settingManager->get(Settings::MAINTENANCE_MESSAGE_CONTENT),
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
                $this->settingManager->set(Settings::MAINTENANCE_MESSAGE_CONTENT, $content);
            } else {
                $this->settingManager->remove(Settings::MAINTENANCE_MESSAGE_CONTENT);
            }
        }

        return $this->render('admin/maintenance/message.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
