<?php

namespace Blume\LaravelDTO\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class ArrayOf
{
    /**
     * @param  class-string  $class  DTO class for each list element
     */
    public function __construct(
        public string $class,
    ) {}
}
