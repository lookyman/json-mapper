<?php

declare(strict_types = 1);

namespace Lookyman\JsonMapper\Schema;

final class UnionParameter
{

    public function __construct(public string|StringParameter $one)
    {
    }

}
