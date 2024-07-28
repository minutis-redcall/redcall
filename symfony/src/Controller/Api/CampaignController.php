<?php

namespace App\Controller\Api;

use App\Base\BaseController;
use App\Facade\Trigger\SimpleMessageRequestFacade;
use App\Facade\Trigger\SimpleMessageResponseFacade;
use App\Form\Model\Campaign;
use App\Form\Model\SmsTrigger;
use App\Form\Type\AudienceType;
use App\Manager\CampaignManager;
use App\Manager\PlatformConfigManager;
use App\Manager\VolunteerManager;
use Bundles\ApiBundle\Annotation\Endpoint;
use Bundles\ApiBundle\Annotation\Facade;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Triggers management
 *
 * A trigger is a message sent through SMS, email or voice call to a given audience.
 *
 * @Route("/api/trigger", name="api_admin_trigger_")
 */
class CampaignController extends BaseController
{
    /**
     * @var VolunteerManager
     */
    private $volunteerManager;

    /**
     * @var PlatformConfigManager
     */
    private $platformManager;

    /**
     * @var CampaignManager
     */
    private $campaignManager;

    public function __construct(VolunteerManager $volunteerManager,
        PlatformConfigManager $platformManager,
        CampaignManager $campaignManager)
    {
        $this->volunteerManager = $volunteerManager;
        $this->platformManager  = $platformManager;
        $this->campaignManager  = $campaignManager;
    }

    /**
     * Send an SMS to a given list of volunteers.
     *
     * @Endpoint(
     *   priority = 700,
     *   request  = @Facade(class = SimpleMessageRequestFacade::class),
     *   response = @Facade(class = SimpleMessageResponseFacade::class)
     * )
     *
     * @Route("/sms", methods={"POST"})
     */
    public function sms(SimpleMessageRequestFacade $request)
    {
        // User is trying to set trigger's ownership to a volunteer that is out of his/her scope
        $sender = $this->volunteerManager->findOneByInternalEmail($request->getSenderInternalEmail());
        if (!$this->isGranted('VOLUNTEER', $sender)) {
            throw $this->createNotFoundException();
        }

        $receivers = [];
        foreach ($request->getReceiverInternalEmails() as $internalEmail) {
            $receiver = $this->volunteerManager->findOneByInternalEmail($internalEmail);

            if (null === $receiver) {
                continue;
            }

            if (!$this->isGranted('VOLUNTEER', $receiver)) {
                continue;
            }

            $receivers[] = $receiver->getId();
        }

        $trigger = new SmsTrigger();
        $trigger->setLanguage($this->platformManager->getPlaform($this->getPlatform())->getDefaultLanguage()->getLocale());
        $trigger->setAudience(AudienceType::createEmptyData([
            'volunteers' => $receivers,
        ]));
        $trigger->setMessage($request->getMessage());

        $campaign        = new Campaign($trigger);
        $campaign->label = sprintf('SimpleSms API (%s)', $this->getUser()->getUserIdentifier());

        $entity = $this->campaignManager->launchNewCampaign($campaign, null, $sender);

        return new SimpleMessageResponseFacade(
            count(($entity->getCommunications()[0] ?? [])->getMessages()),
            sprintf('%s%s', getenv('WEBSITE_URL'), $this->generateUrl('communication_index', ['id' => $entity->getId()]))
        );
    }
}