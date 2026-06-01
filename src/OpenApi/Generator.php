<?php

namespace Blume\LaravelDTO\OpenApi;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionException;
use Blume\LaravelDTO\OpenApi\Attributes\Operation;
use Blume\LaravelDTO\OpenApi\Attributes\PathItem;

/**
 * Orchestrates OpenAPI spec generation by scanning Laravel routes and
 * delegating to focused builder classes.
 */
class Generator
{
    public function generate(): OpenApiSpec
    {
        $schemaBuilder = new SchemaBuilder;

        $operationBuilder = new OperationBuilder(
            new DocBlockExtractor,
            new ResponseBuilder($schemaBuilder),
            new RequestBodyBuilder($schemaBuilder),
        );

        $spec = $this->initializeSpec();

        foreach (Route::getRoutes() as $route) {
            $routeInfo = $this->resolveRouteInfo($route);

            if ($routeInfo === null) {
                continue;
            }

            [$pathItemAttribute, $operationAttribute, $reflectionMethod] = $routeInfo;

            foreach ($operationBuilder->build($route, $pathItemAttribute, $operationAttribute, $reflectionMethod) as $path => $methods) {
                if (! isset($spec['paths'][$path])) {
                    $spec['paths'][$path] = [];
                }

                $spec['paths'][$path] = array_merge($spec['paths'][$path], $methods);
            }
        }

        $spec['components']['schemas'] = $schemaBuilder->getSchemas();

        return new OpenApiSpec($spec);
    }

    private function initializeSpec(): array
    {
        return [
            'openapi' => '3.1.0',
            'info' => [
                'title' => config('app.name', 'API'),
                'version' => '1.0.0',
            ],
            'paths' => [],
            'components' => [
                'schemas' => [],
            ],
        ];
    }

    /**
     * Extracts the PathItem + Operation attributes and the ReflectionMethod for a route.
     * Returns null when the route is not annotated for OpenAPI generation.
     */
    private function resolveRouteInfo(mixed $route): ?array
    {
        $action = $route->getAction();

        if (! isset($action['controller'])) {
            return null;
        }

        [$controller, $method] = Str::parseCallback($action['controller']);

        if (! $controller) {
            return null;
        }

        $method ??= '__invoke';

        try {
            $reflectionClass = new ReflectionClass($controller);
            $pathItemAttributes = $reflectionClass->getAttributes(PathItem::class);

            if (empty($pathItemAttributes)) {
                return null;
            }

            $reflectionMethod = $reflectionClass->getMethod($method);
            $operationAttributes = $reflectionMethod->getAttributes(Operation::class);

            if (empty($operationAttributes)) {
                return null;
            }

            return [
                $pathItemAttributes[0]->newInstance(),
                $operationAttributes[0]->newInstance(),
                $reflectionMethod,
            ];
        } catch (ReflectionException) {
            return null;
        }
    }
}
