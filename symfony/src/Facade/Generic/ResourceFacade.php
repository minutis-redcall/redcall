<?php

namespace App\Facade\Generic;

use Bundles\ApiBundle\Annotation as Api;
use Bundles\ApiBundle\Contracts\FacadeInterface;

class ResourceFacade implements FacadeInterface
{
    const TYPE_VOLUNTEER = 'volunteer';
    const TYPE_STRUCTURE = 'structure';
    const TYPE_BADGE     = 'badge';
    const TYPE_CATEGORY  = 'category';

    /**
     * Resource type ({VOLUNTEER}, {STRUCTURE}, {BADGE}, {CATEGORY})
     *
     * @Api\Placeholder("{VOLUNTEER}", replaceBy=ResourceFacade::TYPE_VOLUNTEER)
     * @Api\Placeholder("{STRUCTURE}", replaceBy=ResourceFacade::TYPE_STRUCTURE)
     * @Api\Placeholder("{BADGE}", replaceBy=ResourceFacade::TYPE_BADGE)
     * @Api\Placeholder("{CATEGORY}", replaceBy=ResourceFacade::TYPE_CATEGORY)
     *
     * @var string
     */
    private $type;

    /**
     * Resource's external id
     *
     * @var string
     */
    private $externalId;

    /**
     * Human-readable resource name
     *
     * @var string
     */
    private $label;

    static public function getExample(Api\Facade $decorates = null) : FacadeInterface
    {
        $facade = new self;

        $facade->type       = self::TYPE_STRUCTURE;
        $facade->externalId = '1234';
        $facade->label      = 'DT DE PARIS';

        return $facade;
    }

    public function getType() : string
    {
        return $this->type;
    }

    public function setType(string $type) : ResourceFacade
    {
        $this->type = $type;

        return $this;
    }

    public function getExternalId() : string
    {
        return $this->externalId;
    }

    public function setExternalId(string $externalId) : ResourceFacade
    {
        $this->externalId = $externalId;

        return $this;
    }

    public function getLabel() : string
    {
        return $this->label;
    }

    public function setLabel(string $label) : ResourceFacade
    {
        $this->label = $label;

        return $this;
    }
}