<?php

namespace BlumeSoftware\LaravelDTO\OpenApi\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Example
{
    public function __construct(
        public mixed $value,
    ) {}
}
