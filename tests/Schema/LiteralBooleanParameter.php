<?php

declare(strict_types = 1);

namespace Lookyman\JsonMapper\Schema;

final class LiteralBooleanParameter
{

    /**
     * @param true $one
     */
    public function __construct(public bool $one)
    {
    }

}
