<?php

namespace Blume\LaravelDTO\Attributes\Validation;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class In extends Rule
{
    public array $values;

    public function __construct(string ...$values)
    {
        $this->values = $values;
    }

    public function toValidationRule(): string
    {
        $values = implode(',', $this->values);

        return "in:{$values}";
    }
}
