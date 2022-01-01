<?php

declare(strict_types = 1);

namespace Lookyman\JsonMapper\Schema;

final class IntegerRangeParameter
{

    /**
     * @param negative-int|int<3, 10> $one
     */
    public function __construct(public int $one)
    {
    }

}
