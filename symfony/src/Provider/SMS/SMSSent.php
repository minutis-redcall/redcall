<?php

namespace App\Provider\SMS;

class SMSSent
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var float
     */
    private $cost;

    /**
     * @param string $id
     * @param float  $cost
     */
    public function __construct(string $id, float $cost)
    {
        $this->id   = $id;
        $this->cost = $cost;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return float
     */
    public function getCost(): float
    {
        return $this->cost;
    }
}