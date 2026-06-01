<?php

namespace Blume\LaravelDTO\OpenApi;

use ReflectionMethod;
use ReflectionNamedType;
use RuntimeException;
use Blume\LaravelDTO\ModelDTO;
use Blume\LaravelDTO\NonPaginatedResponseDTO;
use Blume\LaravelDTO\PaginatedResponseDTO;
use Blume\LaravelDTO\OpenApi\Attributes\Response as ResponseAttribute;
use Blume\LaravelDTO\Contracts\InfersOpenApiSchema;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Builds the OpenAPI responses object for a single controller method.
 */
class ResponseBuilder
{
    public function __construct(
        private readonly SchemaBuilder $schemaBuilder,
    ) {}

    /**
     * @throws RuntimeException if the return type is missing, unsupported, or ambiguous.
     */
    public function build(ReflectionMethod $method): array
    {
        $returnType = $method->getReturnType();

        if (! $returnType) {
            throw new RuntimeException(sprintf(
                'Method %s::%s must have a return type declaration for OpenAPI generation',
                $method->getDeclaringClass()->getName(),
                $method->getName()
            ));
        }

        if (! ($returnType instanceof ReflectionNamedType)) {
            throw new RuntimeException(sprintf(
                'Unsupported return type for method %s::%s. Only named types implementing %s are supported.',
                $method->getDeclaringClass()->getName(),
                $method->getName(),
                InfersOpenApiSchema::class
            ));
        }

        $typeName = $returnType->getName();

        if (! class_exists($typeName)) {
            throw new RuntimeException(sprintf(
                'Return type %s for method %s::%s is not a valid class',
                $typeName,
                $method->getDeclaringClass()->getName(),
                $method->getName()
            ));
        }

        ['statusCode' => $statusCode, 'description' => $description] = $this->getResponseInfo($method);

        if (is_a($typeName, SymfonyResponse::class, allow_string: true)) {
            return [(string) $statusCode => ['description' => $description]];
        }

        if (! is_subclass_of($typeName, InfersOpenApiSchema::class)) {
            throw new RuntimeException(sprintf(
                'Return type %s for method %s::%s must implement %s',
                $typeName,
                $method->getDeclaringClass()->getName(),
                $method->getName(),
                InfersOpenApiSchema::class
            ));
        }

        if (is_subclass_of($typeName, PaginatedResponseDTO::class) || $typeName === PaginatedResponseDTO::class) {
            return $this->buildPaginatedResponse($method, $statusCode, $description);
        }

        if (is_subclass_of($typeName, NonPaginatedResponseDTO::class) || $typeName === NonPaginatedResponseDTO::class) {
            return $this->buildNonPaginatedResponse($method, $statusCode, $description);
        }

        $schemaName = $typeName::getSchemaName();

        if (! $this->schemaBuilder->has($schemaName)) {
            $this->schemaBuilder->register($schemaName, $this->schemaBuilder->buildSchemaFromClass($typeName));
        }

        if (is_subclass_of($typeName, ModelDTO::class) || $typeName === ModelDTO::class) {
            return [
                (string) $statusCode => [
                    'description' => $description,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'data' => ['$ref' => '#/components/schemas/'.$schemaName],
                                ],
                                'required' => ['data'],
                            ],
                        ],
                    ],
                ],
            ];
        }

        return [
            (string) $statusCode => [
                'description' => $description,
                'content' => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/'.$schemaName],
                    ],
                ],
            ],
        ];
    }

    public function getResponseInfo(ReflectionMethod $method): array
    {
        $attributes = $method->getAttributes(ResponseAttribute::class);

        if (! empty($attributes)) {
            $attr = $attributes[0]->newInstance();

            return [
                'statusCode' => $attr->statusCode,
                'description' => $attr->description ?? $this->getDefaultDescription($attr->statusCode),
            ];
        }

        return ['statusCode' => 200, 'description' => 'Successful response'];
    }

    public function getDefaultDescription(int $statusCode): string
    {
        return match ($statusCode) {
            200 => 'Successful response',
            201 => 'Resource created successfully',
            202 => 'Request accepted',
            204 => 'No content',
            400 => 'Bad request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Resource not found',
            422 => 'Validation error',
            500 => 'Internal server error',
            default => 'Response',
        };
    }

    private function buildPaginatedResponse(ReflectionMethod $method, int $statusCode, string $description): array
    {
        $itemType = $this->extractGenericTypeFromDocComment($method);
        $itemSchemaName = $this->ensureSchemaRegistered($itemType);
        $paginatedSchemaName = 'PaginatedResponseOf'.$itemSchemaName;

        if (! $this->schemaBuilder->has($paginatedSchemaName)) {
            $baseSchema = $this->schemaBuilder->buildSchemaFromClass(PaginatedResponseDTO::class);
            $baseSchema['properties']['data'] = [
                'type' => 'array',
                'items' => ['$ref' => '#/components/schemas/'.$itemSchemaName],
            ];
            $this->schemaBuilder->register($paginatedSchemaName, $baseSchema);
        }

        return $this->wrapInJsonContent((string) $statusCode, $description, $paginatedSchemaName);
    }

    private function buildNonPaginatedResponse(ReflectionMethod $method, int $statusCode, string $description): array
    {
        $itemType = $this->extractGenericTypeFromDocComment($method);
        $itemSchemaName = $this->ensureSchemaRegistered($itemType);
        $nonPaginatedSchemaName = 'NonPaginatedResponseOf'.$itemSchemaName;

        if (! $this->schemaBuilder->has($nonPaginatedSchemaName)) {
            $baseSchema = $this->schemaBuilder->buildSchemaFromClass(NonPaginatedResponseDTO::class);
            $baseSchema['properties']['data'] = [
                'type' => 'array',
                'items' => ['$ref' => '#/components/schemas/'.$itemSchemaName],
            ];
            $this->schemaBuilder->register($nonPaginatedSchemaName, $baseSchema);
        }

        return $this->wrapInJsonContent((string) $statusCode, $description, $nonPaginatedSchemaName);
    }

    /**
     * Reads the `@return ...DTO<ItemClass>` generic annotation to determine the collection item type.
     */
    public function extractGenericTypeFromDocComment(ReflectionMethod $method): string
    {
        $docComment = $method->getDocComment();

        if (! $docComment) {
            throw new RuntimeException(sprintf(
                'Method %s::%s returns a paginated/non-paginated DTO but has no doc comment. '.
                'Add @return PaginatedResponseDTO<YourDTO> to specify the item type.',
                $method->getDeclaringClass()->getName(),
                $method->getName()
            ));
        }

        if (preg_match('/@return\s+[a-zA-Z0-9_\\\\]*(?:Paginated|NonPaginated)ResponseDTO<([a-zA-Z0-9_\\\\]+)>/', $docComment, $matches)) {
            return $this->schemaBuilder->resolveClassName($matches[1], $method->getDeclaringClass());
        }

        throw new RuntimeException(sprintf(
            'Method %s::%s returns a paginated/non-paginated DTO but the doc comment does not specify the generic type. '.
            'Add @return PaginatedResponseDTO<YourDTO> or @return NonPaginatedResponseDTO<YourDTO>.',
            $method->getDeclaringClass()->getName(),
            $method->getName()
        ));
    }

    private function ensureSchemaRegistered(string $className): string
    {
        $schemaName = $this->schemaBuilder->getSchemaNameForType($className);

        if (! $this->schemaBuilder->has($schemaName)) {
            $this->schemaBuilder->register($schemaName, $this->schemaBuilder->buildSchemaFromClass($className));
        }

        return $schemaName;
    }

    private function wrapInJsonContent(string $statusCode, string $description, string $schemaName): array
    {
        return [
            $statusCode => [
                'description' => $description,
                'content' => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/'.$schemaName],
                    ],
                ],
            ],
        ];
    }
}
