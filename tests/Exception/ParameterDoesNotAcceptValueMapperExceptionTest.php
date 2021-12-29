<?php

declare(strict_types = 1);

namespace Lookyman\JsonMapper\Exception;

use PHPStan\Type\StringType;
use PHPUnit\Framework\TestCase;

final class ParameterDoesNotAcceptValueMapperExceptionTest extends TestCase
{

    public function testException(): void
    {
        $type = new StringType();
        $exception = new ParameterDoesNotAcceptValueMapperException('foo', 'bar', $type, 1);
        self::assertSame('foo', $exception->getClass());
        self::assertSame('bar', $exception->getName());
        self::assertSame($type, $exception->getType());
        self::assertSame(1, $exception->getValue());
        self::assertSame('Class foo constructor parameter $bar of type string does not accept 1', $exception->getMessage());
    }

}
