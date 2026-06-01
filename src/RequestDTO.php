<?php

namespace BlumeSoftware\LaravelDTO;

use Illuminate\Http\Request;
use BlumeSoftware\LaravelDTO\Concerns\HasSchemaName;
use BlumeSoftware\LaravelDTO\Concerns\ValidatesFromArray;
use BlumeSoftware\LaravelDTO\Contracts\InfersOpenApiSchema;

abstract class RequestDTO extends BaseDTO implements InfersOpenApiSchema
{
    use HasSchemaName;
    use ValidatesFromArray;

    public function __construct(
        ?array $data = null,
    ) {
        if (! $data) {
            $request = app(Request::class);

            $data = array_merge(
                $request->query->all(),
                $request->request->all(),
                $request->route()?->parameters() ?? []
            );
        }

        $this->validateHydrateAndMap($data);
    }
}
