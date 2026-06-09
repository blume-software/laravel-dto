<?php

namespace BlumeSoftware\LaravelDTO\Tests\DTOs;

use BlumeSoftware\LaravelDTO\ModelDTO;
use BlumeSoftware\LaravelDTO\NonPaginatedResponseDTO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class NonPaginatedResponseDTOTest extends TestCase
{
    // -------------------------------------------------------------------------
    // fromArray
    // -------------------------------------------------------------------------

    public function test_from_array_populates_data(): void
    {
        $dto = NonPaginatedResponseDTO::fromArray([['id' => 1], ['id' => 2]]);

        $this->assertCount(2, $dto->data);
    }

    public function test_from_array_applies_mapper(): void
    {
        $dto = NonPaginatedResponseDTO::fromArray(
            [['id' => 1], ['id' => 2]],
            fn ($item) => ['uid' => $item['id'] + 100]
        );

        $this->assertSame(['uid' => 101], $dto->data[0]);
        $this->assertSame(['uid' => 102], $dto->data[1]);
    }

    // -------------------------------------------------------------------------
    // Constructor
    // -------------------------------------------------------------------------

    public function test_constructor_accepts_data_key(): void
    {
        $dto = new NonPaginatedResponseDTO(['data' => [['x' => 1]]]);

        $this->assertSame([['x' => 1]], $dto->data);
    }

    public function test_constructor_accepts_flat_array(): void
    {
        $dto = new NonPaginatedResponseDTO([['x' => 1], ['x' => 2]]);

        $this->assertCount(2, $dto->data);
    }

    // -------------------------------------------------------------------------
    // toArray
    // -------------------------------------------------------------------------

    public function test_to_array_wraps_in_data_key(): void
    {
        $dto = NonPaginatedResponseDTO::fromArray([['id' => 10]]);

        $this->assertArrayHasKey('data', $dto->toArray());
    }

    public function test_to_array_calls_to_array_on_dto_items(): void
    {
        $item = new class(['val' => 7]) extends ModelDTO
        {
            public int $val;
        };

        $dto = NonPaginatedResponseDTO::fromArray([$item]);

        $this->assertSame(['val' => 7], $dto->toArray()['data'][0]);
    }

    // -------------------------------------------------------------------------
    // toResponse
    // -------------------------------------------------------------------------

    public function test_to_response_returns_correct_status_and_body(): void
    {
        $dto = NonPaginatedResponseDTO::fromArray([['id' => 1]]);
        $dto->setStatusCode(200);

        $response = $dto->toResponse(new Request);
        $body = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertArrayHasKey('data', $body);
    }

    // -------------------------------------------------------------------------
    // getSchemaName
    // -------------------------------------------------------------------------

    public function test_get_schema_name_returns_non_paginated_response(): void
    {
        $this->assertSame('NonPaginatedResponse', NonPaginatedResponseDTO::getSchemaName());
    }
}
