<?php

namespace Tests\Unit\Blume\LaravelDTO\Casts;

use BlumeSoftware\LaravelDTO\Casts\EnumCast;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ValueError;

// ---------------------------------------------------------------------------
// Test enums (defined at file scope to avoid anonymous-class limitations)
// ---------------------------------------------------------------------------

enum Status: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}

enum Priority: int
{
    case Low = 1;
    case High = 2;
}

enum Direction
{
    case North;
    case South;
}

class EnumCastTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Backed string enum
    // -------------------------------------------------------------------------

    public function test_casts_string_to_backed_string_enum(): void
    {
        $cast = new EnumCast(Status::class);

        $result = $cast->cast('status', 'active');

        $this->assertSame(Status::Active, $result);
    }

    public function test_casts_int_to_backed_int_enum(): void
    {
        $cast = new EnumCast(Priority::class);

        $result = $cast->cast('priority', 1);

        $this->assertSame(Priority::Low, $result);
    }

    public function test_returns_existing_enum_instance_unchanged(): void
    {
        $cast = new EnumCast(Status::class);

        $result = $cast->cast('status', Status::Inactive);

        $this->assertSame(Status::Inactive, $result);
    }

    public function test_throws_value_error_for_invalid_backed_value(): void
    {
        $this->expectException(ValueError::class);

        (new EnumCast(Status::class))->cast('status', 'unknown');
    }

    public function test_throws_value_error_for_wrong_type_on_backed_enum(): void
    {
        $this->expectException(ValueError::class);

        (new EnumCast(Status::class))->cast('status', ['array-value']);
    }

    // -------------------------------------------------------------------------
    // Pure (unit) enum
    // -------------------------------------------------------------------------

    public function test_casts_string_name_to_unit_enum(): void
    {
        $cast = new EnumCast(Direction::class);

        $result = $cast->cast('direction', 'North');

        $this->assertSame(Direction::North, $result);
    }

    public function test_throws_value_error_for_invalid_unit_enum_name(): void
    {
        $this->expectException(ValueError::class);

        (new EnumCast(Direction::class))->cast('direction', 'East');
    }

    // -------------------------------------------------------------------------
    // Invalid class
    // -------------------------------------------------------------------------

    public function test_throws_invalid_argument_exception_for_non_enum_class(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new EnumCast(\stdClass::class))->cast('prop', 'value');
    }
}
