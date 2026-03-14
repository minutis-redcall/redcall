<?php

namespace App\Tests\ParamConverter;

use App\Enum\Type;
use App\ParamConverter\EnumParamConverter;
use MyCLabs\Enum\Enum;
use PHPUnit\Framework\TestCase;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EnumParamConverterTest extends TestCase
{
    private $converter;

    protected function setUp() : void
    {
        $this->converter = new EnumParamConverter();
    }

    public function testSupportsReturnsTrueForEnumSubclass()
    {
        $config = $this->createMock(ParamConverter::class);
        $config->method('getClass')->willReturn(Type::class);

        $this->assertTrue($this->converter->supports($config));
    }

    public function testSupportsReturnsFalseForNonEnumClass()
    {
        $config = $this->createMock(ParamConverter::class);
        $config->method('getClass')->willReturn(\stdClass::class);

        $this->assertFalse($this->converter->supports($config));
    }

    public function testSupportsReturnsFalseForNullClass()
    {
        $config = $this->createMock(ParamConverter::class);
        $config->method('getClass')->willReturn(null);

        $this->assertFalse($this->converter->supports($config));
    }

    public function testApplyReturnsFalseWhenAttributeNotPresent()
    {
        $request = new Request();
        // No attributes set

        $config = $this->createMock(ParamConverter::class);
        $config->method('getName')->willReturn('type');
        $config->method('getClass')->willReturn(Type::class);

        $result = $this->converter->apply($request, $config);

        $this->assertFalse($result);
    }

    public function testApplyReturnsFalseForEmptyOptionalParam()
    {
        $request = new Request();
        $request->attributes->set('type', '');

        $config = $this->createMock(ParamConverter::class);
        $config->method('getName')->willReturn('type');
        $config->method('getClass')->willReturn(Type::class);
        $config->method('isOptional')->willReturn(true);

        $result = $this->converter->apply($request, $config);

        $this->assertFalse($result);
    }

    public function testApplyThrowsNotFoundForInvalidValue()
    {
        $this->expectException(NotFoundHttpException::class);

        $request = new Request();
        $request->attributes->set('type', 'invalid_value');

        $config = $this->createMock(ParamConverter::class);
        $config->method('getName')->willReturn('type');
        $config->method('getClass')->willReturn(Type::class);
        $config->method('isOptional')->willReturn(false);

        $this->converter->apply($request, $config);
    }

    public function testApplySetsEnumOnRequestForValidValue()
    {
        $request = new Request();
        $request->attributes->set('type', 'sms');

        $config = $this->createMock(ParamConverter::class);
        $config->method('getName')->willReturn('type');
        $config->method('getClass')->willReturn(Type::class);
        $config->method('isOptional')->willReturn(false);

        $result = $this->converter->apply($request, $config);

        $this->assertTrue($result);

        $attribute = $request->attributes->get('type');
        $this->assertInstanceOf(Type::class, $attribute);
        $this->assertTrue($attribute->equals(Type::SMS()));
    }

    public function testApplyWorksWithCallType()
    {
        $request = new Request();
        $request->attributes->set('type', 'call');

        $config = $this->createMock(ParamConverter::class);
        $config->method('getName')->willReturn('type');
        $config->method('getClass')->willReturn(Type::class);
        $config->method('isOptional')->willReturn(false);

        $result = $this->converter->apply($request, $config);

        $this->assertTrue($result);

        $attribute = $request->attributes->get('type');
        $this->assertInstanceOf(Type::class, $attribute);
        $this->assertTrue($attribute->equals(Type::CALL()));
    }

    public function testApplyWorksWithEmailType()
    {
        $request = new Request();
        $request->attributes->set('type', 'email');

        $config = $this->createMock(ParamConverter::class);
        $config->method('getName')->willReturn('type');
        $config->method('getClass')->willReturn(Type::class);
        $config->method('isOptional')->willReturn(false);

        $result = $this->converter->apply($request, $config);

        $this->assertTrue($result);

        $attribute = $request->attributes->get('type');
        $this->assertInstanceOf(Type::class, $attribute);
        $this->assertTrue($attribute->equals(Type::EMAIL()));
    }

    public function testSupportsReturnsTrueForBaseEnumClass()
    {
        // Enum itself is abstract, but isSubclassOf checks for inheritance
        $config = $this->createMock(ParamConverter::class);
        $config->method('getClass')->willReturn(Enum::class);

        // Enum::class is not a subclass of itself
        $this->assertFalse($this->converter->supports($config));
    }
}
