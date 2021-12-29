<?php

declare(strict_types = 1);

namespace Lookyman\JsonMapper\Schema;

final class NullableParameter
{

    public function __construct(public ?string $one)
    {
    }

}
