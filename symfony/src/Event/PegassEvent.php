<?php

namespace App\Event;

use App\Entity\Pegass;
use Symfony\Contracts\EventDispatcher\Event;

class PegassEvent extends Event
{
    /**
     * @var Pegass
     */
    private $pegass;

    /**
     * @param Pegass $pegass
     */
    public function __construct(Pegass $pegass)
    {
        $this->pegass = $pegass;
    }

    /**
     * @return Pegass
     */
    public function getPegass()
    {
        return $this->pegass;
    }
}