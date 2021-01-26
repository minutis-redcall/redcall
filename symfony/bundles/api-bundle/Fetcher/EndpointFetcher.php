<?php

namespace Bundles\ApiBundle\Fetcher;

use Bundles\ApiBundle\Model\Documentation\ControllerDescription;
use Bundles\ApiBundle\Model\Documentation\EndpointDescription;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlockFactory;

class EndpointFetcher
{
    /**
     * @var RolesFetcher
     */
    private $rolesFetcher;

    public function __construct(RolesFetcher $rolesFetcher)
    {
        $this->rolesFetcher = $rolesFetcher;
    }

    public function fetch(ControllerDescription $controller) : EndpointDescription
    {
        $endpoint = new EndpointDescription();

        $endpoint->setPriority($controller->getAnnotation()->priority);

        $docblock = $this->extractDocBlock($controller->getClass(), $controller->getMethod());
        $endpoint->setTitle($docblock->getSummary());

        $methods = $controller->getRoute()->getMethods() ?: ['GET'];
        $endpoint->setMethods($methods);

        $uri = sprintf('%s%s', getenv('WEBSITE_URL'), $controller->getRoute()->getPath());
        $endpoint->setUri($uri);

        $this->rolesFetcher->fetch($controller, $endpoint);

        $endpoint->setDescription($docblock->getDescription());

        /*
        ✅ private $title;
        ✅ private $method;
        ✅ private $uri;
        ✅ private $roles = [];
        ✅ private $description;
        private $requestFacade;
        private $responseFacade;
        private $errors = [];
         */

        return $endpoint;
    }

    private function extractDocBlock(string $class, string $method) : DocBlock
    {
        $reflector = new \ReflectionMethod($class, $method);
        $docblock  = $reflector->getDocComment();
        $factory   = DocBlockFactory::createInstance();

        return $factory->create($docblock);
    }
}