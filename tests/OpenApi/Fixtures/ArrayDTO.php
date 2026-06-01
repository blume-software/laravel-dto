<?php

namespace BlumeSoftware\LaravelDTO\Tests\OpenApi\Fixtures;

use BlumeSoftware\LaravelDTO\BaseDTO;

class ArrayDTO extends BaseDTO
{
    public array $bare;

    /** @var string[] */
    public array $strings;

    /** @var array<int> */
    public array $ints;
}
