<?php

namespace App\Controller;

use App\Security\CronTokenVerifier;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: "cron")]
class CronController extends AbstractController
{
    private const CRONS = [
        'user:cron',
        'sync:data',
        'twilio:price',
        'clear:campaign',
        'clear:media',
        'clear:space',
        'clear:expirable',
        'report:communication',
        'import:national',
    ];

    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    #[Route("/{key}")]
    public function run(Request $request, string $key, KernelInterface $kernel, CronTokenVerifier $verifier)
    {
        $key = str_replace('-', ':', $key);
        if (!in_array($key, self::CRONS)) {
            throw $this->createNotFoundException();
        }

        if (!$this->isTrustedCronCall($request, $verifier)) {
            if ($this->getUser() && $this->getUser()->isAdmin()) {
                $this->requestStack->getSession()->save();
            } else {
                throw $this->createAccessDeniedException();
            }
        }

        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput(array_merge($request->query->all(), [
            'command' => $key,
        ]));

        $application->run($input, new NullOutput());

        return new Response();
    }

    private function isTrustedCronCall(Request $request, CronTokenVerifier $verifier) : bool
    {
        if ('127.0.0.1' === $request->getClientIp()) {
            return true;
        }

        // On App Engine the front end strips X-Appengine-Cron from external
        // traffic, so the header proves the call comes from GAE Cron. Off
        // GAE (e.g. Cloud Run) it is forgeable and must be ignored.
        if ('true' === $request->headers->get('X-Appengine-Cron') && getenv('GAE_SERVICE')) {
            return true;
        }

        // Cloud Scheduler authenticates with an OIDC identity token.
        $authorization = $request->headers->get('Authorization', '');
        if (0 === strpos($authorization, 'Bearer ')) {
            return $verifier->verify(substr($authorization, 7));
        }

        return false;
    }
}
