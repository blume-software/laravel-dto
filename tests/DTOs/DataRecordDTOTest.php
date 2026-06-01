<?php

namespace Blume\LaravelDTO\Tests\DTOs;

use PHPUnit\Framework\TestCase;
use Blume\LaravelDTO\Attributes\ArrayOf;
use Blume\LaravelDTO\DataRecordDTO;

class DataRecordDTOTest extends TestCase
{
    public function test_fills_scalar_and_nullable_fields_from_array(): void
    {
        $dto = new class(['id' => 'x-1', 'name' => 'Acme', 'enabled' => true]) extends DataRecordDTO
        {
            public ?string $id = null;

            public ?string $name = null;

            public ?bool $enabled = null;
        };

        $this->assertSame('x-1', $dto->id);
        $this->assertSame('Acme', $dto->name);
        $this->assertTrue($dto->enabled);
    }

    public function test_from_array_factory(): void
    {
        $class = new class extends DataRecordDTO
        {
            public string $access_token = '';

            public int $expires_in = 0;
        };

        $instance = $class::fromArray([
            'access_token' => 'tok',
            'expires_in' => 120,
        ]);

        $this->assertSame('tok', $instance->access_token);
        $this->assertSame(120, $instance->expires_in);
    }

    public function test_array_of_maps_nested_rows_to_dtos(): void
    {
        $list = new DataRecordListFixture([
            'items' => [
                ['id' => 'a'],
                ['id' => 'b'],
            ],
        ]);

        $this->assertCount(2, $list->items);
        $this->assertInstanceOf(DataRecordItemFixture::class, $list->items[0]);
        $this->assertSame('a', $list->items[0]->id);
        $this->assertSame('b', $list->items[1]->id);
    }

    public function test_to_array_serializes_nested_dtos(): void
    {
        $list = new DataRecordListFixture([
            'items' => [['id' => 'z']],
        ]);

        $this->assertSame([
            'items' => [
                ['id' => 'z'],
            ],
        ], $list->toArray());
    }
}

final class DataRecordItemFixture extends DataRecordDTO
{
    public ?string $id = null;
}

final class DataRecordListFixture extends DataRecordDTO
{
    /** @var DataRecordItemFixture[] */
    #[ArrayOf(DataRecordItemFixture::class)]
    public array $items = [];
}
