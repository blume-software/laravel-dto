<?php

namespace Tests\Unit\Blume\LaravelDTO\Casts;

use PHPUnit\Framework\TestCase;
use Blume\LaravelDTO\Casts\DTOCast;
use Blume\LaravelDTO\ModelDTO;

class DTOCastTest extends TestCase
{
    private function makeItemDTOClass(): string
    {
        return (new class(['val' => 0]) extends ModelDTO
        {
            public int $val;
        })::class;
    }

    // -------------------------------------------------------------------------
    // Happy path
    // -------------------------------------------------------------------------

    public function test_casts_array_to_dto_instance(): void
    {
        $dtoClass = $this->makeItemDTOClass();
        $cast = new DTOCast($dtoClass);

        $result = $cast->cast('item', ['val' => 42]);

        $this->assertInstanceOf($dtoClass, $result);
        $this->assertSame(42, $result->val);
    }

    // -------------------------------------------------------------------------
    // Null / falsy values
    // -------------------------------------------------------------------------

    public function test_returns_null_for_null_value(): void
    {
        $cast = new DTOCast($this->makeItemDTOClass());

        $this->assertNull($cast->cast('item', null));
    }

    public function test_returns_null_for_empty_array(): void
    {
        $cast = new DTOCast($this->makeItemDTOClass());

        $this->assertNull($cast->cast('item', []));
    }
}
