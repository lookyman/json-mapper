<?php

declare(strict_types = 1);

namespace Lookyman\JsonMapper;

use Lookyman\JsonMapper\Exception\ArrayDoesNotAcceptValueMapperException;
use Lookyman\JsonMapper\Exception\EnumDoesNotAcceptValueMapperException;
use Lookyman\JsonMapper\Exception\InvalidJsonValueMapperException;
use Lookyman\JsonMapper\Exception\JsonStringIsNotAnObjectMapperException;
use Lookyman\JsonMapper\Exception\MapperException;
use Lookyman\JsonMapper\Exception\ParameterDoesNotAcceptValueMapperException;
use Lookyman\JsonMapper\Parameters\Provider;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\Enum\EnumCaseObjectType;
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

    /**
     * @param array<class-string, class-string> $classMappings
     * @param array<class-string, array<string, string>> $parameterNameMappings
     */
    public function __construct(
        private Provider $parametersProvider,
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
            if ($expectedType instanceof ObjectType && $expectedType->getClassReflection()?->isBackedEnum() === true) {
                return $this->mapEnum($rawValue, $expectedType);
            }

            return [$rawValue, new ConstantIntegerType($rawValue)];
        }

        if (is_float($rawValue)) {
            return [$rawValue, new ConstantFloatType($rawValue)];
        }

        if (is_string($rawValue)) {
            if ($expectedType instanceof ObjectType && $expectedType->getClassReflection()?->isBackedEnum() === true) {
                return $this->mapEnum($rawValue, $expectedType);
            }

            return [$rawValue, new ConstantStringType($rawValue)];
        }

        if (is_bool($rawValue)) {
            return [$rawValue, new ConstantBooleanType($rawValue)];
        }

        if (is_array($rawValue)) {
            return $this->mapArray($rawValue, $expectedType);
        }

        // $rawValue is instance of stdClass from this point on

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
            $arguments = [];
            foreach ($this->parametersProvider->getParameters($class, $expectedType) as [$name, $type, $defaultValue]) {
                $value = $rawValue->{$this->parameterNameMappings[$class][$name] ?? $name} ?? ($defaultValue instanceof ConstantScalarType ? $defaultValue->getValue() : null);
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

        throw new InvalidJsonValueMapperException($rawValue);
    }

    /**
     * @return array{\BackedEnum, \PHPStan\Type\Type}
     */
    private function mapEnum(
        int|string $rawValue,
        ObjectType $expectedType,
    ): array {
        $value = $expectedType->getClassName()::tryFrom($rawValue);
        if ($value === null) {
            throw new EnumDoesNotAcceptValueMapperException($expectedType, $rawValue);
        }

        return [$value, new EnumCaseObjectType($value::class, $value->name)]; // @phpstan-ignore-line
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

    public function getParametersProvider(): Provider
    {
        return $this->parametersProvider;
    }

}
