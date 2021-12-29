<?php

declare(strict_types = 1);

namespace Lookyman\JsonMapper\Schema;

final class LiteralIntegerParameter
{

    /**
     * @param 1 $one
     */
    public function __construct(public int $one)
    {
    }

}
