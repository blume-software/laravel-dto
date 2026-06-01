<?php

namespace BlumeSoftware\LaravelDTO\Attributes\Validation;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class RequiredWithout extends Rule
{
    public function __construct(
        public string|array $field
    ) {}

    public function toValidationRule(): string
    {
        $fields = is_array($this->field)
            ? implode(',', $this->field)
            : $this->field;

        return 'required_without:'.$fields;
    }
}
