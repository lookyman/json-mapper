<?php

declare(strict_types = 1);

namespace Lookyman\JsonMapper\Schema;

final class ArrayOfUnionsParameter
{

    /**
     * @param array<\Lookyman\JsonMapper\Schema\StringParameter|\Lookyman\JsonMapper\Schema\IntegerParameter> $one
     */
    public function __construct(public array $one)
    {
    }

}
