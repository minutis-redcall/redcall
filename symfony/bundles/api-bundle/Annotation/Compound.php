<?php

namespace Bundles\ApiBundle\Annotation;

/**
 * A facade is compound when it decorates another facade.
 *
 * For example:
 * - a CollectionFacade is an array of a child facade
 * - a SuccessFacade wraps a children facade
 * - etc.
 *
 * @Annotation
 * @Target({"CLASS"})
 */
final class Compound
{

}