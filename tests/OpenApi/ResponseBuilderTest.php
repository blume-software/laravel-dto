<?php

namespace Blume\LaravelDTO\Tests\OpenApi;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;
use Blume\LaravelDTO\OpenApi\ResponseBuilder;
use Blume\LaravelDTO\OpenApi\SchemaBuilder;

class ResponseBuilderTest extends TestCase
{
    private SchemaBuilder $schemaBuilder;

    private ResponseBuilder $builder;

    protected function setUp(): void
    {
        $this->schemaBuilder = new SchemaBuilder;
        $this->builder = new ResponseBuilder($this->schemaBuilder);
    }

    // -------------------------------------------------------------------------
    // getDefaultDescription
    // -------------------------------------------------------------------------

    public function test_get_default_description_for_known_status_codes(): void
    {
        $cases = [
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
        ];

        foreach ($cases as $code => $expected) {
            $this->assertSame($expected, $this->builder->getDefaultDescription($code), "Status $code");
        }
    }

    public function test_get_default_description_falls_back_to_generic(): void
    {
        $this->assertSame('Response', $this->builder->getDefaultDescription(418));
    }

    // -------------------------------------------------------------------------
    // getResponseInfo
    // -------------------------------------------------------------------------

    public function test_get_response_info_defaults_to_200(): void
    {
        $method = (new ReflectionClass(Fixtures\FakeController::class))->getMethod('show');
        // show() has an explicit #[Response(200, 'Item found')]
        $info = $this->builder->getResponseInfo($method);

        $this->assertSame(200, $info['statusCode']);
        $this->assertSame('Item found', $info['description']);
    }

    public function test_get_response_info_uses_default_description_when_attribute_has_none(): void
    {
        $method = (new ReflectionClass(Fixtures\FakeController::class))->getMethod('store');
        // store() has #[Response(statusCode: 201)] – no description
        $info = $this->builder->getResponseInfo($method);

        $this->assertSame(201, $info['statusCode']);
        $this->assertSame('Resource created successfully', $info['description']);
    }

    public function test_get_response_info_defaults_when_no_attribute(): void
    {
        $method = (new ReflectionClass(Fixtures\FakeController::class))->getMethod('noAttribute');
        $info = $this->builder->getResponseInfo($method);

        $this->assertSame(200, $info['statusCode']);
        $this->assertSame('Successful response', $info['description']);
    }

    // -------------------------------------------------------------------------
    // build – HTTP response types (no schema body)
    // -------------------------------------------------------------------------

    public function test_build_returns_empty_body_for_illuminate_response(): void
    {
        $method = (new ReflectionClass(Fixtures\FakeController::class))->getMethod('noContent');

        $responses = $this->builder->build($method);

        $this->assertArrayHasKey('200', $responses);
        $this->assertArrayNotHasKey('content', $responses['200']);
        $this->assertSame('Successful response', $responses['200']['description']);
    }

    public function test_build_returns_empty_body_for_redirect_response(): void
    {
        $method = (new ReflectionClass(Fixtures\FakeController::class))->getMethod('redirect');

        $responses = $this->builder->build($method);

        $this->assertArrayHasKey('302', $responses);
        $this->assertArrayNotHasKey('content', $responses['302']);
    }

    // -------------------------------------------------------------------------
    // build – SimpleResponseDTO (InfersOpenApiSchema)
    // -------------------------------------------------------------------------

    public function test_build_returns_ref_for_infers_schema_dto(): void
    {
        $method = (new ReflectionClass(Fixtures\FakeController::class))->getMethod('show');

        $responses = $this->builder->build($method);

        $this->assertArrayHasKey('200', $responses);
        $schema = $responses['200']['content']['application/json']['schema'];
        $this->assertSame('#/components/schemas/SimpleResponse', $schema['$ref']);
    }

    public function test_build_registers_schema_in_builder(): void
    {
        $method = (new ReflectionClass(Fixtures\FakeController::class))->getMethod('show');

        $this->builder->build($method);

        $this->assertTrue($this->schemaBuilder->has('SimpleResponse'));
    }

    // -------------------------------------------------------------------------
    // build – error cases
    // -------------------------------------------------------------------------

    public function test_build_throws_when_method_has_no_return_type(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/must have a return type/');

        $method = (new ReflectionClass(Fixtures\FakeController::class))->getMethod('withoutReturnType');
        $this->builder->build($method);
    }

    // -------------------------------------------------------------------------
    // build – NonPaginatedResponseDTO
    // -------------------------------------------------------------------------

    public function test_build_creates_non_paginated_schema_with_item_ref(): void
    {
        $method = (new ReflectionClass(Fixtures\FakeController::class))->getMethod('index');

        $responses = $this->builder->build($method);

        $this->assertArrayHasKey('200', $responses);
        $schema = $responses['200']['content']['application/json']['schema'];
        $this->assertSame('#/components/schemas/NonPaginatedResponseOfSimpleResponse', $schema['$ref']);
        $this->assertTrue($this->schemaBuilder->has('NonPaginatedResponseOfSimpleResponse'));
    }

    // -------------------------------------------------------------------------
    // extractGenericTypeFromDocComment
    // -------------------------------------------------------------------------

    public function test_extract_generic_type_throws_without_doccomment(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/no doc comment/');

        $method = (new ReflectionClass(Fixtures\FakeController::class))->getMethod('withGenericPaginated');
        $this->builder->extractGenericTypeFromDocComment($method);
    }

    public function test_extract_generic_type_resolves_class_from_doccomment(): void
    {
        $method = (new ReflectionClass(Fixtures\FakeController::class))->getMethod('paginated');

        $resolved = $this->builder->extractGenericTypeFromDocComment($method);

        $this->assertSame(Fixtures\SimpleResponseDTO::class, $resolved);
    }
}
