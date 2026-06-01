<?php

namespace BlumeSoftware\LaravelDTO\OpenApi;

use phpDocumentor\Reflection\DocBlockFactory;
use ReflectionMethod;

/**
 * Extracts summary and description text from PHPDoc comments.
 */
class DocBlockExtractor
{
    private DocBlockFactory $factory;

    public function __construct(?DocBlockFactory $factory = null)
    {
        $this->factory = $factory ?? DocBlockFactory::createInstance();
    }

    public function extractSummary(ReflectionMethod $method): ?string
    {
        $raw = $method->getDocComment();

        if (! $raw) {
            return null;
        }

        return $this->factory->create($raw)->getSummary() ?: null;
    }

    public function extractDescription(ReflectionMethod $method): ?string
    {
        $raw = $method->getDocComment();

        if (! $raw) {
            return null;
        }

        $description = (string) $this->factory->create($raw)->getDescription();

        return $description !== '' ? $description : null;
    }
}
