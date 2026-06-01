<?php

namespace BlumeSoftware\LaravelDTO\Tests\OpenApi\Fixtures;

use BlumeSoftware\LaravelDTO\BaseDTO;

class NullableRefDTO extends BaseDTO
{
    public ?SimpleResponseDTO $item;
}
