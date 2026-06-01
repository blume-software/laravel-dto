<?php

namespace Blume\LaravelDTO\OpenApi\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Response
{
    public function __construct(
        public int $statusCode = 200,
        public ?string $description = null,
    ) {}
}
