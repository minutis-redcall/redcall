<?php

namespace App\Tests\Twig;

use App\Twig\Extension\AppExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtensionTest extends TestCase
{
    private $extension;

    protected function setUp() : void
    {
        $requestStack = $this->createMock(RequestStack::class);
        $this->extension = new AppExtension($requestStack);
    }

    public function testGetFunctionsReturnsArray()
    {
        $functions = $this->extension->getFunctions();

        $this->assertIsArray($functions);
        $this->assertNotEmpty($functions);

        foreach ($functions as $function) {
            $this->assertInstanceOf(TwigFunction::class, $function);
        }
    }

    public function testGetFunctionsContainsExpectedNames()
    {
        $functions = $this->extension->getFunctions();
        $names = array_map(function (TwigFunction $f) {
            return $f->getName();
        }, $functions);

        $this->assertContains('http_build_query', $names);
        $this->assertContains('random', $names);
        $this->assertContains('uuid', $names);
        $this->assertContains('intval', $names);
    }

    public function testGetFiltersReturnsArray()
    {
        $filters = $this->extension->getFilters();

        $this->assertIsArray($filters);
        $this->assertNotEmpty($filters);

        foreach ($filters as $filter) {
            $this->assertInstanceOf(TwigFilter::class, $filter);
        }
    }

    public function testGetFiltersContainsSnake()
    {
        $filters = $this->extension->getFilters();
        $names = array_map(function (TwigFilter $f) {
            return $f->getName();
        }, $filters);

        $this->assertContains('snake', $names);
    }

    public function testUuidReturnsValidUuid()
    {
        $uuid = $this->extension->uuid();

        $this->assertIsString($uuid);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $uuid
        );
    }

    public function testUuidGeneratesUniqueValues()
    {
        $uuid1 = $this->extension->uuid();
        $uuid2 = $this->extension->uuid();

        $this->assertNotSame($uuid1, $uuid2);
    }

    public function testRandomReturnsStringOfDefaultSize()
    {
        $result = $this->extension->random();

        $this->assertIsString($result);
        $this->assertSame(16, strlen($result));
    }

    public function testRandomReturnsStringOfCustomSize()
    {
        $result = $this->extension->random(32);

        $this->assertSame(32, strlen($result));
    }

    public function testRandomReturnsAlphanumericCharacters()
    {
        $result = $this->extension->random(100);

        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]+$/', $result);
    }

    public function testSnakeConvertsSimpleCamelCase()
    {
        $this->assertSame('hello_world', $this->extension->snake('helloWorld'));
    }

    public function testSnakeConvertsMultipleWords()
    {
        $this->assertSame('my_long_variable_name', $this->extension->snake('myLongVariableName'));
    }

    public function testSnakeHandlesSingleWord()
    {
        $this->assertSame('hello', $this->extension->snake('hello'));
    }

    public function testSnakeHandlesAlreadySnakeCase()
    {
        $this->assertSame('already_snake', $this->extension->snake('already_snake'));
    }

    public function testSnakeConvertsFromPascalCase()
    {
        // The regex (?<!^) prevents matching at the start, so "H" is not prefixed with "_"
        // strtolower then lowercases everything
        $this->assertSame('hello_world', $this->extension->snake('HelloWorld'));
    }

    public function testGetNameReturnsApp()
    {
        $this->assertSame('app', $this->extension->getName());
    }
}
