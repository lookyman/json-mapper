<?php

declare(strict_types = 1);

namespace Lookyman\JsonMapper\Schema;

final class ObjectParameter
{

    public function __construct(public StringParameter $one)
    {
    }

}
