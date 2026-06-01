<?php

namespace Blume\LaravelDTO\Tests\OpenApi\Fixtures;

use Blume\LaravelDTO\BaseDTO;

class NullableRefDTO extends BaseDTO
{
    public ?SimpleResponseDTO $item;
}
