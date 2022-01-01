<?php

declare(strict_types = 1);

namespace Lookyman\JsonMapper\Schema;

final class EnumParameter
{

    public function __construct(
        public StringEnum $one,
        public IntegerEnum $two,
    ) {
    }

}
