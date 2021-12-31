<?php

declare(strict_types = 1);

namespace Lookyman\JsonMapper\Parameters;

use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

final class MemoizingProvider implements Provider
{

    /** @var array<class-string, array<string, array<array{string, \PHPStan\Type\Type}>>> */
    private array $cache = [];

    public function __construct(private Provider $inner)
    {
    }

    public function getParameters(
        string $class,
        Type $expectedType,
    ): array {
        $description = $expectedType->describe(VerbosityLevel::precise());
        if (isset($this->cache[$class][$description])) {
            return $this->cache[$class][$description];
        }

        return $this->cache[$class][$description] = $this->inner->getParameters($class, $expectedType);
    }

}
