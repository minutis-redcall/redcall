<?php

namespace App\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

abstract class BaseTrigger implements \JsonSerializable
{
    /**
     * @var string
     *
     * @Assert\Length(max=255, groups={"label_edition"})
     */
    private $label;

    /**
     * @var string
     *
     * @Assert\NotNull
     */
    private $type;

    /**
     * @var array
     *
     * @Assert\NotNull
     * @Assert\Count(min=1, minMessage="form.campaign.errors.volunteers.min")
     */
    private $audience = [];

    /**
     * @var string
     *
     * @Assert\NotNull
     */
    private $language;

    /**
     * @var string
     *
     * @Assert\NotNull()
     * @Assert\Length(min=1, minMessage="form.campaign.errors.message.empty")
     */
    private $message;

    /**
     * @var array
     *
     * @Assert\Count(max=9)
     * @Assert\Valid
     */
    private $answers = [];

    /**
     * @var boolean
     */
    private $multipleAnswer = false;

    /**
     * Only used when adding a new communication to an existing campaign
     *
     * @var bool
     */
    private $operation = false;

    /**
     * @var string[]
     */
    private $operationAnswers = [];

    public function getLabel() : string
    {
        return $this->label;
    }

    public function setLabel(string $label) : BaseTrigger
    {
        $this->label = $label;

        return $this;
    }

    public function getType() : string
    {
        return $this->type;
    }

    public function setType(string $type) : BaseTrigger
    {
        $this->type = $type;

        return $this;
    }

    public function getAudience() : array
    {
        return $this->audience;
    }

    public function setAudience(array $audience) : BaseTrigger
    {
        $this->audience = $audience;

        return $this;
    }

    public function getLanguage() : string
    {
        return $this->language;
    }

    public function setLanguage(string $language) : BaseTrigger
    {
        $this->language = $language;

        return $this;
    }

    public function getMessage() : ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message) : BaseTrigger
    {
        $this->message = $message;

        return $this;
    }

    public function getAnswers() : array
    {
        return $this->answers;
    }

    public function setAnswers(array $answers) : BaseTrigger
    {
        $this->answers = $answers;

        return $this;
    }

    public function isMultipleAnswer() : bool
    {
        return $this->multipleAnswer;
    }

    public function setMultipleAnswer(bool $multipleAnswer) : BaseTrigger
    {
        $this->multipleAnswer = $multipleAnswer;

        return $this;
    }

    public function getOperationAnswers() : array
    {
        return $this->operationAnswers;
    }

    public function addOperationAnswer(string $answer) : self
    {
        $this->operationAnswers[] = $answer;

        return $this;
    }

    public function removeOperationAnswer(string $answer) : self
    {
        $index = array_search($answer, $this->operationAnswers);

        if (false !== $index) {
            unset($this->operationAnswers[$index]);
        }
    }

    /**
     * @return bool
     */
    public function isOperation() : bool
    {
        return $this->operation;
    }

    /**
     * @param bool $operation
     *
     * @return BaseTrigger
     */
    public function setOperation(bool $operation) : BaseTrigger
    {
        $this->operation = $operation;

        return $this;
    }

    public function jsonSerialize()
    {
        $vars = get_object_vars($this);

        return $vars;
    }

    public function validate(ExecutionContextInterface $context, $payload)
    {
    }
}
