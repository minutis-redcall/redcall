<?php

namespace Bundles\ApiBundle\ParamConverter;

use Bundles\ApiBundle\Contracts\FacadeInterface;
use Bundles\ApiBundle\Error\ViolationError;
use Bundles\ApiBundle\Exception\ApiException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FacadeParamConverter implements ParamConverterInterface
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    public function __construct(SerializerInterface $serializer, ValidatorInterface $validator)
    {
        $this->serializer = $serializer;
        $this->validator  = $validator;
    }

    public function apply(Request $request, ParamConverter $configuration)
    {
        if (Request::METHOD_GET === $request->getMethod()) {
            $params = [];
            foreach ($request->query->all() as $key => $value) {
                if (is_numeric($value)) {
                    $params[$key] = floatval($value);
                } else {
                    $params[$key] = $value;
                }
            }

            $content = json_encode($params);
        } else {
            $content = $request->getContent();
        }

        try {
            $facade = $this->serializer->deserialize(
                $content,
                $configuration->getClass(),
                'json'
            );
        } catch (ExceptionInterface $e) {
            throw new BadRequestHttpException('Bad request');
        }

        $violations = $this->validator->validate($facade);
        if (count($violations)) {
            throw new ApiException(
                new ViolationError($violations)
            );
        }

        $request->attributes->set(
            $configuration->getName(),
            $facade
        );
    }

    public function supports(ParamConverter $configuration)
    {
        return is_subclass_of($configuration->getClass(), FacadeInterface::class);
    }
}