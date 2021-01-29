<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ChoiceRepository")
 */
class Choice
{
    const MAX_LENGTH_DEFAULT = 255;
    const MAX_LENGTH_SMS     = 16;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=2)
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=self::MAX_LENGTH_DEFAULT)
     */
    private $label;

    /**
     * @var Communication
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Communication", inversedBy="choices")
     */
    private $communication;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getCode() : string
    {
        return $this->code;
    }

    /**
     * @param string $code
     *
     * @return $this
     */
    public function setCode(string $code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return string
     */
    public function getLabel() : string
    {
        return $this->label;
    }

    /**
     * @param string $label
     *
     * @return $this
     */
    public function setLabel(string $label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return Communication
     */
    public function getCommunication() : Communication
    {
        return $this->communication;
    }

    /**
     * @param Communication $communication
     *
     * @return $this
     */
    public function setCommunication(Communication $communication)
    {
        $this->communication = $communication;

        return $this;
    }

    /**
     * @return int
     */
    public function getCount() : int
    {
        $count = 0;

        foreach ($this->getCommunication()->getMessages() as $message) {
            $count += boolval($message->getAnswerByChoice($this));
        }

        return $count;
    }
}
