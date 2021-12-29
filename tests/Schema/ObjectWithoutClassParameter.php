<?php

declare(strict_types = 1);

namespace Lookyman\JsonMapper\Schema;

final class ObjectWithoutClassParameter
{

    public function __construct(public object $one)
    {
    }

}
