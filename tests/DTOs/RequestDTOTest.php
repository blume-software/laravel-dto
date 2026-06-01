<?php

namespace BlumeSoftware\LaravelDTO\Tests\DTOs;

use Illuminate\Validation\ValidationException;
use BlumeSoftware\LaravelDTO\Attributes\Cast;
use BlumeSoftware\LaravelDTO\Attributes\DefaultValue;
use BlumeSoftware\LaravelDTO\Attributes\Validation\IsInt;
use BlumeSoftware\LaravelDTO\Attributes\Validation\IsString;
use BlumeSoftware\LaravelDTO\Attributes\Validation\Max;
use BlumeSoftware\LaravelDTO\Attributes\Validation\Min;
use BlumeSoftware\LaravelDTO\Attributes\Validation\Nullable;
use BlumeSoftware\LaravelDTO\Attributes\Validation\Required;
use BlumeSoftware\LaravelDTO\Casts\EnumCast;
use BlumeSoftware\LaravelDTO\RequestDTO;
use BlumeSoftware\LaravelDTO\Tests\TestCase;

enum RequestTestColor: string
{
    case Red = 'red';
    case Blue = 'blue';
}

class RequestDTOTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Basic data mapping
    // -------------------------------------------------------------------------

    public function test_valid_data_is_mapped_to_properties(): void
    {
        $dto = new class(['name' => 'Alice', 'age' => 25]) extends RequestDTO
        {
            #[Required]
            #[IsString]
            public string $name;

            #[Required]
            #[IsInt]
            public int $age;

            protected function rules(): array
            {
                return [];
            }
        };

        $this->assertSame('Alice', $dto->name);
        $this->assertSame(25, $dto->age);
    }

    // -------------------------------------------------------------------------
    // Validation
    // -------------------------------------------------------------------------

    public function test_validation_exception_thrown_for_missing_required_field(): void
    {
        $this->expectException(ValidationException::class);

        new class(['age' => 30]) extends RequestDTO
        {
            #[Required]
            #[IsString]
            public string $name;

            #[Required]
            #[IsInt]
            public int $age;
        };
    }

    public function test_validation_exception_thrown_for_wrong_type(): void
    {
        $this->expectException(ValidationException::class);

        new class(['name' => 'Bob', 'age' => 'not-a-number']) extends RequestDTO
        {
            #[Required]
            #[IsString]
            public string $name;

            #[Required]
            #[IsInt]
            public int $age;
        };
    }

    public function test_nullable_field_accepts_null(): void
    {
        $dto = new class(['search' => null]) extends RequestDTO
        {
            #[Nullable]
            #[IsString]
            public ?string $search;
        };

        $this->assertNull($dto->search);
    }

    public function test_rules_method_takes_precedence_over_attributes(): void
    {
        $this->expectException(ValidationException::class);

        // The rules() method enforces max:3 even though attribute only has IsString
        new class(['name' => 'Too Long Name']) extends RequestDTO
        {
            #[IsString]
            public string $name;

            protected function rules(): array
            {
                return ['name' => ['required', 'string', 'max:3']];
            }
        };
    }

    // -------------------------------------------------------------------------
    // Attribute-driven validation rules
    // -------------------------------------------------------------------------

    public function test_min_max_attributes_are_applied(): void
    {
        $this->expectException(ValidationException::class);

        new class(['count' => 200]) extends RequestDTO
        {
            #[Required]
            #[IsInt]
            #[Min(1)]
            #[Max(100)]
            public int $count;
        };
    }

    // -------------------------------------------------------------------------
    // Defaults
    // -------------------------------------------------------------------------

    public function test_default_value_attribute_is_applied_for_missing_key(): void
    {
        $dto = new class([]) extends RequestDTO
        {
            #[DefaultValue(10)]
            public int $page;
        };

        $this->assertSame(10, $dto->page);
    }

    public function test_defaults_method_is_applied_for_missing_key(): void
    {
        $dto = new class([]) extends RequestDTO
        {
            public string $locale;

            protected function defaults(): array
            {
                return ['locale' => 'en'];
            }
        };

        $this->assertSame('en', $dto->locale);
    }

    public function test_defaults_method_takes_precedence_over_default_value_attribute(): void
    {
        $dto = new class([]) extends RequestDTO
        {
            #[DefaultValue('from-attribute')]
            public string $source;

            protected function defaults(): array
            {
                return ['source' => 'from-method'];
            }
        };

        $this->assertSame('from-method', $dto->source);
    }

    // -------------------------------------------------------------------------
    // Casting
    // -------------------------------------------------------------------------

    public function test_casts_attribute_transforms_value(): void
    {
        $dto = new class(['color' => 'red']) extends RequestDTO
        {
            #[Required]
            #[Cast(EnumCast::class, RequestTestColor::class)]
            public RequestTestColor $color;
        };

        $this->assertSame(RequestTestColor::Red, $dto->color);
    }

    // -------------------------------------------------------------------------
    // toArray
    // -------------------------------------------------------------------------

    public function test_to_array_excludes_uninitialised_properties(): void
    {
        $dto = new class(['name' => 'Test']) extends RequestDTO
        {
            #[Required]
            #[IsString]
            public string $name;

            public string $optional;
        };

        $array = $dto->toArray();

        $this->assertArrayHasKey('name', $array);
        $this->assertArrayNotHasKey('optional', $array);
    }

    public function test_to_array_includes_null_values(): void
    {
        $dto = new class(['tag' => null]) extends RequestDTO
        {
            #[Nullable]
            public ?string $tag;
        };

        $array = $dto->toArray();

        $this->assertArrayHasKey('tag', $array);
        $this->assertNull($array['tag']);
    }

    // -------------------------------------------------------------------------
    // getSchemaName
    // -------------------------------------------------------------------------

    public function test_get_schema_name_returns_non_empty_string(): void
    {
        $dto = new class([]) extends RequestDTO {};

        $this->assertNotEmpty($dto::getSchemaName());
    }
}
