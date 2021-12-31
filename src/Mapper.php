<?php

declare(strict_types = 1);

namespace Lookyman\JsonMapper;

use Lookyman\JsonMapper\Exception\ArrayDoesNotAcceptValueMapperException;
use Lookyman\JsonMapper\Exception\CannotFindClassMapperException;
use Lookyman\JsonMapper\Exception\ClassDoesNotHaveConstructorMapperException;
use Lookyman\JsonMapper\Exception\InvalidJsonValueMapperException;
use Lookyman\JsonMapper\Exception\JsonStringIsNotAnObjectMapperException;
use Lookyman\JsonMapper\Exception\MapperException;
use Lookyman\JsonMapper\Exception\ParameterDoesNotAcceptValueMapperException;
use Lookyman\JsonMapper\Exception\ConstructorHasMultipleVariantsMapperException;
use PHPStan\Reflection\GenericParametersAcceptorResolver;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IterableType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeWithClassName;
use PHPStan\Type\UnionType;
use stdClass;
use function assert;
use function count;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;
use function json_decode;
use const JSON_THROW_ON_ERROR;

final class Mapper
{

    /** @var array<class-string, \PHPStan\Reflection\ParametersAcceptor> */
    private array $constructorCache = [];

    /**
     * @param array<class-string, class-string> $classMappings
     * @param array<class-string, array<string, string>> $parameterNameMappings
     */
    public function __construct(
        private ReflectionProvider $broker,
        private array $classMappings,
        private array $parameterNameMappings,
    ) {
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T
     *
     * @throws \JsonException
     * @throws \Lookyman\JsonMapper\Exception\MapperException
     */
    public function map(
        string $class,
        string $json,
    ): object {
        $decoded = json_decode($json, flags: JSON_THROW_ON_ERROR);
        if (!$decoded instanceof stdClass) {
            throw new JsonStringIsNotAnObjectMapperException($json);
        }

        $mappedValue = $this->doMap($decoded, new ObjectType($class))[0];
        assert($mappedValue instanceof $class);

        return $mappedValue;
    }

    /**
     * @param null|int|float|string|bool|array<mixed>|\stdClass $rawValue
     *
     * @return array{mixed, \PHPStan\Type\Type}
     *
     * @throws \Lookyman\JsonMapper\Exception\MapperException
     */
    private function doMap(
        null|int|float|string|bool|array|stdClass $rawValue,
        Type $expectedType,
    ): array {
        if ($rawValue === null) {
            return [$rawValue, new NullType()];
        }

        if (is_int($rawValue)) {
            return [$rawValue, new ConstantIntegerType($rawValue)];
        }

        if (is_float($rawValue)) {
            return [$rawValue, new ConstantFloatType($rawValue)];
        }

        if (is_string($rawValue)) {
            return [$rawValue, new ConstantStringType($rawValue)];
        }

        if (is_bool($rawValue)) {
            return [$rawValue, new ConstantBooleanType($rawValue)];
        }

        if (is_array($rawValue)) {
            return $this->mapArray($rawValue, $expectedType);
        }

        if ($rawValue instanceof stdClass) {
            if ($expectedType instanceof ObjectWithoutClassType) {
                return [$rawValue, $expectedType];
            }

            if ($expectedType instanceof ArrayType || $expectedType instanceof IterableType) {
                return $this->mapArray($rawValue, $expectedType);
            }

            if ($expectedType instanceof TypeWithClassName) {
                /** @var class-string $class */
                $class = $expectedType->getClassName();
                $class = $this->classMappings[$class] ?? $class;
                $constructor = $this->getConstructor($class);
                if ($expectedType instanceof GenericObjectType) {
                    $constructor = GenericParametersAcceptorResolver::resolve($expectedType->getTypes(), $constructor);
                }

                $arguments = [];
                foreach ($constructor->getParameters() as $parameter) {
                    $name = $parameter->getName();
                    $type = $parameter->getType();
                    $value = $rawValue->{$this->parameterNameMappings[$class][$name] ?? $name} ?? null;
                    $valueAndType = $this->doMap($value, $type);
                    if (!$type->accepts($valueAndType[1], true)->yes()) {
                        throw new ParameterDoesNotAcceptValueMapperException($class, $name, $type, $value);
                    }

                    $arguments[$name] = $valueAndType[0];
                }

                return [new $class(...$arguments), $expectedType];
            }

            if ($expectedType instanceof UnionType) {
                $mapped = [];
                foreach ($expectedType->getTypes() as $innerType) {
                    try {
                        $mapped[] = $this->doMap($rawValue, $innerType);
                    } catch (MapperException) {
                        // no-op
                    }

                    if (count($mapped) > 1) {
                        throw new InvalidJsonValueMapperException($rawValue);
                    }
                }

                if (isset($mapped[0])) {
                    return $mapped[0];
                }
            }
        }

        throw new InvalidJsonValueMapperException($rawValue);
    }

    /**
     * @param iterable<mixed>|\stdClass $rawValue
     *
     * @return array{array<mixed>, \PHPStan\Type\Type}
     *
     * @throws \Lookyman\JsonMapper\Exception\MapperException
     */
    private function mapArray(
        iterable|stdClass $rawValue,
        Type $expectedType,
    ): array {
        $array = [];
        $arrayBuilder = ConstantArrayTypeBuilder::createEmpty();
        $keyType = $expectedType->getIterableKeyType();
        $itemType = $expectedType->getIterableValueType();

        foreach ($rawValue as $key => $item) { // @phpstan-ignore-line
            $keyValueAndType = $this->doMap($key, $keyType);
            if (!$keyType->accepts($keyValueAndType[1], true)->yes()) {
                throw new ArrayDoesNotAcceptValueMapperException($keyType, $key);
            }

            $itemValueAndType = $this->doMap($item, $itemType);
            if (!$itemType->accepts($itemValueAndType[1], true)->yes()) {
                throw new ArrayDoesNotAcceptValueMapperException($itemType, $item);
            }

            $array[$keyValueAndType[0]] = $itemValueAndType[0];
            $arrayBuilder->setOffsetValueType($keyValueAndType[1], $itemValueAndType[1]);
        }

        return [$array, $arrayBuilder->getArray()];
    }

    /**
     * @param class-string $class
     *
     * @throws \Lookyman\JsonMapper\Exception\MapperException
     */
    private function getConstructor(string $class): ParametersAcceptor
    {
        if (isset($this->constructorCache[$class])) {
            return $this->constructorCache[$class];
        }

        if (!$this->broker->hasClass($class)) {
            throw new CannotFindClassMapperException($class);
        }

        $classReflection = $this->broker->getClass($class);
        if (!$classReflection->hasNativeMethod('__construct')) {
            throw new ClassDoesNotHaveConstructorMapperException($class);
        }

        $constructor = $classReflection->getNativeMethod('__construct');
        if (!$constructor->isPublic()) {
            throw new ClassDoesNotHaveConstructorMapperException($class);
        }

        $variants = $constructor->getVariants();
        if (count($variants) !== 1 || !isset($variants[0])) {
            throw new ConstructorHasMultipleVariantsMapperException($class);
        }

        return $this->constructorCache[$class] = $variants[0];
    }

}
