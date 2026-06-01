<?php

namespace BlumeSoftware\LaravelDTO\OpenApi\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use JsonException;
use BlumeSoftware\LaravelDTO\OpenApi\Generator;

class OpenApiController extends Controller
{
    public function __construct(
        protected Generator $generator
    ) {}

    public function openapi(): Response
    {
        return response($this->getDefinition())
            ->header('Content-Type', 'application/json');
    }

    public function openapiUI(): View
    {
        return view('laravel-dto::openapi-ui', [
            'spec' => $this->getDefinition(),
        ]);
    }

    private function getDefinition(): string
    {
        /**
         * @throws JsonException
         */
        $generate = fn (): string => $this->generator->generate()->toJson();

        if (config('app.env') === 'local') {
            return $generate();
        }

        return Cache::remember(
            'openapi-definition',
            1440,
            $generate
        );
    }
}
