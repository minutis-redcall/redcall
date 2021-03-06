<?php

namespace Bundles\ApiBundle\Model\Facade;

use Bundles\ApiBundle\Annotation\Facade;
use Bundles\ApiBundle\Contracts\FacadeInterface;

class ThrowableFacade implements FacadeInterface
{
    /**
     * The eception type in development environment.
     *
     * @var string
     */
    private $type;

    /**
     * Internal Server Error
     * Or the real exception message in development environment.
     *
     * @var string
     */
    private $message;

    /**
     * The exception stack trace in development environment.
     *
     * @var string
     */
    private $trace;

    /**
     * The previous exception (if any), rendered only in development environment.
     *
     * @var ThrowableFacade|null
     */
    private $previous;

    public function __construct(\Throwable $throwable)
    {
        $this->type    = get_class($throwable);
        $this->message = $throwable->getMessage();
        $this->trace   = $throwable->getTraceAsString();

        if ($throwable->getPrevious()) {
            $this->previous = new self($throwable->getPrevious());
        }
    }

    static public function getExample(Facade $decorates = null) : FacadeInterface
    {
        try {
            throw new \Exception('This is a sample exception');
        } catch (\Exception $e) {
            return new static($e);
        }
    }

    public function getType() : string
    {
        return $this->type;
    }

    public function getMessage() : string
    {
        return $this->message;
    }

    public function getTrace() : string
    {
        return $this->trace;
    }

    public function getPrevious() : ?ThrowableFacade
    {
        return $this->previous;
    }
}
