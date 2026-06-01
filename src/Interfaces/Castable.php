<?php

namespace BlumeSoftware\LaravelDTO\Interfaces;

interface Castable
{
    public function cast(string $property, mixed $value): mixed;
}
