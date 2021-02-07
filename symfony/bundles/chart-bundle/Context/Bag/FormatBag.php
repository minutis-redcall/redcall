<?php

namespace Bundles\ChartBundle\Context\Bag;

use Bundles\ChartBundle\Context\Format\FormatInterface;

class FormatBag
{
    private $formats = [];

    public function addFormat(FormatInterface $format)
    {
        $this->formats[$format->getName()] = $format;
    }

    public function getFormat(string $name) : FormatInterface
    {
        if (!array_key_exists($name, $this->formats)) {
            throw new \LogicException(sprintf('Variable type %s does not exist', $name));
        }

        return $this->formats[$name];
    }

    /**
     * @return FormatInterface[]
     */
    public function getFormats() : array
    {
        return $this->formats;
    }
}
