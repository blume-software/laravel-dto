<?php

namespace BlumeSoftware\LaravelDTO\Tests\OpenApi\Fixtures;

use BlumeSoftware\LaravelDTO\BaseDTO;

class PrimitiveDTO extends BaseDTO
{
    public int $count;

    public float $score;

    public bool $active;

    public string $label;

    public ?string $nickname;
}
