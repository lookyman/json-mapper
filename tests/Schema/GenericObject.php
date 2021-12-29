<?php

declare(strict_types = 1);

namespace Lookyman\JsonMapper\Schema;

/**
 * @template TOne of \Lookyman\JsonMapper\Schema\StringParameter
 * @template TTwo of \Lookyman\JsonMapper\Schema\IntegerParameter
 */
final class GenericObject
{

    /**
     * @param TOne $one
     */
    public function __construct(
        public StringParameter $one,
        public IntegerParameter $two,
    ) {
    }

}
