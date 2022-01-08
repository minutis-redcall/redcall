<?php

namespace App\Form\Model;

use App\Entity\Communication;
use App\Entity\Media;
use App\Entity\Message;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class EmailTrigger extends BaseTrigger
{
    /**
     * @var string|null
     *
     * @Assert\Length(max=80)
     */
    private $subject = null;

    /**
     * @var array
     */
    private $images = [];

    public function __construct()
    {
        parent::__construct();

        $this->setType(Communication::TYPE_EMAIL);
    }

    public function getSubject() : ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject) : self
    {
        $this->subject = $subject;

        return $this;
    }

    public function getImages() : array
    {
        return $this->images;
    }

    public function addImage(Media $media) : self
    {
        $this->images[] = $media;

        return $this;
    }

    /**
     * @Assert\Callback
     */
    public function validate(ExecutionContextInterface $context, $payload)
    {
        parent::validate($context, $payload);

        if (mb_strlen(strip_tags($this->getMessage())) > Message::MAX_LENGTH_EMAIL) {
            $context->buildViolation('form.communication.errors.too_large_sms')
                    ->atPath('message')
                    ->addViolation();
        }

        if (!$this->getSubject()) {
            $context->buildViolation('form.communication.errors.no_subject')
                    ->atPath('subject')
                    ->addViolation();
        }
    }

    public function jsonSerialize()
    {
        $vars = parent::jsonSerialize();

        $vars['images'] = array_map(function (Media $media) {
            return $media->getUuid();
        }, $this->images);

        return $vars;
    }
}