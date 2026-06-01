<?php

namespace Blume\LaravelDTO\Tests\DTOs;

use Illuminate\Pagination\LengthAwarePaginator;
use PHPUnit\Framework\TestCase;
use Blume\LaravelDTO\ModelDTO;
use Blume\LaravelDTO\PaginatedResponseDTO;
use Symfony\Component\HttpFoundation\Request;

class PaginatedResponseDTOTest extends TestCase
{
    private function makePaginator(array $items, int $total = 10, int $perPage = 5, int $page = 1): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            items: $items,
            total: $total,
            perPage: $perPage,
            currentPage: $page,
            options: ['path' => 'http://example.com/items'],
        );
    }

    // -------------------------------------------------------------------------
    // fromPaginator
    // -------------------------------------------------------------------------

    public function test_from_paginator_populates_data(): void
    {
        $paginator = $this->makePaginator([['id' => 1], ['id' => 2]]);

        $dto = PaginatedResponseDTO::fromPaginator($paginator);

        $this->assertCount(2, $dto->data);
    }

    public function test_from_paginator_applies_mapper(): void
    {
        $paginator = $this->makePaginator([['id' => 1], ['id' => 2]]);

        $dto = PaginatedResponseDTO::fromPaginator(
            $paginator,
            fn ($item) => ['mapped_id' => $item['id'] * 10]
        );

        $this->assertSame(['mapped_id' => 10], $dto->data[0]);
        $this->assertSame(['mapped_id' => 20], $dto->data[1]);
    }

    public function test_from_paginator_sets_meta_values(): void
    {
        $paginator = $this->makePaginator([['id' => 1]], total: 20, perPage: 5, page: 2);

        $dto = PaginatedResponseDTO::fromPaginator($paginator);

        $this->assertSame(2, $dto->meta->current_page);
        $this->assertSame(20, $dto->meta->total);
        $this->assertSame(5, $dto->meta->per_page);
    }

    public function test_from_paginator_sets_links(): void
    {
        $paginator = $this->makePaginator([['id' => 1]], total: 10, perPage: 5, page: 2);

        $dto = PaginatedResponseDTO::fromPaginator($paginator);

        $this->assertNotNull($dto->links->first);
        $this->assertNotNull($dto->links->prev);
    }

    // -------------------------------------------------------------------------
    // toArray
    // -------------------------------------------------------------------------

    public function test_to_array_has_expected_keys(): void
    {
        $dto = PaginatedResponseDTO::fromPaginator($this->makePaginator([['a' => 1]]));

        $array = $dto->toArray();

        $this->assertArrayHasKey('data', $array);
        $this->assertArrayHasKey('links', $array);
        $this->assertArrayHasKey('meta', $array);
    }

    public function test_to_array_calls_to_array_on_dto_items(): void
    {
        $item = new class(['score' => 99]) extends ModelDTO
        {
            public int $score;
        };

        $paginator = $this->makePaginator([$item]);
        $dto = PaginatedResponseDTO::fromPaginator($paginator);

        $array = $dto->toArray();

        $this->assertSame(['score' => 99], $array['data'][0]);
    }

    // -------------------------------------------------------------------------
    // toResponse
    // -------------------------------------------------------------------------

    public function test_to_response_returns_json_response(): void
    {
        $dto = PaginatedResponseDTO::fromPaginator($this->makePaginator([]));

        $response = $dto->toResponse(new Request);

        $this->assertSame(200, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('meta', $body);
    }

    // -------------------------------------------------------------------------
    // getSchemaName
    // -------------------------------------------------------------------------

    public function test_get_schema_name_returns_paginated_response(): void
    {
        $this->assertSame('PaginatedResponse', PaginatedResponseDTO::getSchemaName());
    }

    // -------------------------------------------------------------------------
    // Direct constructor with raw array
    // -------------------------------------------------------------------------

    public function test_constructor_accepts_raw_array(): void
    {
        $dto = new PaginatedResponseDTO([
            'data' => [['id' => 5]],
            'links' => ['first' => 'http://example.com?page=1', 'last' => null, 'prev' => null, 'next' => null],
            'meta' => ['current_page' => 1, 'from' => 1, 'last_page' => 1, 'path' => '/', 'per_page' => 15, 'to' => 1, 'total' => 1],
        ]);

        $this->assertCount(1, $dto->data);
        $this->assertSame('http://example.com?page=1', $dto->links->first);
        $this->assertSame(1, $dto->meta->total);
    }
}
