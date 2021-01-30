<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\MappedSuperclass
 */
abstract class AbstractReport
{
    /**
     * @ORM\Column(type="integer")
     */
    private $messageCount = 0;

    /**
     * @ORM\Column(type="integer")
     */
    private $questionCount = 0;

    /**
     * @ORM\Column(type="integer")
     */
    private $answerCount = 0;

    /**
     * @ORM\Column(type="integer")
     */
    private $exchangeCount = 0;

    /**
     * @ORM\Column(type="integer")
     */
    private $errorCount = 0;

    public function getMessageCount() : int
    {
        return $this->messageCount;
    }

    public function setMessageCount(int $messageCount) : self
    {
        $this->messageCount = $messageCount;

        return $this;
    }

    public function getQuestionCount() : int
    {
        return $this->questionCount;
    }

    public function setQuestionCount(int $questionCount) : self
    {
        $this->questionCount = $questionCount;

        return $this;
    }

    public function getAnswerCount() : int
    {
        return $this->answerCount;
    }

    public function setAnswerCount(int $answerCount) : self
    {
        $this->answerCount = $answerCount;

        return $this;
    }

    public function getExchangeCount() : int
    {
        return $this->exchangeCount;
    }

    public function setExchangeCount(int $exchangeCount) : self
    {
        $this->exchangeCount = $exchangeCount;

        return $this;
    }

    public function getErrorCount() : int
    {
        return $this->errorCount;
    }

    public function setErrorCount(int $errorCount) : AbstractReport
    {
        $this->errorCount = $errorCount;

        return $this;
    }
}