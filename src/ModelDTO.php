<?php

namespace BlumeSoftware\LaravelDTO;

use BlumeSoftware\LaravelDTO\Attributes\Cast;
use BlumeSoftware\LaravelDTO\Attributes\Getter;
use BlumeSoftware\LaravelDTO\Attributes\Map;
use BlumeSoftware\LaravelDTO\Concerns\HasSchemaName;
use BlumeSoftware\LaravelDTO\Contracts\InfersOpenApiSchema;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class ModelDTO extends BaseDTO implements InfersOpenApiSchema, Responsable
{
    use HasSchemaName;

    protected Model|array $model;

    public function __construct(Model|array $model)
    {
        $this->model = $model;

        foreach ($this->getProperties() as $name => $attributes) {
            $reflProperty = new ReflectionProperty($this, $name);

            if ($reflProperty->isInitialized($this)) {
                continue;
            }

            $hasGetter = false;
            $sourceKey = $name;
            $castAttributes = [];

            foreach ($attributes as $attribute) {
                if ($attribute instanceof Getter) {
                    $hasGetter = true;
                } elseif ($attribute instanceof Map) {
                    $sourceKey = $attribute->key;
                } elseif ($attribute instanceof Cast) {
                    $castAttributes[] = $attribute;
                }
            }

            if ($hasGetter) {
                $value = $this->{sprintf('get%s', Str::pascal($name))}();
            } elseif ($this->hasValue($model, $sourceKey)) {
                $value = $this->getValue($model, $sourceKey);
            } else {
                // Key absent from source — leave property uninitialized
                continue;
            }

            foreach ($castAttributes as $cast) {
                $value = $cast->cast($name, $value);
            }

            $this->{$name} = $value;
        }
    }

    /**
     * @param  SymfonyRequest  $request
     */
    public function toResponse($request): JsonResponse
    {
        return new JsonResponse([
            'data' => $this->toArray(),
        ], $this->statusCode);
    }

    protected function hasValue(mixed $model, string $key): bool
    {
        if ($model instanceof Model) {
            return Arr::has($model->getAttributes(), $key)
                || $model->offsetExists($key)
                || array_key_exists($key, $model->getRelations());
        }

        return array_key_exists($key, $model);
    }

    protected function getValue(mixed $model, string $key): mixed
    {
        if ($model instanceof Model) {
            return $this->model->{$key};
        }

        return $this->model[$key] ?? null;
    }
}
