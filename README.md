# JSON Mapper

Strict JSON to object hydrator based on [PHPStan](https://phpstan.org/).

## Usage

```php
use Lookyman\JsonMapper\MapperBuilder
use PHPUnit\Framework\Assert;

class Foo
{
    public function __construct(public string $foo)
    {
    }
}

class Bar
{
    public function __construct(public Foo $first)
    {
    }
}

$result = (new MapperBuilder())
    ->build()
    ->map(Bar::class, '{"first":{"foo":"wtf"}}');

Assert::assertEquals(
    new Bar(new Foo('wtf')),
    $result,
);
```

If the parameter is not present in source, and has a scalar default value, it will be assigned that value.
If the parameter is not present in source, but is nullable, it will be assigned a `NULL` value.
If the source contains keys not present in parameter names, those will be ignored.
Otherwise, we do not do any guessing or type casting.

In case of any error, `Lookyman\JsonMapper\Exception\MapperException` is thrown.

## Supported types

* Scalars (including literals and integer ranges),
* arrays (including associative and shapes),
* iterables,
* `class-string` (including generic),
* `object`,
* normal class objects (including generic),
* unions (including nullable parameters),
* backed enums.

See [PHPStan documentation](https://phpstan.org/writing-php-code/phpdoc-types) and `tests` directory for examples.

## Caveats

All classes must be instantiable with a public constructor.
If your types contain interfaces or abstract classes, use `MapperBuilder::withClassMapping` to provide a map to concrete classes.

## Questions?

This is a pet project I played with over the 2021 end of year holidays, because I saw [@Ocramius](https://twitter.com/Ocramius) looking for a similar library a few weeks earlier.
At the moment, I have no ambitions with it, I just wanted to see if this approach is feasible.

It probably contains a lot of bugs.

If you have any questions, the answer is probably "I don't know".
If you have any requests, I will probably not respond.

But I reserve the right to change my mind at any point.
