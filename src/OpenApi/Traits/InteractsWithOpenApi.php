<?php

namespace BlumeSoftware\LaravelDTO\OpenApi\Traits;

trait InteractsWithOpenApi
{
    public static function openapiEnabled(): bool
    {
        return config('openapi.enabled') === true;
    }
}
