<?php

namespace Blume\LaravelDTO\Tests\OpenApi\Fixtures;

use Blume\LaravelDTO\BaseDTO;

class ArrayDTO extends BaseDTO
{
    public array $bare;

    /** @var string[] */
    public array $strings;

    /** @var array<int> */
    public array $ints;
}
