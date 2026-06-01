<?php

namespace Blume\LaravelDTO\Attributes\Validation;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class IsString extends Rule
{
    public function toValidationRule(): string
    {
        return 'string';
    }
}
