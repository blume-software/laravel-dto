<?php

namespace BlumeSoftware\LaravelDTO\Tests\OpenApi\Fixtures;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use BlumeSoftware\LaravelDTO\NonPaginatedResponseDTO;
use BlumeSoftware\LaravelDTO\PaginatedResponseDTO;
use BlumeSoftware\LaravelDTO\OpenApi\Attributes\Operation;
use BlumeSoftware\LaravelDTO\OpenApi\Attributes\PathItem;
use BlumeSoftware\LaravelDTO\OpenApi\Attributes\Response as ResponseAttribute;

/**
 * Fixture controller for OpenAPI unit tests.
 */
#[PathItem()]
class FakeController
{
    /**
     * List all items.
     *
     * Returns a collection of available items.
     *
     * @return NonPaginatedResponseDTO<SimpleResponseDTO>
     */
    #[Operation(summary: 'List items', tags: ['items'])]
    public function index(): NonPaginatedResponseDTO
    {
        // fixture – never called
    }

    /**
     * Show a single item.
     */
    #[Operation]
    #[ResponseAttribute(statusCode: 200, description: 'Item found')]
    public function show(): SimpleResponseDTO
    {
        // fixture – never called
    }

    /**
     * Create an item.
     */
    #[Operation(operationId: 'items.create')]
    #[ResponseAttribute(statusCode: 201)]
    public function store(FakeRequest $request): SimpleResponseDTO
    {
        // fixture – never called
    }

    /** No doc summary here. */
    #[Operation]
    public function noContent(): Response
    {
        // fixture – never called
    }

    #[Operation]
    public function withGenericPaginated(): PaginatedResponseDTO
    {
        // fixture – missing @return annotation intentionally
    }

    /**
     * @return PaginatedResponseDTO<SimpleResponseDTO>
     */
    #[Operation]
    public function paginated(): PaginatedResponseDTO
    {
        // fixture – never called
    }

    #[Operation]
    #[ResponseAttribute(statusCode: 302, description: 'Redirect')]
    public function redirect(): RedirectResponse
    {
        // fixture – never called
    }

    public function noAttribute(): SimpleResponseDTO
    {
        // fixture – no #[Operation]
    }

    public function missingReturnType()
    {
        // fixture – no return type
    }

    #[Operation]
    public function withoutReturnType()
    {
        // fixture – no return type, has Operation attr
    }
}
