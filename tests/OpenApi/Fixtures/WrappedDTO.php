<?php

namespace BlumeSoftware\LaravelDTO\Tests\OpenApi\Fixtures;

use BlumeSoftware\LaravelDTO\BaseDTO;
use BlumeSoftware\LaravelDTO\Contracts\InfersOpenApiSchema;

class WrappedDTO extends BaseDTO implements InfersOpenApiSchema
{
    public const WRAPS_IN_DATA = true;

    public string $value;

    public static function getSchemaName(): string
    {
        return 'Wrapped';
    }
}
