<?php

declare(strict_types = 1);

namespace Lookyman\JsonMapper\Schema;

final class NullableArrayParameter
{

    /**
     * @param \Lookyman\JsonMapper\Schema\StringParameter[]|null $one
     */
    public function __construct(public ?array $one)
    {
    }

}
