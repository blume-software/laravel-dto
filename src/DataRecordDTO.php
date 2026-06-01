<?php

namespace Blume\LaravelDTO;

use Blume\LaravelDTO\Concerns\FillsFromArray;

/**
 * DTO filled from associative array payloads (e.g. JSON objects). Declares public typed properties;
 * {@see FillsFromArray} maps keys to properties with coercion. Use {@see ArrayOf} for list-of-DTO fields.
 */
abstract class DataRecordDTO extends BaseDTO
{
    use FillsFromArray;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(array $data = [])
    {
        if ($data !== []) {
            $this->fillFromArray($data);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): static
    {
        return new static($data);
    }
}
