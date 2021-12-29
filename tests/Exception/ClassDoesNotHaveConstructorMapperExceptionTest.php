<?php

declare(strict_types = 1);

namespace Lookyman\JsonMapper\Exception;

use PHPUnit\Framework\TestCase;

final class ClassDoesNotHaveConstructorMapperExceptionTest extends TestCase
{

    public function testException(): void
    {
        $exception = new ClassDoesNotHaveConstructorMapperException('foo');
        self::assertSame('foo', $exception->getClass());
    }

}
