<?php

namespace Blume\LaravelDTO\Attributes\Validation;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Nullable extends Rule
{
    public function toValidationRule(): string
    {
        return 'nullable';
    }
}
