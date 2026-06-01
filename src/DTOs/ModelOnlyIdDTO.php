<?php

namespace Blume\LaravelDTO\DTOs;

use Blume\LaravelDTO\Attributes\Validation\IsInt;
use Blume\LaravelDTO\Attributes\Validation\Required;
use Blume\LaravelDTO\RequestDTO;

class ModelOnlyIdDTO extends RequestDTO
{
    #[Required]
    #[IsInt]
    public int $id;
}
