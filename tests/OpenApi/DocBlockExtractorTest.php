<?php

namespace BlumeSoftware\LaravelDTO\Tests\OpenApi;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use BlumeSoftware\LaravelDTO\OpenApi\DocBlockExtractor;

class DocBlockExtractorTest extends TestCase
{
    private DocBlockExtractor $extractor;

    protected function setUp(): void
    {
        $this->extractor = new DocBlockExtractor;
    }

    public function test_extract_summary_returns_null_when_no_docblock(): void
    {
        $method = (new ReflectionClass(Fixtures\FakeController::class))->getMethod('noAttribute');

        $this->assertNull($this->extractor->extractSummary($method));
    }

    public function test_extract_summary_returns_first_line_of_docblock(): void
    {
        $method = (new ReflectionClass(Fixtures\DocBlockFixture::class))->getMethod('withSummary');

        $this->assertSame('Short summary line.', $this->extractor->extractSummary($method));
    }

    public function test_extract_summary_returns_null_for_empty_summary(): void
    {
        $method = (new ReflectionClass(Fixtures\FakeController::class))->getMethod('store');

        // "Create an item." – single-line summary, no description
        $this->assertSame('Create an item.', $this->extractor->extractSummary($method));
    }

    public function test_extract_description_returns_null_when_no_docblock(): void
    {
        $method = (new ReflectionClass(Fixtures\FakeController::class))->getMethod('noAttribute');

        $this->assertNull($this->extractor->extractDescription($method));
    }

    public function test_extract_description_returns_body_when_present(): void
    {
        $method = (new ReflectionClass(Fixtures\DocBlockFixture::class))->getMethod('withSummaryAndDescription');

        $description = $this->extractor->extractDescription($method);

        $this->assertStringContainsString('Longer description', $description);
    }

    public function test_extract_description_returns_null_when_only_summary(): void
    {
        $method = (new ReflectionClass(Fixtures\FakeController::class))->getMethod('store');

        $this->assertNull($this->extractor->extractDescription($method));
    }
}
