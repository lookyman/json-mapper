<?php

declare(strict_types = 1);

namespace Lookyman\JsonMapper\Exception;

use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function sprintf;
use function var_export;

final class ParameterDoesNotAcceptValueMapperException extends MapperException
{

    public function __construct(
        private string $class,
        private string $name,
        private Type $type,
        private mixed $value,
    ) {
        parent::__construct(sprintf('Class %s constructor parameter $%s of type %s does not accept %s', $class, $name, $type->describe(VerbosityLevel::precise()), var_export($value, true)));
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

}
