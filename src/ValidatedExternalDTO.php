<?php

namespace Blume\LaravelDTO;

use Blume\LaravelDTO\Concerns\HasSchemaName;
use Blume\LaravelDTO\Concerns\ValidatesFromArray;
use Blume\LaravelDTO\Contracts\InfersOpenApiSchema;

/**
 * Base for DTOs built from raw array payloads outside the HTTP request lifecycle
 * (third-party APIs, webhooks, message queues, CLI imports, etc.).
 *
 * Shares the same rules, validation attributes, defaults, and casts as {@see RequestDTO}.
 */
abstract class ValidatedExternalDTO extends BaseDTO implements InfersOpenApiSchema
{
    use HasSchemaName;
    use ValidatesFromArray;

    public function __construct(array $data)
    {
        $this->validateHydrateAndMap($data);
    }
}
