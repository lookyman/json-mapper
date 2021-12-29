<?php

declare(strict_types = 1);

namespace Lookyman\JsonMapper\Exception;

use PHPUnit\Framework\TestCase;

final class CannotFindClassMapperExceptionTest extends TestCase
{

    public function testException(): void
    {
        $exception = new CannotFindClassMapperException('foo');
        self::assertSame('foo', $exception->getClass());
    }

}
