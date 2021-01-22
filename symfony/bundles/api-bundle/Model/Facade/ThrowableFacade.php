<?php

namespace Bundles\ApiBundle\Model\Facade;

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

    static public function getExample() : FacadeInterface
    {
        $facade = new self;

        $facade->message = 'This is a sample exception message';
        $facade->trace   = implode("\n", [
            '#0 /Users/alain/Data/developpement/redcall/app/symfony/vendor/symfony/http-kernel/HttpKernel.php(158): App\Controller\HomeController->home()',
            '#1 /Users/alain/Data/developpement/redcall/app/symfony/vendor/symfony/http-kernel/HttpKernel.php(80): Symfony\Component\HttpKernel\HttpKernel->handleRaw(Object(Symfony\Component\HttpFoundation\Request), 1)',
            '#2 /Users/alain/Data/developpement/redcall/app/symfony/vendor/symfony/http-kernel/Kernel.php(201): Symfony\Component\HttpKernel\HttpKernel->handle(Object(Symfony\Component\HttpFoundation\Request), 1, true)',
            '#3 /Users/alain/Data/developpement/redcall/app/symfony/public/index.php(46): Symfony\Component\HttpKernel\Kernel->handle(Object(Symfony\Component\HttpFoundation\Request))',
            '#4 {main}',
        ]);

        return $facade;
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
