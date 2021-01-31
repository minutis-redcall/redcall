<?php

namespace App\Form\Model;

use App\Entity\Media;
use Symfony\Component\Validator\Constraints as Assert;

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
     * @var string|null
     *
     * @Assert\Length(max=80)
     */
    private $subject = null;

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
    private $geoLocation = false;

    /**
     * @var boolean
     */
    private $multipleAnswer = false;

    /**
     * @var array
     */
    private $images = [];

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
     * @return BaseTrigger
     */
    public function setLabel(string $label) : BaseTrigger
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return string
     */
    public function getType() : string
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return BaseTrigger
     */
    public function setType(string $type) : BaseTrigger
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return array
     */
    public function getAudience() : array
    {
        return $this->audience;
    }

    /**
     * @param array $audience
     *
     * @return BaseTrigger
     */
    public function setAudience(array $audience) : BaseTrigger
    {
        $this->audience = $audience;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSubject() : ?string
    {
        return $this->subject;
    }

    /**
     * @param string|null $subject
     *
     * @return BaseTrigger
     */
    public function setSubject(?string $subject) : BaseTrigger
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getMessage() : ?string
    {
        return $this->message;
    }

    /**
     * @param string|null $message
     *
     * @return BaseTrigger
     */
    public function setMessage(?string $message) : BaseTrigger
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return array
     */
    public function getAnswers() : array
    {
        return $this->answers;
    }

    /**
     * @param array $answers
     *
     * @return BaseTrigger
     */
    public function setAnswers(array $answers) : BaseTrigger
    {
        $this->answers = $answers;

        return $this;
    }

    /**
     * @return bool
     */
    public function isGeoLocation() : bool
    {
        return $this->geoLocation;
    }

    /**
     * @param bool $geoLocation
     *
     * @return BaseTrigger
     */
    public function setGeoLocation(bool $geoLocation) : BaseTrigger
    {
        $this->geoLocation = $geoLocation;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMultipleAnswer() : bool
    {
        return $this->multipleAnswer;
    }

    /**
     * @param bool $multipleAnswer
     *
     * @return BaseTrigger
     */
    public function setMultipleAnswer(bool $multipleAnswer) : BaseTrigger
    {
        $this->multipleAnswer = $multipleAnswer;

        return $this;
    }

    public function getImages() : array
    {
        return $this->images;
    }

    public function addImage(Media $media) : EmailTrigger
    {
        $this->images[] = $media;

        return $this;
    }

    public function jsonSerialize()
    {
        $vars = get_object_vars($this);

        $vars['images'] = array_map(function (Media $media) {
            return $media->getUuid();
        }, $this->images);

        return $vars;
    }
}
