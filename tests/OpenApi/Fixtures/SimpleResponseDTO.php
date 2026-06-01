<?php

namespace Blume\LaravelDTO\Tests\OpenApi\Fixtures;

use Blume\LaravelDTO\BaseDTO;
use Blume\LaravelDTO\Contracts\InfersOpenApiSchema;

class SimpleResponseDTO extends BaseDTO implements InfersOpenApiSchema
{
    public int $id;

    public string $name;

    public static function getSchemaName(): string
    {
        return 'SimpleResponse';
    }
}
