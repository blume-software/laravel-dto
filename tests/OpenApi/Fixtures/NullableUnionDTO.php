<?php

namespace BlumeSoftware\LaravelDTO\Tests\OpenApi\Fixtures;

use BlumeSoftware\LaravelDTO\BaseDTO;

class NullableUnionDTO extends BaseDTO
{
    public ?string $value;

    public string|int|null $mixed;
}
