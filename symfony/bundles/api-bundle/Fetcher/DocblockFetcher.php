<?php

namespace Bundles\ApiBundle\Fetcher;

use Bundles\ApiBundle\Annotation\Placeholder;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\DocBlockFactoryInterface;

class DocblockFetcher
{
    private $factory;

    public function fetch(\Reflector $reflector, array $annotations) : DocBlock
    {
        $docblock = $reflector->getDocComment();

        if (!$docblock) {
            return new DocBlock();
        }

        foreach ($annotations as $annotation) {
            if ($annotation instanceof Placeholder) {
                $docblock = str_replace($annotation->toReplace, $annotation->replaceBy, $docblock);
            }
        }

        return $this->getFactory()->create($docblock);
    }

    public function getFactory() : DocBlockFactoryInterface
    {
        if ($this->factory) {
            return $this->factory;
        }

        $this->factory = DocBlockFactory::createInstance();

        return $this->factory;
    }
}