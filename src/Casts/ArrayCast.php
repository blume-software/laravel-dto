<?php

namespace Blume\LaravelDTO\Casts;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use Blume\LaravelDTO\Interfaces\Castable;

class ArrayCast implements Castable
{
    public function __construct(
        protected ?Castable $castable = null
    ) {}

    public function cast(string $property, mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Collection) {
            $value = $value->all();
        }

        if (isset($this->castable) && is_array($value)) {
            $value = array_map(function ($i) use ($property) {
                return $this->castable->cast($property, $i);
            }, $value);
        }

        if (is_object($value) && method_exists($value, 'toArray')) {
            $value = $value->toArray();
        }

        if (! is_array($value)) {
            throw new InvalidArgumentException('value must be an array');
        }

        return $value;
    }
}
