<?php

namespace Bundles\ApiBundle\Model\Documentation;

use Ramsey\Uuid\Uuid;

class FacadeDescription
{
    const TYPE_REQUEST  = 'request';
    const TYPE_RESPONSE = 'response';

    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string|null
     */
    private $description;

    /**
     * @var PropertyDescription[]
     */
    private $properties = [];

    /**
     * @var mixed
     */
    private $example;

    /**
     * @var int
     */
    private $statusCode;

    public function __construct()
    {
        $this->id = Uuid::uuid4();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getTitle() : string
    {
        return $this->title;
    }

    public function setTitle(string $title) : FacadeDescription
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription() : ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description) : FacadeDescription
    {
        $this->description = $description;

        return $this;
    }

    public function getProperties() : PropertyCollectionDescription
    {
        return $this->properties;
    }

    public function setProperties(PropertyCollectionDescription $properties) : FacadeDescription
    {
        $this->properties = $properties;

        return $this;
    }

    public function getExample()
    {
        return $this->example;
    }

    public function setExample($example) : FacadeDescription
    {
        $this->example = $example;

        return $this;
    }

    public function getFormattedExample(string $method, bool $isRequest, bool $html = false) : ?string
    {
        if (!$this->example) {
            return null;
        }

        if ('GET' === $method && $isRequest) {
            return sprintf('?%s', http_build_query($this->example));
        }

        $json = json_encode($this->example, JSON_PRETTY_PRINT);
        if (!$html) {
            return $json;
        }

        $json = str_replace(["\n", ' ', "\t"], ['<br/>', '&nbsp;', '&nbsp;'], $json);

        return $json;
    }

    public function getStatusCode() : int
    {
        return $this->statusCode;
    }

    public function setStatusCode(int $statusCode) : FacadeDescription
    {
        $this->statusCode = $statusCode;

        return $this;
    }
}