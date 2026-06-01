<?php

namespace BlumeSoftware\LaravelDTO\Tests\OpenApi\Fixtures;

use BlumeSoftware\LaravelDTO\RequestDTO;

class FakeRequest extends RequestDTO
{
    public string $search;

    public ?int $page;
}
