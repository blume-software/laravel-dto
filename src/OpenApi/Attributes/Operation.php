<?php

namespace Blume\LaravelDTO\OpenApi\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Operation
{
    public function __construct(
        public ?string $method = null,
        public ?string $summary = null,
        public ?string $description = null,
        public array $tags = [],
        public ?string $operationId = null,
    ) {}
}
