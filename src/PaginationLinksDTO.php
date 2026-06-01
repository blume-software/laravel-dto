<?php

namespace BlumeSoftware\LaravelDTO;

class PaginationLinksDTO extends BaseDTO
{
    public function __construct(
        public readonly ?string $first,
        public readonly ?string $last,
        public readonly ?string $prev,
        public readonly ?string $next,
    ) {}

    public static function fromArray(array $data): static
    {
        return new static(
            first: $data['first'] ?? null,
            last: $data['last'] ?? null,
            prev: $data['prev'] ?? null,
            next: $data['next'] ?? null,
        );
    }
}
