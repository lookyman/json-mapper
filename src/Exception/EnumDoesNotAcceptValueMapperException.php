<?php

declare(strict_types = 1);

namespace Lookyman\JsonMapper\Exception;

use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function sprintf;
use function var_export;

final class EnumDoesNotAcceptValueMapperException extends MapperException
{

    public function __construct(
        private Type $type,
        private mixed $value,
    ) {
        parent::__construct(sprintf('Enum of type %s does not accept %s', $type->describe(VerbosityLevel::precise()), var_export($value, true)));
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
