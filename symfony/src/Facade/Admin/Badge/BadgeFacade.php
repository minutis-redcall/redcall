<?php

namespace App\Facade\Admin\Badge;

use Bundles\ApiBundle\Annotation\Facade;
use Bundles\ApiBundle\Contracts\FacadeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class BadgeFacade implements FacadeInterface
{
    /**
     * An unique identifier for the badge.
     * You can use a random UUID or the same identifier as in your own application.
     *
     * @Assert\NotBlank
     * @Assert\Length(max = 64)
     *
     * @var string
     */
    private $externalId;

    /**
     * Badge name.
     * Badge name should be small because it is rendered everywhere where a volunteer is rendered.
     *
     * @Assert\NotBlank
     * @Assert\Length(max = 64)
     *
     * @var string
     */
    private $name;

    /**
     * Badge description.
     * Badge description should help to understand what it is/means
     *
     * @var string|null
     */
    private $description;

    /**
     * @var int
     */
    private $volunteerCount;

    static public function getExample(Facade $decorates = null) : FacadeInterface
    {
        $facade = new self;

        return $facade;
    }


}