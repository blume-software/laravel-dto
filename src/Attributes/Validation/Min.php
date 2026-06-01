<?php

namespace BlumeSoftware\LaravelDTO\Attributes\Validation;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Min extends Rule
{
    public function __construct(
        public int $value
    ) {}

    public function toValidationRule(): string
    {
        return "min:{$this->value}";
    }
}
