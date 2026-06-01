<?php

namespace BlumeSoftware\LaravelDTO;

use BlumeSoftware\LaravelDTO\Concerns\HasSchemaName;
use BlumeSoftware\LaravelDTO\Contracts\InfersOpenApiSchema;

abstract class SimpleDTO extends BaseDTO implements InfersOpenApiSchema
{
    use HasSchemaName;
}
