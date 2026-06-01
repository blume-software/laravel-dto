<?php

namespace Blume\LaravelDTO;

use Blume\LaravelDTO\Concerns\HasSchemaName;
use Blume\LaravelDTO\Contracts\InfersOpenApiSchema;

abstract class SimpleDTO extends BaseDTO implements InfersOpenApiSchema
{
    use HasSchemaName;
}
