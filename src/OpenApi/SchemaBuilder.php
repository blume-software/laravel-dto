<?php

namespace BlumeSoftware\LaravelDTO\OpenApi;

use BackedEnum;
use BlumeSoftware\LaravelDTO\Attributes\Validation\Email;
use BlumeSoftware\LaravelDTO\Attributes\Validation\In;
use BlumeSoftware\LaravelDTO\Attributes\Validation\Max;
use BlumeSoftware\LaravelDTO\Attributes\Validation\Min;
use BlumeSoftware\LaravelDTO\Attributes\Validation\Required;
use BlumeSoftware\LaravelDTO\Attributes\Validation\URule;
use BlumeSoftware\LaravelDTO\Attributes\Validation\Uuid;
use BlumeSoftware\LaravelDTO\BaseDTO;
use BlumeSoftware\LaravelDTO\Contracts\InfersOpenApiSchema;
use BlumeSoftware\LaravelDTO\OpenApi\Attributes\Example;
use BlumeSoftware\LaravelDTO\RequestDTO;
use Carbon\Carbon;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionEnum;
use ReflectionEnumBackedCase;
use ReflectionEnumUnitCase;
use ReflectionException;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;
use RuntimeException;
use stdClass;

/**
 * Builds JSON Schema objects from PHP classes and enums via reflection.
 */
class SchemaBuilder
{
    private array $schemas = [];

    public function getSchemas(): array
    {
        return $this->schemas;
    }

    public function has(string $name): bool
    {
        return isset($this->schemas[$name]);
    }

    public function register(string $name, array $schema): void
    {
        $this->schemas[$name] = $schema;
    }

    public function getSchemaNameForType(string $className): string
    {
        if (is_subclass_of($className, InfersOpenApiSchema::class)) {
            return $className::getSchemaName();
        }

        $parts = explode('\\', $className);

        return end($parts);
    }

    /**
     * @throws ReflectionException
     */
    public function buildSchemaFromClass(string $className): array
    {
        return $this->buildSchemaFromReflectionClass(new ReflectionClass($className));
    }

    public function buildSchemaFromReflectionClass(ReflectionClass $reflectionClass): array
    {
        $required = [];
        $properties = [];
        $isRequest = $reflectionClass->isSubclassOf(RequestDTO::class);

        foreach ($reflectionClass->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->getDeclaringClass()->getName() === BaseDTO::class) {
                continue;
            }

            $propertyName = $property->getName();
            $type = $property->getType();

            if (! $type) {
                continue;
            }

            $schema = $this->getSchemaFromType($type, $property);
            $schema = $this->applyValidationConstraints($schema, $property);
            $schema = $this->applyExample($schema, $property, $propertyName);

            if ($this->isPropertyRequired($property, $type, $isRequest)) {
                $required[] = $propertyName;
            }

            $properties[$propertyName] = $schema;
        }

        $object = [
            'type' => 'object',
            'properties' => $properties,
            'required' => array_values(array_unique($required)),
        ];

        if ($reflectionClass->hasConstant('WRAPS_IN_DATA') && $reflectionClass->getName()::WRAPS_IN_DATA) {
            return [
                'type' => 'object',
                'properties' => [
                    'data' => $object,
                ],
                'required' => ['data'],
            ];
        }

        return $object;
    }

    /**
     * @throws ReflectionException
     */
    public function getSchemaFromType(mixed $type, ReflectionProperty $property): array
    {
        if ($type instanceof ReflectionUnionType) {
            return $this->getSchemaFromUnionType($type, $property);
        }

        if ($type instanceof ReflectionNamedType) {
            return $this->getSchemaFromNamedType($type, $property);
        }

        return [];
    }

    /**
     * @throws ReflectionException
     */
    public function getSchemaFromNamedType(ReflectionNamedType $type, ReflectionProperty $property): array
    {
        $schema = $this->resolveNamedTypeSchema($type->getName(), $property);

        if ($type->allowsNull() && $type->getName() !== 'null' && $type->getName() !== 'mixed') {
            return $this->withNullType($schema);
        }

        return $schema;
    }

    /**
     * @throws ReflectionException
     */
    public function getSchemaFromUnionType(ReflectionUnionType $unionType, ReflectionProperty $property): array
    {
        $hasNull = false;
        $schemas = [];

        foreach ($unionType->getTypes() as $type) {
            if ($type->getName() === 'null') {
                $hasNull = true;

                continue;
            }

            $schemas[] = $this->getSchemaFromNamedType($type, $property);
        }

        if (count($schemas) === 1) {
            return $hasNull ? $this->withNullType($schemas[0]) : $schemas[0];
        }

        if ($hasNull) {
            $schemas[] = ['type' => 'null'];
        }

        return ['oneOf' => $schemas];
    }

    /**
     * @throws ReflectionException
     */
    private function resolveNamedTypeSchema(string $typeName, ReflectionProperty $property): array
    {
        if ($typeName === 'mixed') {
            return [];
        }

        if ($typeName === 'int') {
            return ['type' => 'integer'];
        }

        if ($typeName === 'float') {
            return ['type' => 'number', 'format' => 'float'];
        }

        if ($typeName === 'bool') {
            return ['type' => 'boolean'];
        }

        if ($typeName === 'array') {
            return $this->getSchemaFromArrayType($property);
        }

        if (! class_exists($typeName)) {
            return ['type' => 'string'];
        }

        if ($typeName === stdClass::class) {
            return ['type' => 'object'];
        }

        if ($typeName === Carbon::class || is_subclass_of($typeName, Carbon::class)) {
            return ['type' => 'string', 'format' => 'date-time'];
        }

        if (is_subclass_of($typeName, BackedEnum::class)) {
            $schemaName = Str::remove('\\', $typeName);

            if (! $this->has($schemaName)) {
                $this->register($schemaName, $this->buildSchemaFromEnum($typeName));
            }

            return ['$ref' => '#/components/schemas/'.$schemaName];
        }

        if (is_subclass_of($typeName, InfersOpenApiSchema::class)) {
            $schemaName = $typeName::getSchemaName();

            if (! $this->has($schemaName)) {
                $this->register($schemaName, $this->buildSchemaFromClass($typeName));
            }

            return ['$ref' => '#/components/schemas/'.$schemaName];
        }

        if (is_subclass_of($typeName, BaseDTO::class)) {
            return $this->buildSchemaFromClass($typeName);
        }

        return ['type' => 'string'];
    }

    /**
     * Adds null to a schema's type for OpenAPI 3.1 nullable representation.
     */
    private function withNullType(array $schema): array
    {
        if (isset($schema['type']) && is_string($schema['type'])) {
            return array_merge($schema, ['type' => [$schema['type'], 'null']]);
        }

        if (isset($schema['$ref'])) {
            return ['oneOf' => [$schema, ['type' => 'null']]];
        }

        return $schema;
    }

    public function getSchemaFromArrayType(ReflectionProperty $property): array
    {
        $docComment = $property->getDocComment();

        if (! $docComment) {
            return ['type' => 'array', 'items' => []];
        }

        if (preg_match('/@var\s+([a-zA-Z0-9_\\\\]+)\[\]/', $docComment, $matches)) {
            $resolvedType = $this->resolveClassNameFromProperty($matches[1], $property);

            return ['type' => 'array', 'items' => $this->getSchemaFromTypeName($resolvedType)];
        }

        if (preg_match('/@var\s+(?:array|list)<([a-zA-Z0-9_\\\\]+)>/', $docComment, $matches)) {
            $resolvedType = $this->resolveClassNameFromProperty($matches[1], $property);

            return ['type' => 'array', 'items' => $this->getSchemaFromTypeName($resolvedType)];
        }

        return ['type' => 'array', 'items' => []];
    }

    public function getSchemaFromTypeName(string $typeName): array
    {
        $builtInTypes = [
            'int' => ['type' => 'integer'],
            'integer' => ['type' => 'integer'],
            'float' => ['type' => 'number', 'format' => 'float'],
            'bool' => ['type' => 'boolean'],
            'boolean' => ['type' => 'boolean'],
            'string' => ['type' => 'string'],
        ];

        if (isset($builtInTypes[$typeName])) {
            return $builtInTypes[$typeName];
        }

        if (! class_exists($typeName)) {
            return ['type' => 'string'];
        }

        if (is_subclass_of($typeName, InfersOpenApiSchema::class)) {
            $schemaName = $typeName::getSchemaName();

            if (! $this->has($schemaName)) {
                $this->register($schemaName, $this->buildSchemaFromClass($typeName));
            }

            return ['$ref' => '#/components/schemas/'.$schemaName];
        }

        if (is_subclass_of($typeName, BaseDTO::class)) {
            return $this->buildSchemaFromClass($typeName);
        }

        return ['type' => 'string'];
    }

    public function resolveClassNameFromProperty(string $className, ReflectionProperty $property): string
    {
        if ($this->isPhpPrimitiveType($className) || str_contains($className, '\\')) {
            return $className;
        }

        $declaringClass = $property->getDeclaringClass();
        $filename = $declaringClass->getFileName();

        if (! $filename || ! file_exists($filename)) {
            return $declaringClass->getNamespaceName().'\\'.$className;
        }

        return $this->resolveFromFileImports($className, $declaringClass->getNamespaceName(), file_get_contents($filename));
    }

    public function resolveClassName(string $className, ReflectionClass $contextClass): string
    {
        if (str_starts_with($className, '\\')) {
            $fullClassName = ltrim($className, '\\');

            if (class_exists($fullClassName)) {
                return $fullClassName;
            }

            throw new RuntimeException(
                sprintf('Class %s not found (referenced in %s)', $fullClassName, $contextClass->getName())
            );
        }

        $namespace = $contextClass->getNamespaceName();
        $inNamespace = $namespace.'\\'.$className;

        if (class_exists($inNamespace)) {
            return $inNamespace;
        }

        $file = file_get_contents($contextClass->getFileName());
        $resolved = $this->resolveFromFileImports($className, $namespace, $file);

        if (class_exists($resolved)) {
            return $resolved;
        }

        if (class_exists($className)) {
            return $className;
        }

        throw new RuntimeException(
            sprintf(
                'Cannot resolve class name "%s" in context of %s. '.
                'Make sure the class exists and is imported with a use statement.',
                $className,
                $contextClass->getName()
            )
        );
    }

    /**
     * @throws ReflectionException
     */
    public function buildSchemaFromEnum(string $enumClassName): array
    {
        $reflectionEnum = new ReflectionEnum($enumClassName);
        $backingType = $reflectionEnum->getBackingType();

        if (! $backingType) {
            throw new RuntimeException('Enums must be backed by a primitive type.');
        }

        return [
            'type' => $backingType->getName() === 'int' ? 'integer' : 'string',
            'enum' => array_map(
                static fn (ReflectionEnumUnitCase|ReflectionEnumBackedCase $c): int|string => $c->getBackingValue(),
                $reflectionEnum->getCases()
            ),
        ];
    }

    public function applyValidationConstraints(array $schema, ReflectionProperty $property): array
    {
        foreach ($property->getAttributes() as $attribute) {
            $instance = $attribute->newInstance();

            $schema = match (true) {
                $instance instanceof Min => $this->applyMinConstraint($schema, $instance->value),
                $instance instanceof Max => $this->applyMaxConstraint($schema, $instance->value),
                $instance instanceof Email => $this->applyFormat($schema, 'email'),
                $instance instanceof Uuid => $this->applyFormat($schema, 'uuid'),
                $instance instanceof In => array_merge($schema, ['enum' => $instance->values]),
                $instance instanceof URule => $this->applyURule($schema, $instance->rule),
                default => $schema,
            };
        }

        return $schema;
    }

    private function applyMinConstraint(array $schema, int|float $value): array
    {
        return $this->isStringSchema($schema)
            ? array_merge($schema, ['minLength' => $value])
            : array_merge($schema, ['minimum' => $value]);
    }

    private function applyMaxConstraint(array $schema, int|float $value): array
    {
        return $this->isStringSchema($schema)
            ? array_merge($schema, ['maxLength' => $value])
            : array_merge($schema, ['maximum' => $value]);
    }

    private function applyFormat(array $schema, string $format): array
    {
        if (isset($schema['$ref'])) {
            return $schema;
        }

        return array_merge($schema, ['format' => $format]);
    }

    private function applyURule(array $schema, string $rule): array
    {
        if ($rule === 'date' || str_starts_with($rule, 'date_format')) {
            return $this->applyFormat($schema, 'date-time');
        }

        return $schema;
    }

    public function applyExample(array $schema, ReflectionProperty $property, string $propertyName): array
    {
        foreach ($property->getAttributes(Example::class) as $attribute) {
            return array_merge($schema, ['example' => $attribute->newInstance()->value]);
        }

        $example = $this->deriveExample($propertyName, $schema);

        if ($example !== null) {
            return array_merge($schema, ['example' => $example]);
        }

        return $schema;
    }

    private function deriveExample(string $propertyName, array $schema): mixed
    {
        $byFormat = match ($schema['format'] ?? null) {
            'email' => 'user@example.com',
            'uuid' => '00000000-0000-4000-8000-000000000000',
            'date-time' => '2026-01-01T12:00:00+00:00',
            default => null,
        };

        if ($byFormat !== null) {
            return $byFormat;
        }

        if ($propertyName === 'id' || str_ends_with($propertyName, '_id')) {
            return 1;
        }

        return match ($propertyName) {
            'name' => 'Acme',
            'slug' => 'acme',
            'description' => 'Short description.',
            'email', 'contact_email' => 'user@example.com',
            'path' => '/uploads/',
            'extension' => 'pdf',
            'content_type', 'mime_type' => 'application/pdf',
            'size' => 1024,
            default => null,
        };
    }

    private function isStringSchema(array $schema): bool
    {
        $type = $schema['type'] ?? null;

        if (is_string($type)) {
            return $type === 'string';
        }

        if (is_array($type)) {
            return in_array('string', $type, strict: true);
        }

        return false;
    }

    public function isPropertyRequired(ReflectionProperty $property, mixed $type, bool $isRequest): bool
    {
        if ($isRequest) {
            return $property->getAttributes(Required::class) !== [];
        }

        return $type instanceof ReflectionNamedType && ! $type->allowsNull();
    }

    private function isPhpPrimitiveType(string $name): bool
    {
        return in_array($name, ['int', 'integer', 'float', 'double', 'bool', 'boolean', 'string', 'null', 'array', 'object', 'mixed', 'void'], strict: true);
    }

    private function resolveFromFileImports(string $className, string $namespace, string $fileContents): string
    {
        preg_match_all('/^use\s+([^;]+);/m', $fileContents, $matches);

        foreach ($matches[1] ?? [] as $import) {
            $import = trim($import);

            if (preg_match('/^(.+)\s+as\s+(\w+)$/', $import, $aliasMatch)) {
                if (trim($aliasMatch[2]) === $className) {
                    return trim($aliasMatch[1]);
                }
            } else {
                $parts = explode('\\', $import);

                if (end($parts) === $className) {
                    return $import;
                }
            }
        }

        return $namespace.'\\'.$className;
    }
}
