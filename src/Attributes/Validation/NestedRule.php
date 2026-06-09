<?php

namespace BlumeSoftware\LaravelDTO\Attributes\Validation;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class NestedRule
{
    public function __construct(
        protected string $prefix,
        protected Rule|string $rule
    ) {}

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function toValidationRule(): string
    {
        return $this->rule instanceof Rule ? $this->rule->toValidationRule() : $this->rule;
    }
}
