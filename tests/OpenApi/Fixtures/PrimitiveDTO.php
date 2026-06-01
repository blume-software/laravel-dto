<?php

namespace Blume\LaravelDTO\Tests\OpenApi\Fixtures;

use Blume\LaravelDTO\BaseDTO;

class PrimitiveDTO extends BaseDTO
{
    public int $count;

    public float $score;

    public bool $active;

    public string $label;

    public ?string $nickname;
}
