<?php

namespace App\Model;

class MinutisId
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $publicId;

    public function __construct(int $id, string $publicId)
    {
        $this->id       = $id;
        $this->publicId = $publicId;
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getPublicId() : string
    {
        return $this->publicId;
    }
}
