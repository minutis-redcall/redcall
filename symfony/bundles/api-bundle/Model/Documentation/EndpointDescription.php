<?php

namespace Bundles\ApiBundle\Model\Documentation;

use Bundles\ApiBundle\Contracts\FacadeInterface;

class EndpointDescription
{
    /**
     * @var int
     */
    private $priority = 0;

    /**
     * @var string|null
     */
    private $title;

    /**
     * @var string
     */
    private $method;

    /**
     * @var string
     */
    private $uri;

    /**
     * @var string|null
     */
    private $description;

    /**
     * @var FacadeInterface|null
     */
    private $requestFacade;

    /**
     * @var FacadeInterface|null
     */
    private $responseFacade;

    /**
     * @var ErrorDescription[]
     */
    private $errors = [];

    public function getPriority() : int
    {
        return $this->priority;
    }

    public function setPriority(int $priority) : EndpointDescription
    {
        $this->priority = $priority;

        return $this;
    }

    public function getTitle() : ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title) : EndpointDescription
    {
        $this->title = $title;

        return $this;
    }

    public function getMethod() : string
    {
        return $this->method;
    }

    public function setMethod(string $method) : EndpointDescription
    {
        $this->method = $method;

        return $this;
    }

    public function getUri() : string
    {
        return $this->uri;
    }

    public function setUri(string $uri) : EndpointDescription
    {
        $this->uri = $uri;

        return $this;
    }

    public function getDescription() : ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description) : EndpointDescription
    {
        $this->description = $description;

        return $this;
    }

    public function getRequestFacade() : ?FacadeInterface
    {
        return $this->requestFacade;
    }

    public function setRequestFacade(?FacadeInterface $requestFacade) : EndpointDescription
    {
        $this->requestFacade = $requestFacade;

        return $this;
    }

    public function getResponseFacade() : ?FacadeInterface
    {
        return $this->responseFacade;
    }

    public function setResponseFacade(?FacadeInterface $responseFacade) : EndpointDescription
    {
        $this->responseFacade = $responseFacade;

        return $this;
    }

    public function getErrors() : array
    {
        return $this->errors;
    }

    public function setErrors(array $errors) : EndpointDescription
    {
        $this->errors = $errors;

        return $this;
    }
}
