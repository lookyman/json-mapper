<?php

declare(strict_types = 1);

namespace Lookyman\JsonMapper\Schema;

final class UnionOfObjectsParameter
{

    public function __construct(public StringParameter|IntegerParameter $one)
    {
    }

}
