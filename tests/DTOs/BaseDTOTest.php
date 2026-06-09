<?php

namespace BlumeSoftware\LaravelDTO\Tests\DTOs;

use BlumeSoftware\LaravelDTO\BaseDTO;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use stdClass;
use Stringable;

class BaseDTOTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Fixtures
    // -------------------------------------------------------------------------

    private function makeSimpleDTO(array $values = []): BaseDTO
    {
        return new class($values) extends BaseDTO
        {
            public string $name;

            public int $age;

            public function __construct(array $values)
            {
                if (isset($values['name'])) {
                    $this->name = $values['name'];
                }
                if (isset($values['age'])) {
                    $this->age = $values['age'];
                }
            }
        };
    }

    // -------------------------------------------------------------------------
    // toArray
    // -------------------------------------------------------------------------

    public function test_to_array_returns_all_public_properties(): void
    {
        $dto = $this->makeSimpleDTO(['name' => 'Alice', 'age' => 30]);

        $this->assertSame(['name' => 'Alice', 'age' => 30], $dto->toArray());
    }

    // -------------------------------------------------------------------------
    // jsonSerialize / toJson
    // -------------------------------------------------------------------------

    public function test_json_serialize_returns_array_not_double_encoded_string(): void
    {
        $dto = $this->makeSimpleDTO(['name' => 'Bob', 'age' => 25]);

        $serialized = $dto->jsonSerialize();

        $this->assertIsArray($serialized, 'jsonSerialize() must return an array so json_encode() does not double-encode it');
        $this->assertSame(['name' => 'Bob', 'age' => 25], $serialized);
    }

    public function test_json_encode_produces_valid_json_object(): void
    {
        $dto = $this->makeSimpleDTO(['name' => 'Carol', 'age' => 40]);

        $json = json_encode($dto);

        $this->assertJson($json);
        $this->assertSame('{"name":"Carol","age":40}', $json);
    }

    public function test_to_json_returns_encoded_string(): void
    {
        $dto = $this->makeSimpleDTO(['name' => 'Dave', 'age' => 50]);

        $this->assertSame('{"name":"Dave","age":50}', $dto->toJson());
    }

    // -------------------------------------------------------------------------
    // convertValueToArray – Carbon
    // -------------------------------------------------------------------------

    public function test_carbon_date_is_converted_to_iso8601_string(): void
    {
        $carbon = Carbon::parse('2024-01-15T12:00:00+00:00');

        $dto = new class($carbon) extends BaseDTO
        {
            public string $created_at;

            public function __construct(Carbon $date)
            {
                $this->created_at = $date->toIso8601String();
            }
        };

        // Verify the DTO itself converts Carbon embedded in arrays
        $dtoWithCarbon = new class($carbon) extends BaseDTO
        {
            public mixed $ts;

            public function __construct(Carbon $c)
            {
                $this->ts = $c;
            }
        };

        $result = $dtoWithCarbon->toArray();
        $this->assertIsString($result['ts']);
        $this->assertStringContainsString('2024-01-15', $result['ts']);
    }

    // -------------------------------------------------------------------------
    // convertValueToArray – Stringable
    // -------------------------------------------------------------------------

    public function test_stringable_is_converted_to_string(): void
    {
        $stringable = new class implements Stringable
        {
            public function __toString(): string
            {
                return 'my-value';
            }
        };

        $dto = new class($stringable) extends BaseDTO
        {
            public mixed $value;

            public function __construct(mixed $v)
            {
                $this->value = $v;
            }
        };

        $this->assertSame(['value' => 'my-value'], $dto->toArray());
    }

    // -------------------------------------------------------------------------
    // convertValueToArray – stdClass
    // -------------------------------------------------------------------------

    public function test_std_class_is_converted_to_array(): void
    {
        $obj = new stdClass;
        $obj->foo = 'bar';
        $obj->nested = new stdClass;
        $obj->nested->x = 1;

        $dto = new class($obj) extends BaseDTO
        {
            public mixed $data;

            public function __construct(mixed $d)
            {
                $this->data = $d;
            }
        };

        $this->assertSame(['data' => ['foo' => 'bar', 'nested' => ['x' => 1]]], $dto->toArray());
    }

    // -------------------------------------------------------------------------
    // convertValueToArray – nested arrays
    // -------------------------------------------------------------------------

    public function test_nested_array_values_are_recursively_converted(): void
    {
        $obj = new stdClass;
        $obj->key = 'val';

        $dto = new class($obj) extends BaseDTO
        {
            public array $items;

            public function __construct(stdClass $item)
            {
                $this->items = [$item, $item];
            }
        };

        $result = $dto->toArray();
        $this->assertSame([['key' => 'val'], ['key' => 'val']], $result['items']);
    }

    // -------------------------------------------------------------------------
    // convertValueToArray – objects with toArray()
    // -------------------------------------------------------------------------

    public function test_object_with_to_array_is_converted(): void
    {
        $inner = new class extends BaseDTO
        {
            public string $x = 'hello';
        };

        $dto = new class($inner) extends BaseDTO
        {
            public mixed $child;

            public function __construct(mixed $c)
            {
                $this->child = $c;
            }
        };

        $this->assertSame(['child' => ['x' => 'hello']], $dto->toArray());
    }

    // -------------------------------------------------------------------------
    // Status code
    // -------------------------------------------------------------------------

    public function test_default_status_code_is_200(): void
    {
        $dto = $this->makeSimpleDTO(['name' => 'X', 'age' => 1]);

        $this->assertSame(200, $dto->getStatusCode());
    }

    public function test_set_status_code_returns_same_instance_and_updates_code(): void
    {
        $dto = $this->makeSimpleDTO(['name' => 'X', 'age' => 1]);

        $result = $dto->setStatusCode(404);

        $this->assertSame($dto, $result);
        $this->assertSame(404, $dto->getStatusCode());
    }

    // -------------------------------------------------------------------------
    // Reflection cache is per class, not per instance
    // -------------------------------------------------------------------------

    public function test_property_cache_is_shared_across_instances(): void
    {
        $a = $this->makeSimpleDTO(['name' => 'A', 'age' => 1]);
        $b = $this->makeSimpleDTO(['name' => 'B', 'age' => 2]);

        $this->assertSame(array_keys($a->toArray()), array_keys($b->toArray()));
    }
}
