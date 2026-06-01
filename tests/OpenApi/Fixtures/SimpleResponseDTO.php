<?php

namespace BlumeSoftware\LaravelDTO\Tests\OpenApi\Fixtures;

use BlumeSoftware\LaravelDTO\BaseDTO;
use BlumeSoftware\LaravelDTO\Contracts\InfersOpenApiSchema;

class SimpleResponseDTO extends BaseDTO implements InfersOpenApiSchema
{
    public int $id;

    public string $name;

    public static function getSchemaName(): string
    {
        return 'SimpleResponse';
    }
}
