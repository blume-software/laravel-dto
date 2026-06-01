<?php

namespace Blume\LaravelDTO\Tests\OpenApi;

use Illuminate\Routing\Route;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Blume\LaravelDTO\OpenApi\Attributes\Operation;
use Blume\LaravelDTO\OpenApi\Attributes\PathItem;
use Blume\LaravelDTO\OpenApi\DocBlockExtractor;
use Blume\LaravelDTO\OpenApi\OperationBuilder;
use Blume\LaravelDTO\OpenApi\RequestBodyBuilder;
use Blume\LaravelDTO\OpenApi\ResponseBuilder;
use Blume\LaravelDTO\OpenApi\SchemaBuilder;

class OperationBuilderTest extends TestCase
{
    private OperationBuilder $builder;

    protected function setUp(): void
    {
        $schemaBuilder = new SchemaBuilder;

        $this->builder = new OperationBuilder(
            docBlockExtractor: new DocBlockExtractor,
            responseBuilder: new ResponseBuilder($schemaBuilder),
            requestBodyBuilder: new RequestBodyBuilder($schemaBuilder),
        );
    }

    // -------------------------------------------------------------------------
    // normalizePath
    // -------------------------------------------------------------------------

    public function test_normalize_path_adds_leading_slash(): void
    {
        $this->assertSame('/items', $this->builder->normalizePath('items'));
    }

    public function test_normalize_path_preserves_existing_slash(): void
    {
        $this->assertSame('/items/nested', $this->builder->normalizePath('/items/nested'));
    }

    public function test_normalize_path_keeps_parameters(): void
    {
        $this->assertSame('/items/{id}', $this->builder->normalizePath('items/{id}'));
    }

    // -------------------------------------------------------------------------
    // determineHttpMethod
    // -------------------------------------------------------------------------

    public function test_determine_http_method_lowercases_verb(): void
    {
        $route = new Route(['GET', 'HEAD'], 'items', []);

        $this->assertSame('get', $this->builder->determineHttpMethod($route));
    }

    public function test_determine_http_method_filters_head(): void
    {
        $route = new Route(['HEAD', 'GET'], 'items', []);

        $this->assertSame('get', $this->builder->determineHttpMethod($route));
    }

    public function test_determine_http_method_handles_post(): void
    {
        $route = new Route(['POST'], 'items', []);

        $this->assertSame('post', $this->builder->determineHttpMethod($route));
    }

    // -------------------------------------------------------------------------
    // generateOperationId
    // -------------------------------------------------------------------------

    public function test_generate_operation_id_uses_class_and_method_name(): void
    {
        $method = (new ReflectionClass(Fixtures\FakeController::class))->getMethod('show');

        $id = $this->builder->generateOperationId($method);

        $this->assertSame('FakeController.show', $id);
    }

    // -------------------------------------------------------------------------
    // build – operation spec structure
    // -------------------------------------------------------------------------

    public function test_build_returns_correct_path_and_method_keys(): void
    {
        $route = new Route(['GET', 'HEAD'], 'items', []);
        $pathItem = new PathItem;
        $operation = new Operation(tags: ['items']);
        $method = (new ReflectionClass(Fixtures\FakeController::class))->getMethod('show');

        $result = $this->builder->build($route, $pathItem, $operation, $method);

        $this->assertArrayHasKey('/items', $result);
        $this->assertArrayHasKey('get', $result['/items']);
    }

    public function test_build_uses_operation_attribute_values_over_docblock(): void
    {
        $route = new Route(['GET', 'HEAD'], 'items', []);
        $pathItem = new PathItem;
        $operation = new Operation(summary: 'Custom summary', description: 'Custom description');
        $method = (new ReflectionClass(Fixtures\FakeController::class))->getMethod('show');

        $result = $this->builder->build($route, $pathItem, $operation, $method);

        $spec = $result['/items']['get'];
        $this->assertSame('Custom summary', $spec['summary']);
        $this->assertSame('Custom description', $spec['description']);
    }

    public function test_build_uses_explicit_operation_id_when_provided(): void
    {
        $route = new Route(['POST'], 'items', []);
        $pathItem = new PathItem;
        $operation = new Operation(operationId: 'items.create');
        $method = (new ReflectionClass(Fixtures\FakeController::class))->getMethod('store');

        $result = $this->builder->build($route, $pathItem, $operation, $method);

        $spec = $result['/items']['post'];
        $this->assertSame('items.create', $spec['operationId']);
    }

    public function test_build_infers_http_method_from_route_when_not_in_operation(): void
    {
        $route = new Route(['DELETE'], 'items/{id}', []);
        $pathItem = new PathItem;
        $operation = new Operation;
        $method = (new ReflectionClass(Fixtures\FakeController::class))->getMethod('show');

        $result = $this->builder->build($route, $pathItem, $operation, $method);

        $this->assertArrayHasKey('delete', $result['/items/{id}']);
    }

    public function test_build_uses_route_uri_when_path_item_has_no_path(): void
    {
        $route = new Route(['GET'], 'api/items', []);
        $pathItem = new PathItem;
        $operation = new Operation;
        $method = (new ReflectionClass(Fixtures\FakeController::class))->getMethod('show');

        $result = $this->builder->build($route, $pathItem, $operation, $method);

        $this->assertArrayHasKey('/api/items', $result);
    }
}
