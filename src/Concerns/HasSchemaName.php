<?php

namespace BlumeSoftware\LaravelDTO\Concerns;

trait HasSchemaName
{
    /**
     * Derives the OpenAPI schema name from the short class name.
     * Override in concrete DTOs to provide a custom name.
     */
    public static function getSchemaName(): string
    {
        return class_basename(static::class);
    }
}
