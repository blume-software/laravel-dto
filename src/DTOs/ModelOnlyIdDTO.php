<?php

namespace BlumeSoftware\LaravelDTO\DTOs;

use BlumeSoftware\LaravelDTO\Attributes\Validation\IsInt;
use BlumeSoftware\LaravelDTO\Attributes\Validation\Required;
use BlumeSoftware\LaravelDTO\RequestDTO;

class ModelOnlyIdDTO extends RequestDTO
{
    #[Required]
    #[IsInt]
    public int $id;
}
