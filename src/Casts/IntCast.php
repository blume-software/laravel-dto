<?php

namespace BlumeSoftware\LaravelDTO\Casts;

use BlumeSoftware\LaravelDTO\Interfaces\Castable;
use InvalidArgumentException;

class IntCast implements Castable
{
    public function cast(string $property, mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            throw new InvalidArgumentException(
                "IntCast: [$property] is not a valid integer."
            );
        }

        return (int) $value;
    }
}
