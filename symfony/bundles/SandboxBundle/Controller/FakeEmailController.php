<?php

namespace Bundles\SandboxBundle\Controller;

use App\Base\BaseController;
use App\Entity\Volunteer;
use Bundles\SandboxBundle\Entity\FakeEmail;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * @Route("/fake-email", name="fake_email_")
 */
class FakeEmailController extends BaseController
{
    /**
     * @Route("/", name="list")
     * @Template()
     */
    public function listAction()
    {
        $emails = $this->getManager(FakeEmail::class)->findAllEmails();
        foreach ($emails as $index => $email) {
            $emails[$index]['volunteer'] = $this->getManager(Volunteer::class)->findOneByEmail($email['email']);
        }

        return [
            'emails' => $emails,
        ];
    }

    /**
     * @Route("/clear/{csrf}", name="clear")
     * @Template()
     */
    public function clearAction(string $csrf)
    {
        $this->validateCsrfOrThrowNotFoundException('fake_email', $csrf);

        $this->getManager(FakeEmail::class)->truncate();

        return $this->redirectToRoute('sandbox_fake_email_list');
    }

    /**
     * @Route("/read/{email}", name="read")
     * @Template()
     */
    public function readAction(string $email)
    {
        $messages = $this->getManager(FakeEmail::class)->findMessagesForEmail($email);

        $lastMessageId = null;
        if ($messages) {
            $lastMessageId = end($messages)->getId();
        }

        return [
            'email'         => $email,
            'volunteer'     => $this->getManager(Volunteer::class)->findOneByEmail($email),
            'messages'      => $messages,
            'lastMessageId' => $lastMessageId,
        ];
    }
}