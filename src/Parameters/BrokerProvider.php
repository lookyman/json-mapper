<?php

declare(strict_types = 1);

namespace Lookyman\JsonMapper\Parameters;

use Lookyman\JsonMapper\Exception\CannotFindClassMapperException;
use Lookyman\JsonMapper\Exception\ClassDoesNotHaveConstructorMapperException;
use Lookyman\JsonMapper\Exception\ConstructorHasMultipleVariantsMapperException;
use PHPStan\Reflection\GenericParametersAcceptorResolver;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Type;
use function array_map;
use function count;

final class BrokerProvider implements Provider
{

    public function __construct(private ReflectionProvider $broker)
    {
    }

    public function getParameters(
        string $class,
        Type $expectedType,
    ): array {
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

        $constructor = $variants[0];
        if ($expectedType instanceof GenericObjectType) {
            $constructor = GenericParametersAcceptorResolver::resolve($expectedType->getTypes(), $constructor);
        }

        return array_map(static fn (ParameterReflection $parameter): array => [$parameter->getName(), $parameter->getType(), $parameter->getDefaultValue()], $constructor->getParameters());
    }

}
