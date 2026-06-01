<?php

namespace BlumeSoftware\LaravelDTO\Casts;

use InvalidArgumentException;
use BlumeSoftware\LaravelDTO\Interfaces\Castable;

class IntCast implements Castable
{
    public function cast(string $property, mixed $value): int
    {
        if (! is_numeric($value) && $value !== '') {
            throw new InvalidArgumentException(
                "IntCast: [$property] is not a valid enum class."
            );
        }

        return (int) $value;
    }
}
