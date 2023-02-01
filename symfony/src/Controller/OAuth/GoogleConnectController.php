<?php

namespace App\Controller\OAuth;

use App\Base\BaseController;
use App\Provider\OAuth\GoogleConnect\GoogleConnectInterface;
use App\Tools\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class GoogleConnectController extends BaseController
{
    /**
     * @var GoogleConnectInterface
     */
    private $googleConnect;

    public function __construct(GoogleConnectInterface $googleConnect)
    {
        $this->googleConnect = $googleConnect;
    }

    /**
     * @Route(path="/google-connect", name="google_connect")
     */
    public function connect(Request $request)
    {
        // Need to generate the CSRF token on the right domain name
        $currentUrl    = sprintf('%s://%s', $request->getScheme(), $request->getHost());
        $configuredUrl = Url::getAbsolute($this->generateUrl('google_connect'));
        if (!str_starts_with($configuredUrl, $currentUrl)) {
            return new RedirectResponse($configuredUrl);
        }

        return new RedirectResponse(
            $this->googleConnect->getAuthorizationUri(
                Url::getAbsolute($this->generateUrl('home'))
            )
        );
    }

    /**
     * @Route(path="/google-verify", name="google_verify")
     */
    public function verify()
    {
        return $this->redirectToRoute('home');
    }
}