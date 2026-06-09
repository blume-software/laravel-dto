<?php

namespace BlumeSoftware\LaravelDTO;

use BlumeSoftware\LaravelDTO\Concerns\HasSchemaName;
use BlumeSoftware\LaravelDTO\Contracts\InfersOpenApiSchema;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use ReflectionException;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

abstract class ResponseDTO extends BaseDTO implements InfersOpenApiSchema, Responsable
{
    use HasSchemaName;

    /**
     * @param  SymfonyRequest  $request
     *
     * @throws ReflectionException
     */
    public function toResponse($request): JsonResponse
    {
        return new JsonResponse($this->getResponseData(), $this->statusCode);
    }

    /**
     * @throws ReflectionException
     */
    public function getResponseData(): array
    {
        return [
            'data' => $this->toArray(),
        ];
    }
}
