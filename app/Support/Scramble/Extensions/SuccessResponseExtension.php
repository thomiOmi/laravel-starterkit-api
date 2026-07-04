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
    public function shouldHandle(Type $type): bool
    {
        return $type instanceof ScrambleObjectType && $type->isInstanceOf(SuccessResponse::class);
    }

    public function toResponse(Type $type): OpenApiResponse
    {
        $code = 200;

        if ($type instanceof Generic && isset($type->templateTypes[1]) && $type->templateTypes[1] instanceof LiteralIntegerType) {
            $code = $type->templateTypes[1]->value;
        }

        $schema = new Schema;
        $schema->type = $this->toSchema($type);

        return (new OpenApiResponse($code))
            ->setContent('application/json', $schema)
            ->setDescription($code === 201 ? 'Created' : 'Success');
    }

    public function toSchema(Type $type): ObjectType
    {
        $code = 200;
        if ($type instanceof Generic && isset($type->templateTypes[1]) && $type->templateTypes[1] instanceof LiteralIntegerType) {
            $code = $type->templateTypes[1]->value;
        }

        $envelope = new ObjectType;
        $envelope->addProperty('status', (new IntegerType)->example($code));
        $envelope->addProperty('title', (new StringType)->example($code === 201 ? 'Created' : 'OK'));
        $envelope->addProperty('detail', (new StringType)->example('The request was processed successfully.'));
        $envelope->addProperty('data', $this->resolveDataType($type));

        if ($this->isPaginated($type)) {
            $this->addPaginationFields($envelope);
        }

        $envelope->setRequired(['status', 'title', 'detail', 'data']);

        return $envelope;
    }

    private function resolveDataType(Type $type): \Dedoc\Scramble\Support\Generator\Types\Type
    {
        if ($type instanceof Generic && isset($type->templateTypes[0])) {
            return $this->openApiTransformer->transform($type->templateTypes[0]);
        }

        return new ObjectType;
    }

    private function isPaginated(Type $type): bool
    {
        if (! $type instanceof Generic || ! isset($type->templateTypes[0])) {
            return false;
        }

        $dataType = $type->templateTypes[0];

        if ($dataType instanceof ScrambleObjectType) {
            if ($dataType->isInstanceOf(AbstractPaginator::class)
                || $dataType->isInstanceOf(CursorPaginator::class)
                || $dataType->isInstanceOf(ResourceCollection::class)) {
                return true;
            }
        }

        if ($dataType instanceof ScrambleArrayType) {
            return true;
        }

        return false;
    }

    private function addPaginationFields(ObjectType $object): void
    {
        $links = new ObjectType;
        $links->addProperty('first', (new StringType)->example('https://example.com'));
        $links->addProperty('last', (new StringType)->example('https://example.com'));
        $links->addProperty('prev', (new StringType)->nullable(true)->example(null));
        $links->addProperty('next', (new StringType)->example('https://example.com'));
        $object->addProperty('links', $links);

        $meta = new ObjectType;
        $meta->addProperty('current_page', (new IntegerType)->example(1));
        $meta->addProperty('from', (new IntegerType)->example(1));
        $meta->addProperty('last_page', (new IntegerType)->example(5));
        $meta->addProperty('path', (new StringType)->example('https://example.com'));
        $meta->addProperty('per_page', (new IntegerType)->example(15));
        $meta->addProperty('to', (new IntegerType)->example(15));
        $meta->addProperty('total', (new IntegerType)->example(75));
        $object->addProperty('meta', $meta);
    }
}
