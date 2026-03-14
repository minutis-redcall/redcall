<?php

namespace App\Tests\Base;

use App\Base\BaseController;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class BaseControllerTest extends TestCase
{
    // --- orderBy ---

    public function testOrderByThrowsExceptionIfNoDotsInColumn(): void
    {
        $request = new Request();
        $requestStack = new RequestStack();
        $requestStack->push($request);
        $controller = $this->createControllerWithRequestStack($requestStack);

        $qb = $this->createMock(QueryBuilder::class);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Invalid format');

        $controller->orderBy($qb, \stdClass::class, 'noDotColumn');
    }

    public function testOrderByThrowsExceptionIfClassNotFound(): void
    {
        $request = new Request();
        $requestStack = new RequestStack();
        $requestStack->push($request);
        $controller = $this->createControllerWithRequestStack($requestStack);

        $qb = $this->createMock(QueryBuilder::class);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('not found');

        $controller->orderBy($qb, 'NonExistentClass', 'e.id');
    }

    public function testOrderByUsesDefaultColumnAndDirection(): void
    {
        $request = new Request();
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $controller = $this->createControllerWithRequestStack($requestStack);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())
            ->method('orderBy')
            ->with('e.id', 'ASC');

        // Using a real class that has an 'id' property
        $result = $controller->orderBy($qb, TestEntityStub::class, 'e.id');

        $this->assertSame('', $result['prefix']);
        $this->assertSame('id', $result['column']);
        $this->assertSame('ASC', $result['direction']);
    }

    public function testOrderByUsesRequestParameters(): void
    {
        $request = new Request(['order-by' => 'name', 'order-by-direction' => 'DESC']);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $controller = $this->createControllerWithRequestStack($requestStack);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())
            ->method('orderBy')
            ->with('e.name', 'DESC');

        $result = $controller->orderBy($qb, TestEntityStub::class, 'e.id');

        $this->assertSame('name', $result['column']);
        $this->assertSame('DESC', $result['direction']);
    }

    public function testOrderByFallsBackToDefaultOnInvalidColumn(): void
    {
        $request = new Request(['order-by' => 'nonexistent']);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $controller = $this->createControllerWithRequestStack($requestStack);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())
            ->method('orderBy')
            ->with('e.id', 'ASC');

        $result = $controller->orderBy($qb, TestEntityStub::class, 'e.id');
        $this->assertSame('id', $result['column']);
    }

    public function testOrderByFallsBackToDefaultOnInvalidDirection(): void
    {
        $request = new Request(['order-by-direction' => 'INVALID']);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $controller = $this->createControllerWithRequestStack($requestStack);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())
            ->method('orderBy')
            ->with('e.id', 'ASC');

        $result = $controller->orderBy($qb, TestEntityStub::class, 'e.id');
        $this->assertSame('ASC', $result['direction']);
    }

    public function testOrderByWithPrefix(): void
    {
        $request = new Request(['my_order-by' => 'name', 'my_order-by-direction' => 'DESC']);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $controller = $this->createControllerWithRequestStack($requestStack);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())
            ->method('orderBy')
            ->with('e.name', 'DESC');

        $result = $controller->orderBy($qb, TestEntityStub::class, 'e.id', 'ASC', 'my_');

        $this->assertSame('my_', $result['prefix']);
        $this->assertSame('name', $result['column']);
        $this->assertSame('DESC', $result['direction']);
    }

    public function testOrderByWithDefaultDirectionDesc(): void
    {
        $request = new Request();
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $controller = $this->createControllerWithRequestStack($requestStack);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())
            ->method('orderBy')
            ->with('e.id', 'DESC');

        $result = $controller->orderBy($qb, TestEntityStub::class, 'e.id', 'DESC');
        $this->assertSame('DESC', $result['direction']);
    }

    // --- createNamedFormBuilder ---

    public function testCreateNamedFormBuilder(): void
    {
        $formBuilder = $this->createMock(FormBuilderInterface::class);
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->expects($this->once())
            ->method('createNamedBuilder')
            ->with('my_form', 'SomeType', null, ['option' => 'value'])
            ->willReturn($formBuilder);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->with('form.factory')
            ->willReturn($formFactory);
        $container->method('has')
            ->willReturn(true);

        $controller = new BaseController();
        $controller->setContainer($container);

        $result = $controller->createNamedFormBuilder('my_form', 'SomeType', null, ['option' => 'value']);
        $this->assertSame($formBuilder, $result);
    }

    // --- Helper methods ---

    private function createController(): BaseController
    {
        return new BaseController();
    }

    private function createControllerWithRequestStack(RequestStack $requestStack): BaseController
    {
        $controller = new BaseController();

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(function ($id) use ($requestStack) {
                if ($id === 'request_stack') {
                    return $requestStack;
                }
                return null;
            });
        $container->method('has')
            ->willReturn(true);

        $controller->setContainer($container);

        return $controller;
    }
}

/**
 * Stub class used in orderBy tests to check property_exists.
 */
class TestEntityStub
{
    public $id;
    public $name;
    public $createdAt;
}
