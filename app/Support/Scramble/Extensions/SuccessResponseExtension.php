<?php

declare(strict_types=1);

namespace App\Support\Scramble\Extensions;

use App\Http\Responses\SuccessResponse;
use Dedoc\Scramble\Extensions\TypeToSchemaExtension;
use Dedoc\Scramble\Support\Generator\Response as OpenApiResponse;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\IntegerType;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\Type\ArrayType as ScrambleArrayType;
use Dedoc\Scramble\Support\Type\Generic;
use Dedoc\Scramble\Support\Type\Literal\LiteralIntegerType;
use Dedoc\Scramble\Support\Type\ObjectType as ScrambleObjectType;
use Dedoc\Scramble\Support\Type\Type;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Pagination\CursorPaginator;

final class SuccessResponseExtension extends TypeToSchemaExtension
{
    /**
     * Determine if this extension should handle the given Scramble type inference.
     */
    public function shouldHandle(Type $type): bool
    {
        return $type instanceof ScrambleObjectType && $type->isInstanceOf(SuccessResponse::class);
    }

    /**
     * Overrides the OpenAPI response generation layer to control status codes dynamically.
     */
    public function toResponse(Type $type): ?OpenApiResponse
    {
        $code = 200;

        // Extract the explicit status code integer from the second generic parameter: SuccessResponse<Data, 201>
        if ($type instanceof Generic && isset($type->templateTypes[1]) && $type->templateTypes[1] instanceof LiteralIntegerType) {
            $code = $type->templateTypes[1]->value;
        }

        $schema = new Schema;
        $schema->type = $this->toSchema($type);

        return (new OpenApiResponse($code))
            ->setContent('application/json', $schema)
            ->setDescription($code === 201 ? 'Created' : 'Success');
    }

    /**
     * Constructs the outer JSON envelope structure for visual rendering in the Web UI.
     */
    public function toSchema(Type $type): ObjectType
    {
        $code = 200;
        if ($type instanceof Generic && isset($type->templateTypes[1]) && $type->templateTypes[1] instanceof LiteralIntegerType) {
            $code = $type->templateTypes[1]->value;
        }

        $envelope = (new ObjectType)
            ->addProperty('status', (new IntegerType)->example($code))
            ->addProperty('title', (new StringType)->example($code === 201 ? 'Created' : 'OK'))
            ->addProperty('detail', (new StringType)->example('The request was processed successfully.'))
            ->addProperty('data', $this->resolveDataType($type));

        // Inject pagination layout if the endpoint indicates a listing/paginated array
        if ($this->isPaginated($type)) {
            $this->addPaginationFields($envelope);
        }

        $envelope->setRequired(['status', 'title', 'detail', 'data']);

        return $envelope;
    }

    /**
     * Resolves and transforms the internal payload structure from the first generic parameter <T>
     */
    private function resolveDataType(Type $type): \Dedoc\Scramble\Support\Generator\Types\Type
    {
        // Fix: Explicitly drill down into the first array element [0] of templateTypes
        if ($type instanceof Generic && isset($type->templateTypes[0])) {
            return $this->openApiTransformer->transform($type->templateTypes[0]);
        }

        return new ObjectType;
    }

    /**
     * Checks if the response requires pagination envelopes (links & meta).
     */
    private function isPaginated(Type $type): bool
    {
        if (! $type instanceof Generic || ! isset($type->templateTypes[0])) {
            return false;
        }

        // Fix: Explicitly check the first array element [0] of templateTypes
        $dataType = $type->templateTypes[0];

        // 1. Class Check: Scan if the explicit class types inside generics are paginators
        if ($dataType instanceof ScrambleObjectType) {
            if ($dataType->isInstanceOf(AbstractPaginator::class)
                || $dataType->isInstanceOf(CursorPaginator::class)
                || $dataType->isInstanceOf(ResourceCollection::class)) {
                return true;
            }
        }

        // 2. Type Check: Automatically trigger pagination if data is a generic list array (e.g., array<int, DeviceResource>)
        if ($dataType instanceof ScrambleArrayType) {
            return true;
        }

        return false;
    }

    /**
     * Appends standardized links and meta structures to the main envelope schema.
     */
    private function addPaginationFields(ObjectType $object): void
    {
        $object->addProperty('links', (new ObjectType)
            ->addProperty('first', (new StringType)->example('https://example.com'))
            ->addProperty('last', (new StringType)->example('https://example.com'))
            ->addProperty('prev', (new StringType)->nullable(true)->example(null))
            ->addProperty('next', (new StringType)->example('https://example.com'))
        );

        $object->addProperty('meta', (new ObjectType)
            ->addProperty('current_page', (new IntegerType)->example(1))
            ->addProperty('from', (new IntegerType)->example(1))
            ->addProperty('last_page', (new IntegerType)->example(5))
            ->addProperty('path', (new StringType)->example('https://example.com'))
            ->addProperty('per_page', (new IntegerType)->example(15))
            ->addProperty('to', (new IntegerType)->example(15))
            ->addProperty('total', (new IntegerType)->example(75))
        );
    }
}
