<?php

namespace Blume\LaravelDTO\Tests\OpenApi\Fixtures;

use Blume\LaravelDTO\BaseDTO;

class NullableUnionDTO extends BaseDTO
{
    public ?string $value;

    public string|int|null $mixed;
}
