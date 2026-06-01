<?php

namespace Blume\LaravelDTO\OpenApi;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Route as RouteObject;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;
use Blume\LaravelDTO\Attributes\Map;
use Blume\LaravelDTO\RequestDTO;

/**
 * Builds the OpenAPI parameters and requestBody for a single controller method.
 */
class RequestBodyBuilder
{
    public function __construct(
        private readonly SchemaBuilder $schemaBuilder,
    ) {}

    public function build(ReflectionMethod $method, RouteObject $route): array
    {
        $parameters = $this->buildPathParameters($route, $method);
        $requestBody = null;

        foreach ($method->getParameters() as $parameter) {
            $type = $parameter->getType();

            if (! $type instanceof ReflectionNamedType) {
                continue;
            }

            $typeName = $type->getName();

            if (! class_exists($typeName) || ! is_subclass_of($typeName, RequestDTO::class)) {
                continue;
            }

            $info = $this->extractRequestDtoInfo($typeName, $route);

            if ($info['queryParams'] !== []) {
                $parameters = array_merge($parameters, $info['queryParams']);
            }

            if ($info['requestBody'] !== null) {
                $requestBody = $info['requestBody'];
            }
        }

        return ['parameters' => $parameters, 'requestBody' => $requestBody];
    }

    public function extractRequestDtoInfo(string $dtoClass, RouteObject $route): array
    {
        $schemaName = $this->schemaBuilder->getSchemaNameForType($dtoClass);

        if (! $this->schemaBuilder->has($schemaName)) {
            $this->schemaBuilder->register($schemaName, $this->schemaBuilder->buildSchemaFromClass($dtoClass));
        }

        if (! $this->routeHasBody($route)) {
            return [
                'queryParams' => $this->buildQueryParameters($dtoClass),
                'requestBody' => null,
            ];
        }

        return [
            'queryParams' => [],
            'requestBody' => [
                'required' => true,
                'content' => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/'.$schemaName],
                    ],
                ],
            ],
        ];
    }

    /**
     * Build query parameters honoring `Map` (dot-notation) attributes.
     *
     * Properties without `Map` get a flat parameter; properties whose `Map` key
     * starts with a prefix (e.g. `pagination.perPage`) collapse into a single
     * `deepObject` parameter (e.g. `pagination[perPage]=...`).
     */
    public function buildQueryParameters(string $dtoClass): array
    {
        $reflection = new ReflectionClass($dtoClass);
        $flat = [];
        $flatRequired = [];
        $groups = [];

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $type = $property->getType();

            if (! $type) {
                continue;
            }

            $propertyName = $property->getName();
            $schema = $this->schemaBuilder->getSchemaFromType($type, $property);
            $schema = $this->schemaBuilder->applyValidationConstraints($schema, $property);
            $schema = $this->schemaBuilder->applyExample($schema, $property, $propertyName);

            $sourceKey = $this->resolveMapKey($property, $propertyName);
            $isRequired = $this->schemaBuilder->isPropertyRequired($property, $type, isRequest: true);

            if (! str_contains($sourceKey, '.')) {
                $flat[$sourceKey] = $schema;

                if ($isRequired) {
                    $flatRequired[] = $sourceKey;
                }

                continue;
            }

            [$prefix, $tail] = explode('.', $sourceKey, 2);
            $groups[$prefix]['properties'][$tail] = $schema;

            if ($isRequired) {
                $groups[$prefix]['required'][] = $tail;
            }
        }

        return [
            ...$this->renderFlatParams($flat, $flatRequired),
            ...$this->renderGroupedParams($groups),
        ];
    }

    private function renderFlatParams(array $flat, array $required): array
    {
        $params = [];

        foreach ($flat as $name => $schema) {
            $param = [
                'name' => $name,
                'in' => 'query',
                'required' => in_array($name, $required, strict: true),
                'schema' => $schema,
            ];

            if ($this->shouldUseDeepObject($schema)) {
                $param['style'] = 'deepObject';
                $param['explode'] = true;
            }

            $params[] = $param;
        }

        return $params;
    }

    /**
     * Arrays of objects and bare objects need `deepObject` style so the URL
     * shape (e.g. `filters[0][field]=...`) matches what FE actually sends.
     */
    private function shouldUseDeepObject(array $schema): bool
    {
        if (($schema['type'] ?? null) === 'array') {
            $items = $schema['items'] ?? [];

            return isset($items['$ref']) || ($items['type'] ?? null) === 'object';
        }

        return ($schema['type'] ?? null) === 'object' || isset($schema['$ref']);
    }

    private function renderGroupedParams(array $groups): array
    {
        $params = [];

        foreach ($groups as $name => $group) {
            $schema = ['type' => 'object', 'properties' => $group['properties'] ?? []];

            if (! empty($group['required'])) {
                $schema['required'] = array_values(array_unique($group['required']));
            }

            $params[] = [
                'name' => $name,
                'in' => 'query',
                'required' => false,
                'style' => 'deepObject',
                'explode' => true,
                'schema' => $schema,
            ];
        }

        return $params;
    }

    private function resolveMapKey(ReflectionProperty $property, string $fallback): string
    {
        foreach ($property->getAttributes(Map::class) as $attribute) {
            return $attribute->newInstance()->key;
        }

        return $fallback;
    }

    private function buildPathParameters(RouteObject $route, ReflectionMethod $method): array
    {
        $methodParamTypes = [];

        foreach ($method->getParameters() as $parameter) {
            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType) {
                $methodParamTypes[$parameter->getName()] = $type->getName();
            }
        }

        return array_map(
            fn (string $name) => [
                'name' => $name,
                'in' => 'path',
                'required' => true,
                'schema' => $this->resolvePathParamSchema($methodParamTypes[$name] ?? null),
            ],
            $route->parameterNames(),
        );
    }

    private function resolvePathParamSchema(?string $methodParamType): array
    {
        if ($methodParamType !== null && class_exists($methodParamType) && is_subclass_of($methodParamType, Model::class)) {
            $model = new $methodParamType;
            $isInt = $model->getKeyType() === 'int';

            return [
                'type' => $isInt ? 'integer' : 'string',
                'example' => $isInt ? 1 : 'abc123',
            ];
        }

        return ['type' => 'string'];
    }

    private function routeHasBody(RouteObject $route): bool
    {
        foreach ($route->methods() as $method) {
            if (in_array(strtoupper($method), ['POST', 'PATCH', 'PUT'], strict: true)) {
                return true;
            }
        }

        return false;
    }
}
