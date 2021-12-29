<?php

declare(strict_types = 1);

namespace Lookyman\JsonMapper\Exception;

final class ClassDoesNotHaveConstructorMapperException extends MapperException
{

    public function __construct(private string $class)
    {
        parent::__construct();
    }

    public function getClass(): string
    {
        return $this->class;
    }

}
