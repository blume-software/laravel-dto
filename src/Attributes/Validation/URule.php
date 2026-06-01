<?php

namespace BlumeSoftware\LaravelDTO\Attributes\Validation;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class URule extends Rule
{
    public function __construct(
        public string $rule
    ) {}

    public function toValidationRule(): string
    {
        return $this->rule;
    }
}
