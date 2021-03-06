<?php

declare(strict_types = 1);

namespace Lookyman\JsonMapper;

use Lookyman\JsonMapper\Parameters\BrokerProvider;
use Lookyman\JsonMapper\Parameters\MemoizingProvider;
use Lookyman\JsonMapper\Parameters\Provider;
use PHPStan\Broker\BrokerFactory;
use PHPStan\DependencyInjection\ContainerFactory;
use function assert;
use function sys_get_temp_dir;

final class MapperBuilder
{

    /** @var array<class-string, class-string> */
    private array $classMappings = [];

    /** @var array<class-string, array<string, string>> */
    private array $parameterNameMappings = [];

    private ?string $cacheDir = null;

    private ?Provider $parametersProvider = null;

    public function build(): Mapper
    {
        return new Mapper($this->parametersProvider ?? $this->getDefaultParametersProvider(), $this->classMappings, $this->parameterNameMappings);
    }

    /**
     * @param class-string $fromClass
     * @param class-string $toClass
     */
    public function withClassMapping(
        string $fromClass,
        string $toClass,
    ): self {
        $clone = clone $this;
        $clone->classMappings[$fromClass] = $toClass;

        return $clone;
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

    public function withParametersProvider(Provider $parametersProvider): self
    {
        $clone = clone $this;
        $clone->parametersProvider = $parametersProvider;

        return $this;
    }

    private function getDefaultParametersProvider(): Provider
    {
        $brokerFactory = (new ContainerFactory(__DIR__))
            ->create($this->cacheDir ?? (sys_get_temp_dir() . '/lookyman/json-mapper'), [], [], [], [], 'max')
            ->getService('brokerFactory');
        assert($brokerFactory instanceof BrokerFactory);

        return new MemoizingProvider(new BrokerProvider($brokerFactory->create())); // @phpstan-ignore-line
    }

}
