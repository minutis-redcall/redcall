<?php

namespace Bundles\ApiBundle\Error;

use Bundles\ApiBundle\Contracts\ErrorInterface;
use Bundles\ApiBundle\Contracts\FacadeInterface;
use Bundles\ApiBundle\Model\Facade\EmptyFacade;
use MyCLabs\Enum\Enum;
use Symfony\Component\HttpFoundation\Response;

/**
 * @method static $this AUTHENTICATION_NO_AUTHORIZATION
 * @method static $this AUTHENTICATION_NO_TOKEN
 * @method static $this AUTHENTICATION_NO_SIGNATURE
 * @method static $this AUTHENTICATION_FAILED
 * @method static $this AUTHENTICATION_REQUIRED
 */
class AuthenticationError extends Enum implements ErrorInterface
{
    private const AUTHENTICATION_NO_AUTHORIZATION = 'authentication.no_authorization';
    private const AUTHENTICATION_NO_TOKEN         = 'authentication.no_token';
    private const AUTHENTICATION_NO_SIGNATURE     = 'authentication.no_signature';
    private const AUTHENTICATION_FAILED           = 'authentication.failed';
    private const AUTHENTICATION_REQUIRED         = 'authentication.required';

    private const KEY_STATUS  = 'http_status';
    private const KEY_CODE    = 'error_code';
    private const KEY_MESSAGE = 'message';

    private const DETAILS = [
        self::AUTHENTICATION_NO_AUTHORIZATION => [
            self::KEY_STATUS  => Response::HTTP_BAD_REQUEST,
            self::KEY_CODE    => 1001,
            self::KEY_MESSAGE => 'Authorization header is missing.',
        ],
        self::AUTHENTICATION_NO_TOKEN         => [
            self::KEY_STATUS  => Response::HTTP_BAD_REQUEST,
            self::KEY_CODE    => 1002,
            self::KEY_MESSAGE => 'Authorization header is invalid.',
        ],
        self::AUTHENTICATION_NO_SIGNATURE     => [
            self::KEY_STATUS  => Response::HTTP_BAD_REQUEST,
            self::KEY_CODE    => 1003,
            self::KEY_MESSAGE => 'X-Signature header is missing.',
        ],
        self::AUTHENTICATION_FAILED           => [
            self::KEY_STATUS  => Response::HTTP_UNAUTHORIZED,
            self::KEY_CODE    => 1004,
            self::KEY_MESSAGE => 'Bad credentials.',
        ],
        self::AUTHENTICATION_REQUIRED         => [
            self::KEY_STATUS  => Response::HTTP_UNAUTHORIZED,
            self::KEY_CODE    => 1005,
            self::KEY_MESSAGE => 'Authentication required.',
        ],
    ];

    public function getStatus() : int
    {
        return self::DETAILS[$this->value][self::KEY_STATUS];
    }

    public function getCode() : string
    {
        return self::DETAILS[$this->value][self::KEY_CODE];
    }

    public function getMessage() : string
    {
        return self::DETAILS[$this->value][self::KEY_MESSAGE];
    }

    public function getContext() : FacadeInterface
    {
        return new EmptyFacade();
    }
}
