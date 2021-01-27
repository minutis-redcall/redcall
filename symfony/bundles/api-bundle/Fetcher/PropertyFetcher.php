<?php

namespace Bundles\ApiBundle\Fetcher;

use Doctrine\Common\Annotations\AnnotationReader;

class PropertyFetcher
{
    /**
     * @var DocblockFetcher
     */
    private $docblockFetcher;

    /**
     * @var AnnotationReader
     */
    private $annotationReader;

    public function __construct(DocblockFetcher $docblockFetcher, AnnotationReader $annotationReader)
    {
        $this->docblockFetcher  = $docblockFetcher;
        $this->annotationReader = $annotationReader;
    }


}