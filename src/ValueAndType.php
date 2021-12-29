<?php

declare(strict_types = 1);

namespace Lookyman\JsonMapper;

use PHPStan\Type\Type;

final class ValueAndType
{

    public function __construct(
        private mixed $value,
        private Type $type,
    ) {
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getType(): Type
    {
        return $this->type;
    }

}
