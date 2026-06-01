<?php

namespace Blume\LaravelDTO\OpenApi;

use JsonException;

class OpenApiSpec
{
    public function __construct(
        protected array $spec
    ) {}

    public function toArray(): array
    {
        return $this->spec;
    }

    /**
     * @throws JsonException
     */
    public function toJson(): string
    {
        return json_encode($this->spec, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
