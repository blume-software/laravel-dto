<?php

namespace BlumeSoftware\LaravelDTO;

class PaginationMetaDTO extends BaseDTO
{
    public function __construct(
        public readonly int $current_page,
        public readonly int $from,
        public readonly int $last_page,
        public readonly string $path,
        public readonly int $per_page,
        public readonly int $to,
        public readonly int $total,
    ) {}

    public static function fromArray(array $data): static
    {
        return new static(
            current_page: $data['current_page'] ?? $data['currentPage'] ?? 1,
            from: $data['from'] ?? 0,
            last_page: $data['last_page'] ?? $data['lastPage'] ?? 1,
            path: $data['path'] ?? '',
            per_page: $data['per_page'] ?? $data['perPage'] ?? 15,
            to: $data['to'] ?? 0,
            total: $data['total'] ?? 0,
        );
    }
}
