<?php

namespace Blume\LaravelDTO\Tests\OpenApi\Fixtures;

use Blume\LaravelDTO\RequestDTO;

class FakeRequest extends RequestDTO
{
    public string $search;

    public ?int $page;
}
