<?php

namespace Blume\LaravelDTO\Tests\DTOs;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Blume\LaravelDTO\Attributes\Validation\IsString;
use Blume\LaravelDTO\Attributes\Validation\Required;
use Blume\LaravelDTO\ValidatedExternalDTO;
use Blume\LaravelDTO\Tests\TestCase;

class ValidatedExternalDTOTest extends TestCase
{
    public function test_construct_validates_and_maps_array_payload(): void
    {
        $dto = new class(['title' => 'Hello']) extends ValidatedExternalDTO
        {
            #[Required]
            #[IsString]
            public string $title;
        };

        $this->assertSame('Hello', $dto->title);
        $this->assertSame(['title' => 'Hello'], $dto->toArray());
    }

    public function test_construct_throws_validation_exception_on_invalid_payload(): void
    {
        $this->expectException(ValidationException::class);

        new class(['title' => 123]) extends ValidatedExternalDTO
        {
            #[Required]
            #[IsString]
            public string $title;
        };
    }

    /**
     * {@see ValidatesFromArray::validate()} does not throw — useful for soft handling or logging.
     */
    public function test_validate_returns_validator_without_throwing(): void
    {
        $fixture = new ValidatedExternalDTOValidatorFixture;

        $invalid = $fixture->validatorFor(['title' => 99]);

        $this->assertTrue($invalid->fails());

        $valid = $fixture->validatorFor(['title' => 'ok']);

        $this->assertFalse($valid->fails());
    }
}

final class ValidatedExternalDTOValidatorFixture extends ValidatedExternalDTO
{
    #[Required]
    #[IsString]
    public string $title;

    public function __construct()
    {
        parent::__construct(['title' => 'seed']);
    }

    public function validatorFor(array $data): Validator
    {
        return $this->validate($data);
    }
}
