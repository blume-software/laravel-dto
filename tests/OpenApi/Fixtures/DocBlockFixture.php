<?php

namespace Blume\LaravelDTO\Tests\OpenApi\Fixtures;

class DocBlockFixture
{
    /**
     * Short summary line.
     */
    public function withSummary(): void {}

    /**
     * Short summary line.
     *
     * Longer description that spans
     * multiple lines.
     */
    public function withSummaryAndDescription(): void {}

    public function noDoc(): void {}
}
