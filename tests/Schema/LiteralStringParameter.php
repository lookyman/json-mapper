<?php

declare(strict_types = 1);

namespace Lookyman\JsonMapper\Schema;

final class LiteralStringParameter
{

    /**
     * @param 'foo' $one
     */
    public function __construct(public string $one)
    {
    }

}
