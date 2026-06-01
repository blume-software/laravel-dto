<?php

namespace BlumeSoftware\LaravelDTO\OpenApi\Traits;

use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;
use BlumeSoftware\LaravelDTO\OpenApi\SchemaBuilder;

/**
 * @deprecated Inject SchemaBuilder directly. This trait is kept for backward compatibility.
 */
trait ReflectsProperties
{
    private ?SchemaBuilder $_schemaBuilderInstance = null;

    private function schemaBuilder(): SchemaBuilder
    {
        if ($this->_schemaBuilderInstance === null) {
            $this->_schemaBuilderInstance = new SchemaBuilder;

            if (property_exists($this, 'schemas')) {
                foreach ($this->schemas as $name => $schema) {
                    $this->_schemaBuilderInstance->register($name, $schema);
                }
            }
        }

        return $this->_schemaBuilderInstance;
    }

    protected function buildSchemaFromClass(string $className): array
    {
        return $this->schemaBuilder()->buildSchemaFromClass($className);
    }

    protected function buildSchemaFromReflectionClass(ReflectionClass $reflectionClass): array
    {
        return $this->schemaBuilder()->buildSchemaFromReflectionClass($reflectionClass);
    }

    protected function getSchemaFromType(mixed $type, ReflectionProperty $property): array
    {
        return $this->schemaBuilder()->getSchemaFromType($type, $property);
    }

    protected function getSchemaFromNamedType(ReflectionNamedType $type, ReflectionProperty $property): array
    {
        return $this->schemaBuilder()->getSchemaFromNamedType($type, $property);
    }

    protected function getSchemaFromUnionType(ReflectionUnionType $unionType, ReflectionProperty $property): array
    {
        return $this->schemaBuilder()->getSchemaFromUnionType($unionType, $property);
    }

    protected function getSchemaFromArrayType(ReflectionProperty $property): array
    {
        return $this->schemaBuilder()->getSchemaFromArrayType($property);
    }

    protected function getSchemaFromTypeName(string $typeName): array
    {
        return $this->schemaBuilder()->getSchemaFromTypeName($typeName);
    }

    protected function resolveClassNameFromProperty(string $className, ReflectionProperty $property): string
    {
        return $this->schemaBuilder()->resolveClassNameFromProperty($className, $property);
    }

    protected function buildSchemaFromEnum(string $enumClassName): array
    {
        return $this->schemaBuilder()->buildSchemaFromEnum($enumClassName);
    }

    protected function getSchemaNameForType(string $className): string
    {
        return $this->schemaBuilder()->getSchemaNameForType($className);
    }
}
