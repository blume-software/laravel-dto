<?php

namespace BlumeSoftware\LaravelDTO\Attributes\Validation;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class IsArray extends Rule
{
    public function toValidationRule(): string
    {
        return 'array';
    }
}
