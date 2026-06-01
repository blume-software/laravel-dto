<?php

namespace Blume\LaravelDTO\Contracts;

interface InfersOpenApiSchema
{
    /**
     * Get the OpenAPI schema name for this DTO.
     */
    public static function getSchemaName(): string;
}
