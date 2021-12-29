<?php

declare(strict_types = 1);

namespace Lookyman\JsonMapper\Schema;

final class GenericObjectParameter
{

    /**
     * @param \Lookyman\JsonMapper\Schema\GenericObject<\Lookyman\JsonMapper\Schema\ExtendedStringParameter, \Lookyman\JsonMapper\Schema\IntegerParameter> $one
     */
    public function __construct(public GenericObject $one)
    {
    }

}
