<?php

namespace BlumeSoftware\LaravelDTO\Attributes\Validation;

abstract class Rule
{
    /**
     * Convert the rule to Laravel validation rule format
     */
    abstract public function toValidationRule(): string|array|object;
}
