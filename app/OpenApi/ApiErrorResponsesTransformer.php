<?php

namespace App\OpenApi;

use Dedoc\Scramble\Contracts\OperationTransformer;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\BooleanType;
use Dedoc\Scramble\Support\Generator\Types\IntegerType;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\RouteInfo;

class ApiErrorResponsesTransformer implements OperationTransformer
{
    /**
     * Inject standardised ApiException error responses into every documented operation.
     */
    public function handle(Operation $operation, RouteInfo $routeInfo): void
    {
        foreach ($this->errorResponses() as [$statusCode, $description, $extra]) {
            $operation->addResponse(
                Response::make($statusCode)
                    ->setDescription($description)
                    ->setContent('application/json', Schema::fromType($this->errorSchema($statusCode, $extra)))
            );
        }
    }

    /**
     * Build the shared error envelope schema with optional extra properties.
     *
     * @param array<string, string> $extra
     */
    private function errorSchema(int $statusCode, array $extra = []): ObjectType
    {
        $type = new ObjectType;

        $type->addProperty('success', (new BooleanType)->example(false));
        $type->addProperty('code', (new IntegerType)->example($statusCode));
        $type->addProperty('message', new StringType()->setDescription('Human-readable error description.'));

        foreach ($extra as $property => $description) {
            $type->addProperty($property, (new StringType)->setDescription($description));
        }

        $type->setRequired(['success', 'code', 'message']);

        return $type;
    }

    /**
     * Enumerate all error responses produced by ApiException::renderApiException().
     *
     * @return array<int, array{int, string, array<string, string>}>
     */
    private function errorResponses(): array
    {
        return [
            [401, 'Unauthenticated. Token missing or invalid.', []],
            [404, 'Resource not found.', []],
            [405, 'Method not allowed.', []],
            [422, 'Validation failed.', ['errors' => 'Field-level validation messages keyed by field name.']],
            [429, 'Too many requests. Rate limit exceeded.', []],
            [500, 'Unexpected server error.', []],
        ];
    }
}
