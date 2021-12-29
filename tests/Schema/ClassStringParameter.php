<?php

declare(strict_types = 1);

namespace Lookyman\JsonMapper\Schema;

final class ClassStringParameter
{

    /**
     * @param class-string $one
     */
    public function __construct(public string $one)
    {
    }

}
