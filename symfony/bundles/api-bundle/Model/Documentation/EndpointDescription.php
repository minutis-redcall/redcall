<?php

namespace Bundles\ApiBundle\Model\Documentation;

class EndpointDescription
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var int
     */
    private $priority = 0;

    /**
     * @var string|null
     */
    private $title;

    /**
     * @var string[]
     */
    private $methods;

    /**
     * @var string
     */
    private $uri;

    /**
     * @var RoleDescription[]
     */
    private $roles = [];

    /**
     * @var string|null
     */
    private $description;

    /**
     * @var FacadeDescription|null
     */
    private $requestFacade;

    /**
     * @var FacadeDescription|null
     */
    private $responseFacade;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function getId() : string
    {
        return $this->id;
    }

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

    public function getMethods() : array
    {
        return $this->methods;
    }

    public function setMethods(array $methods) : EndpointDescription
    {
        $this->methods = $methods;

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

    public function getRoles() : array
    {
        return $this->roles;
    }

    public function addRole(RoleDescription $role) : EndpointDescription
    {
        $this->roles[] = $role;

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

    public function getRequestFacade() : ?FacadeDescription
    {
        return $this->requestFacade;
    }

    public function setRequestFacade(?FacadeDescription $requestFacade) : EndpointDescription
    {
        $this->requestFacade = $requestFacade;

        return $this;
    }

    public function getResponseFacade() : ?FacadeDescription
    {
        return $this->responseFacade;
    }

    public function setResponseFacade(?FacadeDescription $responseFacade) : EndpointDescription
    {
        $this->responseFacade = $responseFacade;

        return $this;
    }
}
