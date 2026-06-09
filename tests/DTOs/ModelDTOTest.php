<?php

namespace BlumeSoftware\LaravelDTO\Tests\DTOs;

use BlumeSoftware\LaravelDTO\Attributes\Getter;
use BlumeSoftware\LaravelDTO\Attributes\Map;
use BlumeSoftware\LaravelDTO\ModelDTO;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class ModelDTOTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Mapping from array
    // -------------------------------------------------------------------------

    public function test_maps_values_from_array(): void
    {
        $dto = new class(['name' => 'Alice', 'email' => 'alice@example.com']) extends ModelDTO
        {
            public string $name;

            public string $email;
        };

        $this->assertSame('Alice', $dto->name);
        $this->assertSame('alice@example.com', $dto->email);
    }

    public function test_missing_keys_in_array_leave_property_uninitialised(): void
    {
        $dto = new class(['name' => 'Bob']) extends ModelDTO
        {
            public string $name;

            public string $email;
        };

        $this->assertSame('Bob', $dto->name);
        // email was not in the source array — property must not be set
        $this->assertFalse((new \ReflectionProperty($dto, 'email'))->isInitialized($dto));
    }

    public function test_pre_initialised_property_is_not_overwritten(): void
    {
        $dto = new class(['name' => 'Charlie']) extends ModelDTO
        {
            public string $name = 'DEFAULT';
        };

        $this->assertSame('DEFAULT', $dto->name);
    }

    // -------------------------------------------------------------------------
    // Mapping from Eloquent model
    // -------------------------------------------------------------------------

    public function test_maps_values_from_eloquent_model(): void
    {
        $model = new class extends Model
        {
            protected $attributes = ['title' => 'Hello', 'body' => 'World'];

            public function getAttribute($key)
            {
                return $this->attributes[$key] ?? null;
            }
        };

        $dto = new class($model) extends ModelDTO
        {
            public string $title;

            public string $body;
        };

        $this->assertSame('Hello', $dto->title);
        $this->assertSame('World', $dto->body);
    }

    // -------------------------------------------------------------------------
    // Map attribute
    // -------------------------------------------------------------------------

    public function test_map_attribute_reads_from_alternative_key(): void
    {
        $dto = new class(['full_name' => 'Dave']) extends ModelDTO
        {
            #[Map('full_name')]
            public string $name;
        };

        $this->assertSame('Dave', $dto->name);
    }

    // -------------------------------------------------------------------------
    // Getter attribute
    // -------------------------------------------------------------------------

    public function test_getter_attribute_calls_getter_method(): void
    {
        $dto = new class(['raw' => 'hello']) extends ModelDTO
        {
            public string $raw;

            #[Getter]
            public string $upper;

            public function getUpper(): string
            {
                return strtoupper($this->raw);
            }
        };

        $this->assertSame('HELLO', $dto->upper);
    }

    // -------------------------------------------------------------------------
    // toArray
    // -------------------------------------------------------------------------

    public function test_to_array_contains_all_mapped_properties(): void
    {
        $dto = new class(['x' => 1, 'y' => 2]) extends ModelDTO
        {
            public int $x;

            public int $y;
        };

        $this->assertSame(['x' => 1, 'y' => 2], $dto->toArray());
    }

    // -------------------------------------------------------------------------
    // toResponse
    // -------------------------------------------------------------------------

    public function test_to_response_returns_json_response_with_data_key(): void
    {
        $dto = new class(['id' => 42]) extends ModelDTO
        {
            public int $id;
        };

        $response = $dto->toResponse(new Request);
        $body = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('data', $body);
        $this->assertSame(['id' => 42], $body['data']);
    }

    // -------------------------------------------------------------------------
    // getSchemaName
    // -------------------------------------------------------------------------

    public function test_get_schema_name_returns_non_empty_string(): void
    {
        $dto = new class([]) extends ModelDTO {};

        $this->assertNotEmpty($dto::getSchemaName());
    }
}
