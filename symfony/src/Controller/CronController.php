<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="cron")
 */
class CronController extends AbstractController
{
    private const CRONS = [
        'user:cron',
        'pegass:files',
        'twilio:price',
        'clear:campaign',
        'clear:media',
        'clear:space',
        'clear:expirable',
        'clear:volunteer',
        'report:communication',
    ];

    /**
     * @var SessionInterface
     */
    protected $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * @Route("/{key}")
     */
    public function run(Request $request, string $key, KernelInterface $kernel)
    {
        $key = str_replace('-', ':', $key);
        if (!in_array($key, self::CRONS)) {
            throw $this->createNotFoundException();
        }

        if ($request->getClientIp() !== '127.0.0.1'
            && 'true' !== $request->headers->get('X-Appengine-Cron')) {
            if ($this->getUser() && $this->getUser()->isAdmin()) {
                $this->session->save();
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
}
