<?php

declare(strict_types = 1);

namespace Lookyman\JsonMapper;

use Lookyman\JsonMapper\Exception\ArrayDoesNotAcceptValueMapperException;
use Lookyman\JsonMapper\Exception\EnumDoesNotAcceptValueMapperException;
use Lookyman\JsonMapper\Exception\InvalidJsonValueMapperException;
use Lookyman\JsonMapper\Exception\JsonStringIsNotAnObjectMapperException;
use Lookyman\JsonMapper\Exception\ParameterDoesNotAcceptValueMapperException;
use Lookyman\JsonMapper\Schema\ArrayOfUnionsParameter;
use Lookyman\JsonMapper\Schema\ArrayParameter;
use Lookyman\JsonMapper\Schema\ArrayShapeParameter;
use Lookyman\JsonMapper\Schema\BooleanParameter;
use Lookyman\JsonMapper\Schema\ClassStringParameter;
use Lookyman\JsonMapper\Schema\EnumParameter;
use Lookyman\JsonMapper\Schema\ExtendedStringParameter;
use Lookyman\JsonMapper\Schema\FloatParameter;
use Lookyman\JsonMapper\Schema\GenericClassStringParameter;
use Lookyman\JsonMapper\Schema\GenericIterableParameter;
use Lookyman\JsonMapper\Schema\GenericObject;
use Lookyman\JsonMapper\Schema\GenericObjectParameter;
use Lookyman\JsonMapper\Schema\IntegerEnum;
use Lookyman\JsonMapper\Schema\IntegerParameter;
use Lookyman\JsonMapper\Schema\LiteralBooleanParameter;
use Lookyman\JsonMapper\Schema\LiteralFloatParameter;
use Lookyman\JsonMapper\Schema\LiteralIntegerParameter;
use Lookyman\JsonMapper\Schema\LiteralStringParameter;
use Lookyman\JsonMapper\Schema\NullableParameter;
use Lookyman\JsonMapper\Schema\ObjectWithoutClassParameter;
use Lookyman\JsonMapper\Schema\StringEnum;
use Lookyman\JsonMapper\Schema\StringParameter;
use Lookyman\JsonMapper\Schema\UnionOfObjectsParameter;
use Lookyman\JsonMapper\Schema\UnionParameter;
use PHPUnit\Framework\TestCase;
use ValueError;
use function json_encode;
use const JSON_THROW_ON_ERROR;

final class MapperTest extends TestCase
{

    private static ?Mapper $defaultMapper = null;

    private function getDefaultMapper(): Mapper
    {
        return self::$defaultMapper ??= (new MapperBuilder())->withCacheDir(__DIR__ . '/..')->build();
    }

    /**
     * @param class-string $class
     * @param object $result
     *
     * @dataProvider mapSuccessProvider
     */
    public function testMapSuccess(string $class, object $json, object $result): void
    {
        self::assertEquals($result, $this->getDefaultMapper()->map($class, json_encode($json, JSON_THROW_ON_ERROR)));
    }

    /**
     * @return iterable<array{class-string, object, object}>
     */
    public function mapSuccessProvider(): iterable
    {
        yield 'nullable parameter' => [
            NullableParameter::class,
            (object) [],
            new NullableParameter(null),
        ];

        yield 'string parameter' => [
            StringParameter::class,
            (object) ['one' => 'foo'],
            new StringParameter('foo'),
        ];

        yield 'literal string parameter' => [
            LiteralStringParameter::class,
            (object) ['one' => 'foo'],
            new LiteralStringParameter('foo'),
        ];

        yield 'integer parameter' => [
            IntegerParameter::class,
            (object) ['one' => 1],
            new IntegerParameter(1),
        ];

        yield 'integer parameter accepts float' => [
            IntegerParameter::class,
            (object) ['one' => 1.0],
            new IntegerParameter(1),
        ];

        yield 'literal integer parameter' => [
            LiteralIntegerParameter::class,
            (object) ['one' => 1],
            new LiteralIntegerParameter(1),
        ];

        yield 'float parameter' => [
            FloatParameter::class,
            (object) ['one' => 1.5],
            new FloatParameter(1.5),
        ];

        yield 'float parameter accepts integer' => [
            FloatParameter::class,
            (object) ['one' => 1],
            new FloatParameter(1.0),
        ];

        yield 'literal float parameter' => [
            LiteralFloatParameter::class,
            (object) ['one' => 1.5],
            new LiteralFloatParameter(1.5),
        ];

        yield 'boolean parameter' => [
            BooleanParameter::class,
            (object) ['one' => true],
            new BooleanParameter(true),
        ];

        yield 'literal boolean parameter' => [
            LiteralBooleanParameter::class,
            (object) ['one' => true],
            new LiteralBooleanParameter(true),
        ];

        yield 'array parameter' => [
            ArrayParameter::class,
            (object) ['one' => ['foo', 1]],
            new ArrayParameter(['foo', 1]),
        ];

        yield 'array with keys parameter' => [
            ArrayParameter::class,
            (object) ['one' => ['foo' => 'bar', 2 => 'baz']],
            new ArrayParameter(['foo' => 'bar', '2' => 'baz']),
        ];

        yield 'array shape parameter' => [
            ArrayShapeParameter::class,
            (object) ['one' => ['foo', 1]],
            new ArrayShapeParameter(['foo', 1]),
        ];

        yield 'object without class' => [
            ObjectWithoutClassParameter::class,
            (object) ['one' => ['two' => 'three']],
            new ObjectWithoutClassParameter((object) ['two' => 'three']),
        ];

        yield 'union parameter first' => [
            UnionParameter::class,
            (object) ['one' => 'foo'],
            new UnionParameter('foo'),
        ];

        yield 'union parameter second' => [
            UnionParameter::class,
            (object) ['one' => ['one' => 'foo']],
            new UnionParameter(new StringParameter('foo')),
        ];

        yield 'union of objects first' => [
            UnionOfObjectsParameter::class,
            (object) ['one' => ['one' => 'foo']],
            new UnionOfObjectsParameter(new StringParameter('foo')),
        ];

        yield 'union of objects second' => [
            UnionOfObjectsParameter::class,
            (object) ['one' => ['one' => 1]],
            new UnionOfObjectsParameter(new IntegerParameter(1)),
        ];

        yield 'array of unions' => [
            ArrayOfUnionsParameter::class,
            (object) ['one' => [['one' => 'foo'], ['one' => 1]]],
            new ArrayOfUnionsParameter([new StringParameter('foo'), new IntegerParameter(1)]),
        ];

        yield 'class string' => [
            ClassStringParameter::class,
            (object) ['one' => ClassStringParameter::class],
            new ClassStringParameter(ClassStringParameter::class),
        ];

        yield 'generic class string' => [
            GenericClassStringParameter::class,
            (object) ['one' => GenericClassStringParameter::class],
            new GenericClassStringParameter(GenericClassStringParameter::class),
        ];

        yield 'generic object parameter' => [
            GenericObjectParameter::class,
            (object) ['one' => ['two' => ['one' => 1], 'one' => ['one' => 'foo']]],
            new GenericObjectParameter(new GenericObject(new ExtendedStringParameter('foo'), new IntegerParameter(1))),
        ];

        yield 'generic iterable parameter' => [
            GenericIterableParameter::class,
            (object) ['one' => ['key1' => ['one' => 'foo'], 'key2' => ['one' => 'bar']]],
            new GenericIterableParameter(['key1' => new StringParameter('foo'), 'key2' => new StringParameter('bar')]),
        ];

        yield 'enum parameter' => [
            EnumParameter::class,
            (object) ['one' => 'foo', 'two' => 1],
            new EnumParameter(StringEnum::FOO, IntegerEnum::ONE),
        ];
    }

    /**
     * @param class-string $class
     * @param object $result
     *
     * @requires PHP >= 8.1
     * @dataProvider mapSuccessProviderPhp81
     */
    public function testMapSuccessPhp81(string $class, object $json, object $result): void
    {
        $this->testMapSuccess($class, $json, $result);
    }

    /**
     * @return iterable<array{class-string, object, object}>
     */
    public function mapSuccessProviderPhp81(): iterable
    {
        yield 'enum parameter' => [
            EnumParameter::class,
            (object) ['one' => 'foo', 'two' => 1],
            new EnumParameter(StringEnum::FOO, IntegerEnum::ONE),
        ];
    }

    /**
     * @param class-string $class
     * @param class-string<\Throwable> $exception
     *
     * @dataProvider mapErrorProvider
     */
    public function testMapError(string $class, object $json, string $exception, string $message): void
    {
        $this->expectException($exception);
        $this->expectExceptionMessage($message);
        $this->getDefaultMapper()->map($class, json_encode($json, JSON_THROW_ON_ERROR));
    }

    /**
     * @return iterable<array{class-string, object, class-string<\Throwable>, string}>
     */
    public function mapErrorProvider(): iterable
    {
        yield 'nullable parameter' => [
            StringParameter::class,
            (object) [],
            ParameterDoesNotAcceptValueMapperException::class,
            'Class Lookyman\\JsonMapper\\Schema\\StringParameter constructor parameter $one of type string does not accept NULL',
        ];

        yield 'string parameter' => [
            StringParameter::class,
            (object) ['one' => 1],
            ParameterDoesNotAcceptValueMapperException::class,
            'Class Lookyman\\JsonMapper\\Schema\\StringParameter constructor parameter $one of type string does not accept 1',
        ];

        yield 'literal string parameter' => [
            LiteralStringParameter::class,
            (object) ['one' => 'bar'],
            ParameterDoesNotAcceptValueMapperException::class,
            'Class Lookyman\\JsonMapper\\Schema\\LiteralStringParameter constructor parameter $one of type \'foo\' does not accept \'bar\'',
        ];

        yield 'integer parameter' => [
            IntegerParameter::class,
            (object) ['one' => 'foo'],
            ParameterDoesNotAcceptValueMapperException::class,
            'Class Lookyman\\JsonMapper\\Schema\\IntegerParameter constructor parameter $one of type int does not accept \'foo\'',
        ];

        yield 'integer parameter does not accept float' => [
            IntegerParameter::class,
            (object) ['one' => 1.5],
            ParameterDoesNotAcceptValueMapperException::class,
            'Class Lookyman\\JsonMapper\\Schema\\IntegerParameter constructor parameter $one of type int does not accept 1.5',
        ];

        yield 'literal integer parameter' => [
            LiteralIntegerParameter::class,
            (object) ['one' => 2],
            ParameterDoesNotAcceptValueMapperException::class,
            'Class Lookyman\\JsonMapper\\Schema\\LiteralIntegerParameter constructor parameter $one of type 1 does not accept 2',
        ];

        yield 'float parameter' => [
            FloatParameter::class,
            (object) ['one' => 'foo'],
            ParameterDoesNotAcceptValueMapperException::class,
            'Class Lookyman\\JsonMapper\\Schema\\FloatParameter constructor parameter $one of type float does not accept \'foo\'',
        ];

        yield 'literal float parameter' => [
            LiteralFloatParameter::class,
            (object) ['one' => 2.5],
            ParameterDoesNotAcceptValueMapperException::class,
            'Class Lookyman\\JsonMapper\\Schema\\LiteralFloatParameter constructor parameter $one of type 1.5 does not accept 2.5',
        ];

        yield 'boolean parameter' => [
            BooleanParameter::class,
            (object) ['one' => 'foo'],
            ParameterDoesNotAcceptValueMapperException::class,
            'Class Lookyman\\JsonMapper\\Schema\\BooleanParameter constructor parameter $one of type bool does not accept \'foo\'',
        ];

        yield 'literal boolean parameter' => [
            LiteralBooleanParameter::class,
            (object) ['one' => false],
            ParameterDoesNotAcceptValueMapperException::class,
            'Class Lookyman\\JsonMapper\\Schema\\LiteralBooleanParameter constructor parameter $one of type true does not accept false',
        ];

        yield 'array parameter' => [
            ArrayParameter::class,
            (object) ['one' => [true]],
            ArrayDoesNotAcceptValueMapperException::class,
            'Array of type int|string does not accept true',
        ];

        yield 'array with keys parameter' => [
            ArrayParameter::class,
            (object) ['one' => ['foo' => true]],
            ArrayDoesNotAcceptValueMapperException::class,
            'Array of type int|string does not accept true',
        ];

        yield 'array shape parameter' => [
            ArrayShapeParameter::class,
            (object) ['one' => [1, 'foo']],
            ParameterDoesNotAcceptValueMapperException::class,
            "Class Lookyman\\JsonMapper\\Schema\\ArrayShapeParameter constructor parameter \$one of type array{string, int} does not accept array (\n  0 => 1,\n  1 => 'foo',\n)",
        ];

        yield 'object without class' => [
            ObjectWithoutClassParameter::class,
            (object) ['one' => 'foo'],
            ParameterDoesNotAcceptValueMapperException::class,
            'Class Lookyman\\JsonMapper\\Schema\\ObjectWithoutClassParameter constructor parameter $one of type object does not accept \'foo\'',
        ];

        yield 'array of unions' => [
            ArrayOfUnionsParameter::class,
            (object) ['one' => [['one' => 'foo'], ['one' => true]]],
            InvalidJsonValueMapperException::class,
            '',
        ];

        yield 'class string' => [
            ClassStringParameter::class,
            (object) ['one' => 'foo'],
            ParameterDoesNotAcceptValueMapperException::class,
            'Class Lookyman\\JsonMapper\\Schema\\ClassStringParameter constructor parameter $one of type class-string does not accept \'foo\'',
        ];

        yield 'generic class string' => [
            GenericClassStringParameter::class,
            (object) ['one' => ClassStringParameter::class],
            ParameterDoesNotAcceptValueMapperException::class,
            'Class Lookyman\\JsonMapper\\Schema\\GenericClassStringParameter constructor parameter $one of type class-string<Lookyman\\JsonMapper\\Schema\\GenericClassStringParameter> does not accept \'Lookyman\\\\JsonMapper\\\\Schema\\\\ClassStringParameter\'',
        ];

        yield 'generic object parameter' => [
            GenericObjectParameter::class,
            (object) ['one' => ['two' => ['one' => 1], 'one' => ['one' => true]]],
            ParameterDoesNotAcceptValueMapperException::class,
            'Class Lookyman\\JsonMapper\\Schema\\ExtendedStringParameter constructor parameter $one of type string does not accept true',
        ];

        yield 'generic iterable parameter' => [
            GenericIterableParameter::class,
            (object) ['one' => [['one' => 'foo'], ['one' => 'bar']]],
            ArrayDoesNotAcceptValueMapperException::class,
            'Array of type string does not accept 0',
        ];

        yield 'enum parameter' => [
            EnumParameter::class,
            (object) ['one' => 'wtf', 'two' => 3],
            EnumDoesNotAcceptValueMapperException::class,
            'Enum of type Lookyman\\JsonMapper\\Schema\\StringEnum does not accept \'wtf\'',
        ];
    }

    /**
     * @param class-string $class
     * @param class-string<\Throwable> $exception
     *
     * @requires PHP >= 8.1
     * @dataProvider mapErrorProviderPhp81
     */
    public function testMapErrorPhp81(string $class, object $json, string $exception, string $message): void
    {
        $this->testMapError($class, $json, $exception, $message);
    }

    /**
     * @return iterable<array{class-string, object, class-string<\Throwable>, string}>
     */
    public function mapErrorProviderPhp81(): iterable
    {
        yield 'enum parameter' => [
            EnumParameter::class,
            (object) ['one' => 'wtf', 'two' => 3],
            EnumDoesNotAcceptValueMapperException::class,
            'Enum of type Lookyman\\JsonMapper\\Schema\\StringEnum does not accept \'wtf\'',
        ];
    }

    public function testInvalidJson(): void
    {
        $this->expectException(JsonStringIsNotAnObjectMapperException::class);
        $this->expectExceptionMessage('foo');
        $this->getDefaultMapper()->map(StringParameter::class, json_encode('foo', JSON_THROW_ON_ERROR));
    }

    public function testParameterNameMapping(): void
    {
        $result = (new MapperBuilder())
            ->withCacheDir(__DIR__ . '/..')
            ->withParameterMapping(StringParameter::class, 'one', 'two')
            ->build()
            ->map(StringParameter::class, json_encode(['two' => 'foo'], JSON_THROW_ON_ERROR));
        self::assertEquals(new StringParameter('foo'), $result);
    }

}
