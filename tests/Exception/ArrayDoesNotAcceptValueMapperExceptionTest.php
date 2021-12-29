<?php

declare(strict_types = 1);

namespace Lookyman\JsonMapper\Exception;

use PHPStan\Type\StringType;
use PHPUnit\Framework\TestCase;

final class ArrayDoesNotAcceptValueMapperExceptionTest extends TestCase
{

    public function testException(): void
    {
        $type = new StringType();
        $exception = new ArrayDoesNotAcceptValueMapperException($type, 1);
        self::assertSame($type, $exception->getType());
        self::assertSame(1, $exception->getValue());
        self::assertSame('Array of type string does not accept 1', $exception->getMessage());
    }

}
