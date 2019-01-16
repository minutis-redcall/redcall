<?php

namespace App\Communication\Processor;

use App\Entity\Communication;

interface ProcessorInterface
{
    /**
     * @param Communication $communication
     */
    public function process(Communication $communication);
}