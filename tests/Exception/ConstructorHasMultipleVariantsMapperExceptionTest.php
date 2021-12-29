<?php

declare(strict_types = 1);

namespace Lookyman\JsonMapper\Exception;

use PHPUnit\Framework\TestCase;

final class ConstructorHasMultipleVariantsMapperExceptionTest extends TestCase
{

    public function testException(): void
    {
        $exception = new ConstructorHasMultipleVariantsMapperException('foo');
        self::assertSame('foo', $exception->getClass());
    }

}
