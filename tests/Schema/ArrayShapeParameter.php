<?php

declare(strict_types = 1);

namespace Lookyman\JsonMapper\Schema;

final class ArrayShapeParameter
{

    /**
     * @param array{string, int} $one
     */
    public function __construct(public array $one)
    {
    }

}
