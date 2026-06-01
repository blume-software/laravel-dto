<?php

namespace Blume\LaravelDTO\Attributes\Validation;

use Attribute;
use Illuminate\Validation\Rule as ValidationRule;

#[Attribute(Attribute::TARGET_PROPERTY)]
class IsEnumCase extends Rule
{
    public function __construct(
        public string $enumClass
    ) {}

    public function toValidationRule(): object
    {
        return ValidationRule::enum($this->enumClass);
    }
}
