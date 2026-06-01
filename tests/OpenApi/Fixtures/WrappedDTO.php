<?php

namespace Blume\LaravelDTO\Tests\OpenApi\Fixtures;

use Blume\LaravelDTO\BaseDTO;
use Blume\LaravelDTO\Contracts\InfersOpenApiSchema;

class WrappedDTO extends BaseDTO implements InfersOpenApiSchema
{
    public const WRAPS_IN_DATA = true;

    public string $value;

    public static function getSchemaName(): string
    {
        return 'Wrapped';
    }
}
