<?php

namespace Bundles\ApiBundle\Model\Facade;

use Bundles\ApiBundle\Annotation\Facade;
use Bundles\ApiBundle\Contracts\FacadeInterface;

class ThrowableFacade implements FacadeInterface
{
    /**
     * @var string
     */
    private $message;

    /**
     * @var string
     */
    private $trace;

    /**
     * @var ThrowableFacade|null
     */
    private $previous;

    public function __construct(\Throwable $throwable)
    {
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
            return new self($e);
        }
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
