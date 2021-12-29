<?php

declare(strict_types = 1);

namespace Lookyman\JsonMapper\Exception;

use PHPUnit\Framework\TestCase;

final class InvalidJsonValueMapperExceptionTest extends TestCase
{

    public function testException(): void
    {
        $exception = new InvalidJsonValueMapperException('foo');
        self::assertSame('foo', $exception->getValue());
    }

}
