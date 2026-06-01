<?php

namespace Blume\LaravelDTO\Attributes\Validation;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
abstract class Rule
{
    /**
     * Convert the rule to Laravel validation rule format
     */
    abstract public function toValidationRule(): string|array|object;
}
