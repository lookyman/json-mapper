<?php

declare(strict_types = 1);

namespace Lookyman\JsonMapper\Schema;

final class GenericClassStringParameter
{

    /**
     * @param class-string<\Lookyman\JsonMapper\Schema\GenericClassStringParameter> $one
     */
    public function __construct(public string $one)
    {
    }

}
