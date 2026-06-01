<?php

namespace Tests\Unit\Blume\LaravelDTO\Casts;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use BlumeSoftware\LaravelDTO\Casts\ArrayCast;
use BlumeSoftware\LaravelDTO\Casts\DTOCast;
use BlumeSoftware\LaravelDTO\Interfaces\Castable;
use BlumeSoftware\LaravelDTO\ModelDTO;

class ArrayCastTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Null passthrough
    // -------------------------------------------------------------------------

    public function test_returns_null_for_null_value(): void
    {
        $cast = new ArrayCast;

        $this->assertNull($cast->cast('prop', null));
    }

    // -------------------------------------------------------------------------
    // Plain arrays
    // -------------------------------------------------------------------------

    public function test_passes_plain_array_through(): void
    {
        $cast = new ArrayCast;

        $result = $cast->cast('items', [1, 2, 3]);

        $this->assertSame([1, 2, 3], $result);
    }

    // -------------------------------------------------------------------------
    // Collection
    // -------------------------------------------------------------------------

    public function test_converts_collection_to_array(): void
    {
        $cast = new ArrayCast;
        $collection = new Collection([4, 5, 6]);

        $result = $cast->cast('items', $collection);

        $this->assertSame([4, 5, 6], $result);
    }

    // -------------------------------------------------------------------------
    // Object with toArray()
    // -------------------------------------------------------------------------

    public function test_calls_to_array_on_objects(): void
    {
        $cast = new ArrayCast;

        $obj = new class
        {
            public function toArray(): array
            {
                return ['a' => 1];
            }
        };

        $result = $cast->cast('prop', $obj);

        $this->assertSame(['a' => 1], $result);
    }

    // -------------------------------------------------------------------------
    // Non-array throws
    // -------------------------------------------------------------------------

    public function test_throws_for_non_array_non_collection_value(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new ArrayCast)->cast('prop', 'not-an-array');
    }

    // -------------------------------------------------------------------------
    // Nested castable
    // -------------------------------------------------------------------------

    public function test_applies_inner_castable_to_each_element(): void
    {
        $innerCast = new class implements Castable
        {
            public function cast(string $property, mixed $value): mixed
            {
                return $value * 2;
            }
        };

        $cast = new ArrayCast($innerCast);

        $result = $cast->cast('nums', [1, 2, 3]);

        $this->assertSame([2, 4, 6], $result);
    }

    // -------------------------------------------------------------------------
    // Nested DTOCast
    // -------------------------------------------------------------------------

    public function test_applies_dto_cast_to_each_element(): void
    {
        $itemDTO = new class(['val' => 0]) extends ModelDTO
        {
            public int $val;
        };

        $dtoCastClass = get_class($itemDTO);

        $innerDtoCast = new DTOCast($dtoCastClass);
        $cast = new ArrayCast($innerDtoCast);

        $result = $cast->cast('items', [['val' => 10], ['val' => 20]]);

        $this->assertCount(2, $result);
        $this->assertSame(10, $result[0]->val);
        $this->assertSame(20, $result[1]->val);
    }
}
