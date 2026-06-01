<?php

namespace BlumeSoftware\LaravelDTO\Attributes\Validation;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Uuid extends Rule
{
    public function toValidationRule(): string
    {
        return 'uuid';
    }
}
