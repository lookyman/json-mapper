<?php

declare(strict_types = 1);

namespace Lookyman\JsonMapper\Schema;

final class GenericIterableParameter
{

    /**
     * @param iterable<string, \Lookyman\JsonMapper\Schema\StringParameter> $one
     */
    public function __construct(public iterable $one)
    {
    }

}
