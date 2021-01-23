<?php

namespace Bundles\ApiBundle\Annotation;

/**
 * Facades can be used to replace standard Symfony responses,
 * in order to be serialized in a response listener. Thus,
 * this annotation can be used to change the used HTTP response
 * status code.
 *
 * @Annotation
 * @Target({"CLASS"})
 */
final class StatusCode
{
    /**
     * @Required
     * @Enum({
     *     \Symfony\Component\HttpFoundation\Response::HTTP_CONTINUE,
     *     \Symfony\Component\HttpFoundation\Response::HTTP_SWITCHING_PROTOCOLS,
     *     \Symfony\Component\HttpFoundation\Response::HTTP_PROCESSING,
     *     \Symfony\Component\HttpFoundation\Response::HTTP_EARLY_HINTS,
     *     \Symfony\Component\HttpFoundation\Response::HTTP_OK,
     *     \Symfony\Component\HttpFoundation\Response::HTTP_CREATED,
     *     \Symfony\Component\HttpFoundation\Response::HTTP_ACCEPTED,
     *     \Symfony\Component\HttpFoundation\Response::HTTP_NON_AUTHORITATIVE_INFORMATION,
     *     \Symfony\Component\HttpFoundation\Response::HTTP_NO_CONTENT,
     *     \Symfony\Component\HttpFoundation\Response::HTTP_RESET_CONTENT,
     *     \Symfony\Component\HttpFoundation\Response::HTTP_PARTIAL_CONTENT,
     *     \Symfony\Component\HttpFoundation\Response::HTTP_MULTI_STATUS,
     *     \Symfony\Component\HttpFoundation\Response::HTTP_ALREADY_REPORTED,
     *     \Symfony\Component\HttpFoundation\Response::HTTP_IM_USED,
     *     \Symfony\Component\HttpFoundation\Response::HTTP_MULTIPLE_CHOICES,
     *     \Symfony\Component\HttpFoundation\Response::HTTP_MOVED_PERMANENTLY,
     *     \Symfony\Component\HttpFoundation\Response::HTTP_FOUND,
     *     \Symfony\Component\HttpFoundation\Response::HTTP_SEE_OTHER,
     *     \Symfony\Component\HttpFoundation\Response::HTTP_NOT_MODIFIED,
     *     \Symfony\Component\HttpFoundation\Response::HTTP_USE_PROXY,
     *     \Symfony\Component\HttpFoundation\Response::HTTP_RESERVED,
     *     \Symfony\Component\HttpFoundation\Response::HTTP_TEMPORARY_REDIRECT,
     *     \Symfony\Component\HttpFoundation\Response::HTTP_PERMANENTLY_REDIRECT,
     *     \Symfony\Component\HttpFoundation\Response::HTTP_BAD_REQUEST,
     *     \Symfony\Component\HttpFoundation\Response::HTTP_UNAUTHORIZED,
     *     \Symfony\Component\HttpFoundation\Response::HTTP_PAYMENT_REQUIRED,
     *     \Symfony\Component\HttpFoundation\Response::HTTP_FORBIDDEN,
     *     \Symfony\Component\HttpFoundation\Response::HTTP_NOT_FOUND,
     *     \Symfony\Component\HttpFoundation\Response::HTTP_METHOD_NOT_ALLOWED,
     *     \Symfony\Component\HttpFoundation\Response::HTTP_NOT_ACCEPTABLE,
     *     \Symfony\Component\HttpFoundation\Response::HTTP_PROXY_AUTHENTICATION_REQUIRED,
     *     \Symfony\Component\HttpFoundation\Response::HTTP_REQUEST_TIMEOUT,
     *     \Symfony\Component\HttpFoundation\Response::HTTP_CONFLICT,
     *     \Symfony\Component\HttpFoundation\Response::HTTP_GONE,
     *     \Symfony\Component\HttpFoundation\Response::HTTP_LENGTH_REQUIRED,
     *     \Symfony\Component\HttpFoundation\Response::HTTP_PRECONDITION_FAILED,
     *     \Symfony\Component\HttpFoundation\Response::HTTP_REQUEST_ENTITY_TOO_LARGE,
     *     \Symfony\Component\HttpFoundation\Response::HTTP_REQUEST_URI_TOO_LONG,
     *     \Symfony\Component\HttpFoundation\Response::HTTP_UNSUPPORTED_MEDIA_TYPE,
     *     \Symfony\Component\HttpFoundation\Response::HTTP_REQUESTED_RANGE_NOT_SATISFIABLE,
     *     \Symfony\Component\HttpFoundation\Response::HTTP_EXPECTATION_FAILED,
     *     \Symfony\Component\HttpFoundation\Response::HTTP_I_AM_A_TEAPOT,
     *     \Symfony\Component\HttpFoundation\Response::HTTP_MISDIRECTED_REQUEST,
     *     \Symfony\Component\HttpFoundation\Response::HTTP_UNPROCESSABLE_ENTITY,
     *     \Symfony\Component\HttpFoundation\Response::HTTP_LOCKED,
     *     \Symfony\Component\HttpFoundation\Response::HTTP_FAILED_DEPENDENCY
     * })
     */
    public $value;
}