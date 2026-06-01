<?php

namespace BlumeSoftware\LaravelDTO;

use Carbon\Carbon;
use JsonException;
use JsonSerializable;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use stdClass;
use Stringable;

abstract class BaseDTO implements JsonSerializable
{
    /** @var array<class-string, array<string, object[]>> Per-class reflection cache shared across instances */
    private static array $propertyCache = [];

    protected int $statusCode = 200;

    protected array $setKeys = [];

    /**
     * Returns the public property names (excluding BaseDTO's own) mapped to their attribute instances,
     * ordered from parent class to child class. Result is cached per concrete class.
     *
     * @throws ReflectionException
     */
    protected function getProperties(): array
    {
        $class = static::class;

        if (isset(self::$propertyCache[$class])) {
            return self::$propertyCache[$class];
        }

        $reflectionClass = new ReflectionClass($this);
        $dtoProperties = [];

        // Walk the hierarchy from root downward so parent properties appear first
        $classHierarchy = [];
        $currentClass = $reflectionClass;

        while ($currentClass !== false) {
            if ($currentClass->getName() === self::class) {
                break;
            }

            $classHierarchy[] = $currentClass;
            $currentClass = $currentClass->getParentClass();
        }

        $classHierarchy = array_reverse($classHierarchy);

        foreach ($classHierarchy as $hierarchyClass) {
            foreach ($hierarchyClass->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
                if ($property->getDeclaringClass()->getName() !== $hierarchyClass->getName()) {
                    continue;
                }

                $dtoProperties[$property->getName()] = array_map(
                    fn (ReflectionAttribute $attr) => $attr->newInstance(),
                    $property->getAttributes()
                );
            }
        }

        return self::$propertyCache[$class] = $dtoProperties;
    }

    /**
     * Returns the array representation suitable for JSON encoding.
     * Implements JsonSerializable — json_encode() will call this automatically.
     *
     * @throws ReflectionException
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @throws ReflectionException
     */
    public function toArray(): array
    {
        $data = [];

        foreach ($this->getProperties() as $name => $_attributes) {
            $reflProperty = new ReflectionProperty($this, $name);

            if (! $reflProperty->isInitialized($this)) {
                continue;
            }

            $data[$name] = $this->convertValueToArray($this->{$name});
        }

        return $data;
    }

    protected function convertValueToArray(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(fn ($v) => $this->convertValueToArray($v), $value);
        }

        if (! is_object($value)) {
            return $value;
        }

        return $this->convertObjectToArray($value);
    }

    protected function convertObjectToArray(mixed $value): mixed
    {
        if ($value instanceof Carbon) {
            return $value->toIso8601String();
        }

        if ($value instanceof Stringable) {
            return (string) $value;
        }

        if (method_exists($value, 'toArray')) {
            return $value->toArray();
        }

        if ($value instanceof stdClass) {
            return array_map(
                fn ($v) => $this->convertValueToArray($v),
                (array) $value
            );
        }

        return $value;
    }

    /**
     * @throws JsonException
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }

    public function setStatusCode(int $statusCode): static
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function hasKey(string $key): bool
    {
        return in_array($key, $this->setKeys, true);
    }

    public function isKeyNull(string $key): bool
    {
        return $this->hasKey($key) && $this->{$key} === null;
    }
}
