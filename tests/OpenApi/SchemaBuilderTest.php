<?php

namespace BlumeSoftware\LaravelDTO\Tests\OpenApi;

use BlumeSoftware\LaravelDTO\OpenApi\SchemaBuilder;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class SchemaBuilderTest extends TestCase
{
    private SchemaBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new SchemaBuilder;
    }

    // -------------------------------------------------------------------------
    // Schema registry
    // -------------------------------------------------------------------------

    public function test_registry_is_empty_initially(): void
    {
        $this->assertSame([], $this->builder->getSchemas());
    }

    public function test_has_returns_false_for_unknown_schema(): void
    {
        $this->assertFalse($this->builder->has('Missing'));
    }

    public function test_register_and_has_round_trip(): void
    {
        $this->builder->register('Foo', ['type' => 'object']);

        $this->assertTrue($this->builder->has('Foo'));
        $this->assertSame(['Foo' => ['type' => 'object']], $this->builder->getSchemas());
    }

    // -------------------------------------------------------------------------
    // getSchemaNameForType
    // -------------------------------------------------------------------------

    public function test_get_schema_name_for_infers_schema_implementor(): void
    {
        $name = $this->builder->getSchemaNameForType(Fixtures\SimpleResponseDTO::class);

        $this->assertSame('SimpleResponse', $name);
    }

    public function test_get_schema_name_falls_back_to_short_class_name(): void
    {
        $name = $this->builder->getSchemaNameForType(\stdClass::class);

        $this->assertSame('stdClass', $name);
    }

    // -------------------------------------------------------------------------
    // buildSchemaFromClass – primitives
    // -------------------------------------------------------------------------

    public function test_build_schema_maps_primitive_types(): void
    {
        $schema = $this->builder->buildSchemaFromClass(Fixtures\PrimitiveDTO::class);

        $this->assertSame('object', $schema['type']);
        $this->assertSame(['type' => 'integer'], $schema['properties']['count']);
        $this->assertSame(['type' => 'number', 'format' => 'float'], $schema['properties']['score']);
        $this->assertSame(['type' => 'boolean'], $schema['properties']['active']);
        $this->assertSame(['type' => 'string'], $schema['properties']['label']);
    }

    public function test_build_schema_marks_non_nullable_properties_as_required(): void
    {
        $schema = $this->builder->buildSchemaFromClass(Fixtures\PrimitiveDTO::class);

        $this->assertContains('count', $schema['required']);
        $this->assertContains('label', $schema['required']);
        $this->assertNotContains('nickname', $schema['required']);
    }

    // -------------------------------------------------------------------------
    // OpenAPI 3.1 nullable representation
    // -------------------------------------------------------------------------

    public function test_nullable_primitive_uses_type_array(): void
    {
        $schema = $this->builder->buildSchemaFromClass(Fixtures\PrimitiveDTO::class);

        // ?string $nickname should be { type: ['string', 'null'] }
        $this->assertSame(['type' => ['string', 'null']], $schema['properties']['nickname']);
    }

    public function test_nullable_dto_ref_wraps_in_one_of_with_null(): void
    {
        $schema = $this->builder->buildSchemaFromClass(Fixtures\NullableRefDTO::class);

        $prop = $schema['properties']['item'];
        $this->assertArrayHasKey('oneOf', $prop);
        $refs = array_column($prop['oneOf'], '$ref');
        $this->assertContains('#/components/schemas/SimpleResponse', $refs);
        $types = array_column($prop['oneOf'], 'type');
        $this->assertContains('null', $types);
    }

    public function test_union_type_with_null_uses_type_array_for_single_type(): void
    {
        $schema = $this->builder->buildSchemaFromClass(Fixtures\NullableUnionDTO::class);

        // string|null → { type: ['string', 'null'] }
        $this->assertSame(['type' => ['string', 'null']], $schema['properties']['value']);
    }

    public function test_union_type_with_null_adds_null_to_one_of(): void
    {
        $schema = $this->builder->buildSchemaFromClass(Fixtures\NullableUnionDTO::class);

        // string|int|null → oneOf with null
        $prop = $schema['properties']['mixed'];
        $this->assertArrayHasKey('oneOf', $prop);
        $types = array_column($prop['oneOf'], 'type');
        $this->assertContains('null', $types);
        $this->assertContains('string', $types);
        $this->assertContains('integer', $types);
    }

    public function test_build_schema_excludes_base_dto_properties(): void
    {
        $schema = $this->builder->buildSchemaFromClass(Fixtures\PrimitiveDTO::class);

        $this->assertArrayNotHasKey('properties', $schema['properties']);
        $this->assertArrayNotHasKey('statusCode', $schema['properties']);
    }

    // -------------------------------------------------------------------------
    // buildSchemaFromClass – WRAPS_IN_DATA constant
    // -------------------------------------------------------------------------

    public function test_build_schema_wraps_in_data_when_constant_is_set(): void
    {
        $schema = $this->builder->buildSchemaFromClass(Fixtures\WrappedDTO::class);

        $this->assertSame('object', $schema['type']);
        $this->assertArrayHasKey('data', $schema['properties']);
        $this->assertSame(['data'], $schema['required']);
        $this->assertSame('object', $schema['properties']['data']['type']);
    }

    // -------------------------------------------------------------------------
    // getSchemaFromTypeName – built-in aliases
    // -------------------------------------------------------------------------

    public function test_get_schema_from_type_name_handles_php_primitive_aliases(): void
    {
        $this->assertSame(['type' => 'integer'], $this->builder->getSchemaFromTypeName('int'));
        $this->assertSame(['type' => 'integer'], $this->builder->getSchemaFromTypeName('integer'));
        $this->assertSame(['type' => 'number', 'format' => 'float'], $this->builder->getSchemaFromTypeName('float'));
        $this->assertSame(['type' => 'boolean'], $this->builder->getSchemaFromTypeName('bool'));
        $this->assertSame(['type' => 'boolean'], $this->builder->getSchemaFromTypeName('boolean'));
        $this->assertSame(['type' => 'string'], $this->builder->getSchemaFromTypeName('string'));
    }

    public function test_get_schema_from_type_name_falls_back_to_string_for_unknown(): void
    {
        $this->assertSame(['type' => 'string'], $this->builder->getSchemaFromTypeName('NonExistentClass'));
    }

    // -------------------------------------------------------------------------
    // getSchemaFromArrayType via buildSchemaFromClass
    // -------------------------------------------------------------------------

    public function test_array_property_without_docblock_defaults_to_empty(): void
    {
        $schema = $this->builder->buildSchemaFromClass(Fixtures\ArrayDTO::class);

        $this->assertSame('array', $schema['properties']['bare']['type']);
        $this->assertSame([], $schema['properties']['bare']['items']);
    }

    public function test_array_property_with_bracket_docblock_resolves_items(): void
    {
        $schema = $this->builder->buildSchemaFromClass(Fixtures\ArrayDTO::class);

        $this->assertSame('array', $schema['properties']['strings']['type']);
        $this->assertSame(['type' => 'string'], $schema['properties']['strings']['items']);
    }

    public function test_array_property_with_generic_docblock_resolves_items(): void
    {
        $schema = $this->builder->buildSchemaFromClass(Fixtures\ArrayDTO::class);

        $this->assertSame('array', $schema['properties']['ints']['type']);
        $this->assertSame(['type' => 'integer'], $schema['properties']['ints']['items']);
    }

    // -------------------------------------------------------------------------
    // buildSchemaFromEnum
    // -------------------------------------------------------------------------

    public function test_build_schema_from_string_backed_enum(): void
    {
        $schema = $this->builder->buildSchemaFromEnum(Fixtures\StatusEnum::class);

        $this->assertSame('string', $schema['type']);
        $this->assertContains('active', $schema['enum']);
        $this->assertContains('inactive', $schema['enum']);
    }

    public function test_build_schema_from_int_backed_enum(): void
    {
        $schema = $this->builder->buildSchemaFromEnum(Fixtures\PriorityEnum::class);

        $this->assertSame('integer', $schema['type']);
        $this->assertContains(1, $schema['enum']);
        $this->assertContains(2, $schema['enum']);
    }

    // -------------------------------------------------------------------------
    // resolveClassName
    // -------------------------------------------------------------------------

    public function test_resolve_class_name_returns_fqn_for_leading_backslash(): void
    {
        $context = new ReflectionClass(Fixtures\PrimitiveDTO::class);
        $resolved = $this->builder->resolveClassName('\\stdClass', $context);

        $this->assertSame('stdClass', $resolved);
    }

    public function test_resolve_class_name_throws_for_unknown_fqn(): void
    {
        $this->expectException(\RuntimeException::class);

        $context = new ReflectionClass(Fixtures\PrimitiveDTO::class);
        $this->builder->resolveClassName('\\Does\\Not\\Exist', $context);
    }
}
