<?php

namespace BlumeSoftware\LaravelDTO\Tests\OpenApi;

use Illuminate\Routing\Route;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use BlumeSoftware\LaravelDTO\OpenApi\RequestBodyBuilder;
use BlumeSoftware\LaravelDTO\OpenApi\SchemaBuilder;

class RequestBodyBuilderTest extends TestCase
{
    private SchemaBuilder $schemaBuilder;

    private RequestBodyBuilder $builder;

    protected function setUp(): void
    {
        $this->schemaBuilder = new SchemaBuilder;
        $this->builder = new RequestBodyBuilder($this->schemaBuilder);
    }

    // -------------------------------------------------------------------------
    // extractRequestDtoInfo – GET → query params
    // -------------------------------------------------------------------------

    public function test_get_route_produces_query_parameters(): void
    {
        $route = $this->makeRoute(['GET', 'HEAD'], 'items');

        $info = $this->builder->extractRequestDtoInfo(Fixtures\FakeRequest::class, $route);

        $this->assertNotEmpty($info['queryParams']);
        $this->assertEmpty($info['bodyFields']);
        $this->assertNull($info['requestBody']);
    }

    // -------------------------------------------------------------------------
    // extractRequestDtoInfo – POST → request body
    // -------------------------------------------------------------------------

    public function test_post_route_produces_request_body(): void
    {
        $route = $this->makeRoute(['POST'], 'items');

        $info = $this->builder->extractRequestDtoInfo(Fixtures\FakeRequest::class, $route);

        $this->assertEmpty($info['queryParams']);
        $this->assertNotNull($info['requestBody']);
        $this->assertSame(
            '#/components/schemas/FakeRequest',
            $info['requestBody']['content']['application/json']['schema']['$ref']
        );
    }

    // -------------------------------------------------------------------------
    // build – path parameters
    // -------------------------------------------------------------------------

    public function test_build_includes_path_parameters_from_route(): void
    {
        $route = $this->makeRoute(['GET'], 'items/{id}');
        $method = (new ReflectionClass(Fixtures\FakeController::class))->getMethod('show');

        $result = $this->builder->build($method, $route);

        $pathParam = $this->findParam($result['parameters'], 'id');
        $this->assertSame('path', $pathParam['in']);
        $this->assertTrue($pathParam['required']);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeRoute(array $methods, string $uri): Route
    {
        return new Route($methods, $uri, []);
    }

    private function findParam(array $params, string $name): array
    {
        foreach ($params as $p) {
            if ($p['name'] === $name) {
                return $p;
            }
        }

        $this->fail("Parameter '$name' not found in params list");
    }
}
