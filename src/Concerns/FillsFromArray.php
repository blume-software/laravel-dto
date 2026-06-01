<?php

namespace BlumeSoftware\LaravelDTO\Concerns;

use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionType;
use ReflectionUnionType;
use BlumeSoftware\LaravelDTO\Attributes\ArrayOf;
use BlumeSoftware\LaravelDTO\BaseDTO;

trait FillsFromArray
{
    /**
     * @param  array<string, mixed>  $data
     */
    protected function fillFromArray(array $data): void
    {
        foreach ($this->getProperties() as $name => $attributes) {
            if (! array_key_exists($name, $data)) {
                continue;
            }

            $property = new ReflectionProperty($this, $name);
            $arrayOfClass = null;
            foreach ($attributes as $attribute) {
                if ($attribute instanceof ArrayOf) {
                    $arrayOfClass = $attribute->class;
                    break;
                }
            }

            $this->{$name} = $this->coerceValueForProperty(
                $data[$name],
                $property->getType(),
                $arrayOfClass,
            );
        }
    }

    /**
     * @param  class-string|null  $arrayOfClass
     */
    private function coerceValueForProperty(mixed $value, ?ReflectionType $type, ?string $arrayOfClass): mixed
    {
        if ($type === null) {
            return $value;
        }

        if ($type instanceof ReflectionUnionType) {
            foreach ($type->getTypes() as $inner) {
                if (! $inner instanceof ReflectionNamedType) {
                    continue;
                }
                if ($inner->getName() === 'null' && $value === null) {
                    return null;
                }
            }

            foreach ($type->getTypes() as $inner) {
                if (! $inner instanceof ReflectionNamedType || $inner->getName() === 'null') {
                    continue;
                }
                try {
                    return $this->coerceNamedType($value, $inner, $arrayOfClass);
                } catch (\Throwable) {
                    continue;
                }
            }

            return $value;
        }

        if ($type instanceof ReflectionIntersectionType) {
            return $value;
        }

        if ($type instanceof ReflectionNamedType) {
            return $this->coerceNamedType($value, $type, $arrayOfClass);
        }

        return $value;
    }

    /**
     * @param  class-string|null  $arrayOfClass
     */
    private function coerceNamedType(mixed $value, ReflectionNamedType $type, ?string $arrayOfClass): mixed
    {
        if ($type->allowsNull() && $value === null) {
            return null;
        }

        $name = $type->getName();

        if ($name === 'array') {
            if (! is_array($value)) {
                return [];
            }

            if ($arrayOfClass !== null && is_subclass_of($arrayOfClass, BaseDTO::class)) {
                if ($value !== [] && ! array_is_list($value)) {
                    return [];
                }

                return array_map(function (mixed $item) use ($arrayOfClass): BaseDTO {
                    $row = is_array($item) ? $item : (array) $item;

                    return new $arrayOfClass($row);
                }, $value);
            }

            return $value;
        }

        return match ($name) {
            'string' => $value === null && $type->allowsNull()
                ? null
                : (string) $value,
            'int' => $value === null && $type->allowsNull()
                ? null
                : (int) $value,
            'float' => $value === null && $type->allowsNull()
                ? null
                : (float) $value,
            'bool' => $value === null && $type->allowsNull()
                ? null
                : filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool) $value,
            default => class_exists($name) && is_subclass_of($name, BaseDTO::class) && is_array($value)
                ? new $name($value)
                : $value,
        };
    }
}
