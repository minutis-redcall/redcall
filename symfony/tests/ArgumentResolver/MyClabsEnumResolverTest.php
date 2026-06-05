<?php

namespace App\Tests\ArgumentResolver;

use App\ArgumentResolver\MyClabsEnumResolver;
use App\Enum\Type;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MyClabsEnumResolverTest extends TestCase
{
    private MyClabsEnumResolver $resolver;

    protected function setUp() : void
    {
        $this->resolver = new MyClabsEnumResolver();
    }

    public function testReturnsEmptyForNonEnumType() : void
    {
        $request  = new Request();
        $argument = new ArgumentMetadata('type', \stdClass::class, false, false, null);

        $this->assertSame([], iterator_to_array($this->resolver->resolve($request, $argument)));
    }

    public function testReturnsEmptyForNullType() : void
    {
        $request  = new Request();
        $argument = new ArgumentMetadata('type', null, false, false, null);

        $this->assertSame([], iterator_to_array($this->resolver->resolve($request, $argument)));
    }

    public function testReturnsEmptyWhenAttributeNotPresent() : void
    {
        $request  = new Request();
        $argument = new ArgumentMetadata('type', Type::class, false, false, null);

        $this->assertSame([], iterator_to_array($this->resolver->resolve($request, $argument)));
    }

    public function testReturnsEmptyForEmptyNullableValue() : void
    {
        $request = new Request();
        $request->attributes->set('type', '');
        $argument = new ArgumentMetadata('type', Type::class, false, false, null, true);

        $this->assertSame([], iterator_to_array($this->resolver->resolve($request, $argument)));
    }

    public function testThrowsNotFoundForInvalidValue() : void
    {
        $this->expectException(NotFoundHttpException::class);

        $request = new Request();
        $request->attributes->set('type', 'invalid_value');
        $argument = new ArgumentMetadata('type', Type::class, false, false, null);

        iterator_to_array($this->resolver->resolve($request, $argument));
    }

    public function testResolvesValidValueToEnum() : void
    {
        $request = new Request();
        $request->attributes->set('type', 'sms');
        $argument = new ArgumentMetadata('type', Type::class, false, false, null);

        $resolved = iterator_to_array($this->resolver->resolve($request, $argument));

        $this->assertCount(1, $resolved);
        $this->assertInstanceOf(Type::class, $resolved[0]);
        $this->assertTrue($resolved[0]->equals(Type::SMS()));
    }

    public function testResolvesCallType() : void
    {
        $request = new Request();
        $request->attributes->set('type', 'call');
        $argument = new ArgumentMetadata('type', Type::class, false, false, null);

        $resolved = iterator_to_array($this->resolver->resolve($request, $argument));

        $this->assertInstanceOf(Type::class, $resolved[0]);
        $this->assertTrue($resolved[0]->equals(Type::CALL()));
    }

    public function testResolvesEmailType() : void
    {
        $request = new Request();
        $request->attributes->set('type', 'email');
        $argument = new ArgumentMetadata('type', Type::class, false, false, null);

        $resolved = iterator_to_array($this->resolver->resolve($request, $argument));

        $this->assertInstanceOf(Type::class, $resolved[0]);
        $this->assertTrue($resolved[0]->equals(Type::EMAIL()));
    }
}
