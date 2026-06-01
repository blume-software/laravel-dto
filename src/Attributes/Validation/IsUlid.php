<?php

namespace BlumeSoftware\LaravelDTO\Attributes\Validation;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class IsUlid extends Rule
{
    public function toValidationRule(): string
    {
        return 'ulid';
    }
}
