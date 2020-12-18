<?php

namespace App\Model;

/**
 * This class can be used inside a controller action in
 * order to automatically validate a CSRF token.
 *
 * The name of the CSRF token should match the parameter
 * name in the controller.
 *
 * In a view, use:
 * path('some_route', {myToken: csrf_token('myToken')})
 *
 * In a controller, this will be:
 * public function someAction(Csrf $myToken)
 *
 * @see \App\ParamConverter\CsrfParamConverter
 */
final class Csrf
{
}