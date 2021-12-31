<?php

declare(strict_types = 1);

namespace Lookyman\JsonMapper\Parameters;

use PHPStan\Type\Type;

interface Provider
{

    /**
     * @param class-string $class
     *
     * @return array<array{string, \PHPStan\Type\Type}>
     *
     * @throws \Lookyman\JsonMapper\Exception\MapperException
     */
    public function getParameters(
        string $class,
        Type $expectedType,
    ): array;

}
