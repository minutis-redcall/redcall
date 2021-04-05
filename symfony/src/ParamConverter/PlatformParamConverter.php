<?php

namespace App\ParamConverter;

use App\Manager\PlatformConfigManager;
use App\Model\PlatformConfig;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;

class PlatformParamConverter implements ParamConverterInterface
{
    /**
     * @var PlatformConfigManager
     */
    private $platformConfigManager;

    public function __construct(PlatformConfigManager $platformConfigManager)
    {
        $this->platformConfigManager = $platformConfigManager;
    }

    public function apply(Request $request, ParamConverter $configuration)
    {
        $param = $configuration->getName();

        if (!$request->attributes->has($param)) {
            return false;
        }

        $value = $request->attributes->get($param);

        if (!$value && $configuration->isOptional()) {
            return false;
        }

        $platform = $this->platformConfigManager->getPlaform($value);

        $request->attributes->set($param, $platform);

        return true;
    }

    public function supports(ParamConverter $configuration)
    {
        return PlatformConfig::class === $configuration->getClass();
    }
}