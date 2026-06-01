<?php

namespace BlumeSoftware\LaravelDTO\Attributes\Validation;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class IsBool extends Rule
{
    public function toValidationRule(): string
    {
        return 'boolean';
    }
}
