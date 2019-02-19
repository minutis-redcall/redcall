<?php

namespace Bundles\SandboxBundle\Controller;

use App\Base\BaseController;
use App\Entity\Message;
use App\Entity\Volunteer;
use Bundles\SandboxBundle\Entity\FakeSms;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/fake-sms", name="fake_sms_")
 */
class FakeSmsController extends BaseController
{
    /**
     * @Route("/", name="list")
     * @Template()
     */
    public function listAction()
    {
        $phoneNumbers = $this->getManager(FakeSms::class)->findAllPhones();
        foreach ($phoneNumbers as $index => $phoneNumber) {
            $phoneNumbers[$index]['volunteer'] = $this->getManager(Volunteer::class)->findOneByPhoneNumber($phoneNumber['phoneNumber']);
        }

        return [
            'phoneNumbers' => $phoneNumbers,
        ];
    }

    /**
     * @Route("/clear/{csrf}", name="clear")
     * @Template()
     */
    public function clearAction(string $csrf)
    {
        $this->validateCsrfOrThrowNotFoundException('fake_sms', $csrf);

        $this->getManager(FakeSms::class)->truncate();

        return $this->redirectToRoute('sandbox_fake_sms_list');
    }

    /**
     * @Route("/thread/{phoneNumber}", name="thread")
     * @Template()
     */
    public function threadAction(string $phoneNumber)
    {
        $messages = $this->getManager(FakeSms::class)->findMessagesForPhoneNumber($phoneNumber);

        $lastMessageId = null;
        if ($messages) {
            $lastMessageId = end($messages)->getId();
        }

        return [
            'phoneNumber'   => $phoneNumber,
            'volunteer'     => $this->getManager(Volunteer::class)->findOneByPhoneNumber($phoneNumber),
            'messages'      => $messages,
            'lastMessageId' => $lastMessageId,
        ];
    }

    /**
     * @Route("/send/{phoneNumber}/{csrf}", name="send")
     * @Method("POST")
     */
    public function sendAction(Request $request, string $phoneNumber, string $csrf)
    {
        $this->validateCsrfOrThrowNotFoundException('fake_sms', $csrf);

        $body = $request->request->get('message');
        if (!$body) {
            throw $this->createNotFoundException();
        }

        $volunteer = $this->getManager(Volunteer::class)->findOneByPhoneNumber($phoneNumber);
        if (!$volunteer) {
            throw $this->createNotFoundException();
        }

        date_default_timezone_set('UTC');

        /* @var Message $message */
        $message = $this->getManager(Message::class)->getLastMessageSentToPhone($phoneNumber);
        if ($message && $message->getCommunication()->getCampaign()->isActive()) {
            if (!$message->getCommunication()->isMultipleAnswer()) {
                $this->getManager(Message::class)->addAnswer($message, $body);
            } else {
                foreach (array_filter(explode(' ', $body)) as $split) {
                    $this->getManager(Message::class)->addAnswer($message, $split);
                }
            }
        }

        $this->getManager(FakeSms::class)->save($volunteer, $body, FakeSms::DIRECTION_SENT);

        return new Response();
    }

    /**
     * @Route("/poll/{phoneNumber}", name="poll")
     */
    public function pollAction(Request $request, string $phoneNumber)
    {
        $lastMessageId = $request->request->get('lastMessageId');
        $manager       = $this->getManager(FakeSms::class);

        $messages = array_map(function (array $entry) {
            $entry['createdAt'] = $entry['createdAt']->format('d/m/Y H:i');

            return $entry;
        }, $manager->findMessagesHavingIdGreaterThan($phoneNumber, $lastMessageId));

        return new JsonResponse($messages);
    }
}