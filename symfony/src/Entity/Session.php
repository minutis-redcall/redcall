<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="sessions")
 */
class Session
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(name="sess_id", type="integer")
     */
    private $id;

    /**
     * @ORM\Column(name="sess_data",type="blob")
     */
    private $data;

    /**
     * @ORM\Column(name="sess_time", type="integer")
     */
    private $time;

    /**
     * @ORM\Column(name="sess_lifetime", type="integer")
     */
    private $lifetime;

    public function getId() : ?int
    {
        return $this->id;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($data) : self
    {
        $this->data = $data;

        return $this;
    }

    public function getTime() : ?int
    {
        return $this->time;
    }

    public function setTime(int $time) : self
    {
        $this->time = $time;

        return $this;
    }

    public function getLifetime() : ?int
    {
        return $this->lifetime;
    }

    public function setLifetime(int $lifetime) : self
    {
        $this->lifetime = $lifetime;

        return $this;
    }
}
