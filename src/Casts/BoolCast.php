<?php

namespace BlumeSoftware\LaravelDTO\Casts;

use BlumeSoftware\LaravelDTO\Interfaces\Castable;
use InvalidArgumentException;

class BoolCast implements Castable
{
    public function cast(string $property, mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if ($value === 'true' || $value === '1' || $value === 1) {
            return true;
        }

        if ($value === 'false' || $value === '0' || $value === 0) {
            return false;
        }

        throw new InvalidArgumentException(
            "BoolCast: [$property] is not a bool."
        );
    }
}
