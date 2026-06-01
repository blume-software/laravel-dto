<?php

namespace BlumeSoftware\LaravelDTO\Casts;

use BlumeSoftware\LaravelDTO\BaseDTO;
use BlumeSoftware\LaravelDTO\Interfaces\Castable;

class DTOCast implements Castable
{
    public function __construct(protected string $dtoClass) {}

    public function cast(string $property, mixed $value): ?BaseDTO
    {
        if (! $value) {
            return null;
        }

        return new ($this->dtoClass)($value);
    }
}
