<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ChoiceRepository")
 */
class Choice
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
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
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * @param int $code
     *
     * @return $this
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @param string $label
     *
     * @return $this
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return Communication
     */
    public function getCommunication(): Communication
    {
        return $this->communication;
    }

    /**
     * @param Communication $communication
     *
     * @return $this
     */
    public function setCommunication($communication)
    {
        $this->communication = $communication;

        return $this;
    }

    /**
     * @param array $answers
     *
     * @return bool
     */
    public function hasAnswered(array $answers): bool
    {
        return in_array(strtolower($this->label), array_map('strtolower', array_map('trim', explode('|', $answers))));
    }

    /**
     * @return int
     */
    public function getCount(): int
    {
        $count = 0;

        foreach ($this->getCommunication()->getMessages() as $message) {
            $count += boolval($message->getAnswerByChoice($this));
        }

        return $count;
    }
}
