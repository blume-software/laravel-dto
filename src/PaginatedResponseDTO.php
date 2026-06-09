<?php

namespace BlumeSoftware\LaravelDTO;

use BlumeSoftware\LaravelDTO\Concerns\HasSchemaName;
use BlumeSoftware\LaravelDTO\Contracts\InfersOpenApiSchema;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use ReflectionException;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

/**
 * Laravel paginated response DTO
 *
 * @template T
 */
class PaginatedResponseDTO extends BaseDTO implements InfersOpenApiSchema, Responsable
{
    use HasSchemaName;

    /**
     * @var T[]
     */
    public array $data;

    public PaginationLinksDTO $links;

    public PaginationMetaDTO $meta;

    public function __construct(array $data)
    {
        $this->data = $data['data'] ?? [];
        $this->links = PaginationLinksDTO::fromArray($data['links'] ?? []);
        $this->meta = PaginationMetaDTO::fromArray($data['meta'] ?? []);
    }

    public function toArray(): array
    {
        return [
            'data' => array_map(function ($item) {
                if (is_object($item) && method_exists($item, 'toArray')) {
                    return $item->toArray();
                }

                return $item;
            }, $this->data),
            'links' => $this->links->toArray(),
            'meta' => $this->meta->toArray(),
        ];
    }

    /**
     * Create a PaginatedResponseDTO from a Laravel paginator
     *
     * @param  callable|null  $mapper  Optional function to map items (e.g., Model to DTO)
     */
    public static function fromPaginator(LengthAwarePaginator $paginator, ?callable $mapper = null): static
    {
        $items = $paginator->items();

        if ($mapper !== null) {
            $items = array_map($mapper, $items);
        }

        return new static([
            'data' => $items,
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'from' => $paginator->firstItem() ?? 0,
                'last_page' => $paginator->lastPage(),
                'path' => $paginator->path(),
                'per_page' => $paginator->perPage(),
                'to' => $paginator->lastItem() ?? 0,
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * @param  SymfonyRequest  $request
     *
     * @throws ReflectionException
     */
    public function toResponse($request): JsonResponse
    {
        return new JsonResponse($this->toArray(), $this->statusCode);
    }

    /**
     * Get the OpenAPI schema name for this DTO.
     */
    public static function getSchemaName(): string
    {
        return 'PaginatedResponse';
    }
}
