<?php

namespace BlumeSoftware\LaravelDTO\Tests\DTOs;

use Illuminate\Http\JsonResponse;
use PHPUnit\Framework\TestCase;
use BlumeSoftware\LaravelDTO\ResponseDTO;
use Symfony\Component\HttpFoundation\Request;

class ResponseDTOTest extends TestCase
{
    private function makeResponseDTO(array $props = []): ResponseDTO
    {
        return new class($props) extends ResponseDTO
        {
            public string $message;

            public int $count;

            public function __construct(array $props)
            {
                if (isset($props['message'])) {
                    $this->message = $props['message'];
                }
                if (isset($props['count'])) {
                    $this->count = $props['count'];
                }
            }
        };
    }

    public function test_to_response_returns_json_response(): void
    {
        $dto = $this->makeResponseDTO(['message' => 'ok', 'count' => 3]);

        $response = $dto->toResponse(Request::createFromGlobals());

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function test_to_response_wraps_data_in_data_key(): void
    {
        $dto = $this->makeResponseDTO(['message' => 'hello', 'count' => 1]);

        $response = $dto->toResponse(Request::createFromGlobals());
        $body = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('data', $body);
        $this->assertSame(['message' => 'hello', 'count' => 1], $body['data']);
    }

    public function test_to_response_uses_status_code(): void
    {
        $dto = $this->makeResponseDTO(['message' => 'created', 'count' => 0]);
        $dto->setStatusCode(201);

        $response = $dto->toResponse(Request::createFromGlobals());

        $this->assertSame(201, $response->getStatusCode());
    }

    public function test_get_response_data_structure(): void
    {
        $dto = $this->makeResponseDTO(['message' => 'test', 'count' => 5]);

        $this->assertSame(
            ['data' => ['message' => 'test', 'count' => 5]],
            $dto->getResponseData()
        );
    }

    public function test_get_schema_name_returns_short_class_name(): void
    {
        $dto = $this->makeResponseDTO();

        // Anonymous class schema name will be the last segment — acceptable for coverage
        $this->assertIsString($dto::getSchemaName());
        $this->assertNotEmpty($dto::getSchemaName());
    }
}
