<?php

namespace BlumeSoftware\LaravelDTO\Casts;

use BackedEnum;
use BlumeSoftware\LaravelDTO\Interfaces\Castable;
use InvalidArgumentException;
use UnitEnum;
use ValueError;

class EnumCast implements Castable
{
    /**
     * @param  class-string<UnitEnum|BackedEnum>  $enum
     */
    public function __construct(protected string $enum) {}

    /**
     * @throws InvalidArgumentException if the class is not an enum
     * @throws ValueError if the value cannot be resolved to an enum case
     */
    public function cast(string $property, mixed $value): UnitEnum|BackedEnum|null
    {
        if (! is_subclass_of($this->enum, UnitEnum::class)) {
            throw new InvalidArgumentException(
                "EnumCast: [{$this->enum}] is not a valid enum class."
            );
        }

        if ($value === null) {
            return null;
        }

        if ($value instanceof $this->enum) {
            return $value;
        }

        if (is_subclass_of($this->enum, BackedEnum::class)) {
            if (! is_string($value) && ! is_int($value)) {
                throw new ValueError(
                    "EnumCast: value for [{$property}] must be a string or int for backed enum [{$this->enum}]."
                );
            }

            $result = $this->enum::tryFrom($value);

            if ($result === null) {
                throw new ValueError(
                    "EnumCast: [{$value}] is not a valid case for [{$this->enum}]."
                );
            }

            return $result;
        }

        // Pure (unit) enum — match by case name
        foreach ($this->enum::cases() as $case) {
            if ($case->name === $value) {
                return $case;
            }
        }

        throw new ValueError(
            "EnumCast: [{$value}] is not a valid case name for [{$this->enum}]."
        );
    }
}
