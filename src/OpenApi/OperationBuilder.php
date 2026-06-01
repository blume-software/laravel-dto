<?php

namespace Blume\LaravelDTO\OpenApi;

use Illuminate\Routing\Route as RouteObject;
use ReflectionMethod;
use Blume\LaravelDTO\OpenApi\Attributes\Operation;
use Blume\LaravelDTO\OpenApi\Attributes\PathItem;

/**
 * Assembles a single OpenAPI operation object from a route, its PHP attributes,
 * and reflection information about the controller method.
 */
class OperationBuilder
{
    public function __construct(
        private readonly DocBlockExtractor $docBlockExtractor,
        private readonly ResponseBuilder $responseBuilder,
        private readonly RequestBodyBuilder $requestBodyBuilder,
    ) {}

    public function build(
        RouteObject $route,
        PathItem $pathItem,
        Operation $operation,
        ReflectionMethod $reflectionMethod,
    ): array {

        $path = $this->normalizePath($route->uri());
        $httpMethod = strtolower($operation->method ?? $this->determineHttpMethod($route));

        $requestInfo = $this->requestBodyBuilder->build($reflectionMethod, $route);
        $responses = $this->responseBuilder->build($reflectionMethod);

        $operationSpec = array_filter([
            'summary' => $operation->summary ?? $this->docBlockExtractor->extractSummary($reflectionMethod),
            'description' => $operation->description ?? $this->docBlockExtractor->extractDescription($reflectionMethod),
            'operationId' => $operation->operationId ?? $this->generateOperationId($reflectionMethod),
            'tags' => $operation->tags ?: [],
            'parameters' => $requestInfo['parameters'],
            'requestBody' => $requestInfo['requestBody'],
            'responses' => $responses,
        ]);

        return [$path => [$httpMethod => $operationSpec]];
    }

    public function normalizePath(string $path): string
    {
        return '/'.ltrim($path, '/');
    }

    public function determineHttpMethod(RouteObject $route): string
    {
        $methods = array_filter($route->methods(), fn (string $m) => $m !== 'HEAD');

        return strtolower(array_values($methods)[0] ?? 'get');
    }

    public function generateOperationId(ReflectionMethod $method): string
    {
        return $method->getDeclaringClass()->getShortName().'.'.$method->getName();
    }
}
