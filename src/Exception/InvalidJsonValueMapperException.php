<?php

declare(strict_types = 1);

namespace Lookyman\JsonMapper\Exception;

final class InvalidJsonValueMapperException extends MapperException
{

    public function __construct(private mixed $value)
    {
        parent::__construct();
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

}
