<?php

declare(strict_types = 1);

namespace Lookyman\JsonMapper\Schema;

final class LiteralFloatParameter
{

    /**
     * @param 1.5 $one
     */
    public function __construct(public float $one)
    {
    }

}
