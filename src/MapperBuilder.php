<?php

declare(strict_types = 1);

namespace Lookyman\JsonMapper;

use PHPStan\Broker\BrokerFactory;
use PHPStan\DependencyInjection\ContainerFactory;
use function assert;
use function sys_get_temp_dir;

final class MapperBuilder
{

    /** @var array<class-string, array<string, string>> */
    private array $parameterNameMappings = [];

    private ?string $cacheDir = null;

    public function build(): Mapper
    {
        $brokerFactory = (new ContainerFactory(__DIR__))
            ->create($this->cacheDir ?? (sys_get_temp_dir() . '/lookyman/json-mapper'), [], [], [], [], 'max')
            ->getService('brokerFactory');
        assert($brokerFactory instanceof BrokerFactory);

        return new Mapper($brokerFactory->create(), $this->parameterNameMappings); // @phpstan-ignore-line
    }

    /**
     * @param class-string $class
     */
    public function withParameterMapping(
        string $class,
        string $name,
        string $sourcePropertyName,
    ): self {
        $clone = clone $this;
        $clone->parameterNameMappings[$class][$name] = $sourcePropertyName;

        return $clone;
    }

    public function withCacheDir(string $cacheDir): self
    {
        $clone = clone $this;
        $clone->cacheDir = $cacheDir;

        return $clone;
    }

}
