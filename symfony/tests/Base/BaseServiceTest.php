<?php

namespace App\Tests\Base;

use App\Base\BaseService;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class BaseServiceTest extends TestCase
{
    public function testSetContainerReturnsNullOnFirstCall(): void
    {
        $service = $this->getMockForAbstractClass(BaseService::class);
        $container = $this->createMock(ContainerInterface::class);

        $previous = $service->setContainer($container);
        $this->assertNull($previous);
    }

    public function testSetContainerReturnsPreviousContainer(): void
    {
        $service = $this->getMockForAbstractClass(BaseService::class);
        $container1 = $this->createMock(ContainerInterface::class);
        $container2 = $this->createMock(ContainerInterface::class);

        $service->setContainer($container1);
        $previous = $service->setContainer($container2);
        $this->assertSame($container1, $previous);
    }

    public function testGetDelegatesToContainer(): void
    {
        $service = $this->getMockForAbstractClass(BaseService::class);

        $mockService = new \stdClass();
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('get')
            ->with('some.service')
            ->willReturn($mockService);

        $service->setContainer($container);
        $result = $service->get('some.service');
        $this->assertSame($mockService, $result);
    }
}
