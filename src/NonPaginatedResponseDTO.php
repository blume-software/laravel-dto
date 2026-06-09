<?php

namespace BlumeSoftware\LaravelDTO;

use BlumeSoftware\LaravelDTO\Concerns\HasSchemaName;
use BlumeSoftware\LaravelDTO\Contracts\InfersOpenApiSchema;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

/**
 * Laravel non-paginated response DTO
 *
 * @template T
 */
class NonPaginatedResponseDTO extends BaseDTO implements InfersOpenApiSchema, Responsable
{
    use HasSchemaName;

    /**
     * @var T[]
     */
    public array $data;

    public function __construct(array $data)
    {
        $this->data = $data['data'] ?? $data;
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
        ];
    }

    /**
     * Create a NonPaginatedResponseDTO from an array
     *
     * @param  callable|null  $mapper  Optional function to map items (e.g., Model to DTO)
     * @return NonPaginatedResponseDTO
     */
    public static function fromArray(array $items, ?callable $mapper = null): static
    {
        if ($mapper !== null) {
            $items = array_map($mapper, $items);
        }

        return new static(['data' => $items]);
    }

    /**
     * @param  SymfonyRequest  $request
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
        return 'NonPaginatedResponse';
    }
}
