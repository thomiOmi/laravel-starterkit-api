<?php

declare(strict_types=1);

namespace App\Support\Scramble\Extensions;

use App\Http\Responses\SuccessResponse;
use Dedoc\Scramble\Extensions\TypeToSchemaExtension;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\IntegerType;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\Generator\Types\Type as OpenApiType;
use Dedoc\Scramble\Support\Type\Generic;
use Dedoc\Scramble\Support\Type\Literal\LiteralIntegerType;
use Dedoc\Scramble\Support\Type\ObjectType as ScrambleObjectType;
use Dedoc\Scramble\Support\Type\Type;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Pagination\CursorPaginator;

class SuccessResponseExtension extends TypeToSchemaExtension
{
    public function shouldHandle(Type $type): bool
    {
        return $type instanceof ScrambleObjectType && $type->isInstanceOf(SuccessResponse::class);
    }

    public function toSchema(Type $type): ObjectType
    {
        $status = 200;
        if ($type instanceof Generic && isset($type->templateTypes[1]) && $type->templateTypes[1] instanceof LiteralIntegerType) {
            $status = $type->templateTypes[1]->value;
        }

        $objectType = new ObjectType;
        $objectType->addProperty('status', (new IntegerType)->example($status));
        $objectType->addProperty('title', (new StringType)->example('OK'));
        $objectType->addProperty('detail', (new StringType)->example('Operation successful.'));
        $objectType->addProperty('data', $this->resolveDataType($type));

        if ($this->isPaginated($type)) {
            $this->addPaginationFields($objectType);
        }

        $objectType->setRequired(['status', 'title', 'detail', 'data']);

        return $objectType;
    }

    public function toResponse(Type $type): ?Response
    {
        $code = 200;
        if ($type instanceof Generic && isset($type->templateTypes[1]) && $type->templateTypes[1] instanceof LiteralIntegerType) {
            $code = $type->templateTypes[1]->value;
        }

        $schema = new Schema;
        $schema->type = $this->toSchema($type);

        return (new Response($code))
            ->setContent('application/json', $schema)
            ->setDescription('Standard Success Response');
    }

    private function resolveDataType(Type $type): OpenApiType
    {
        if (! $type instanceof Generic || ! isset($type->templateTypes[0])) {
            return new ObjectType;
        }

        $dataType = $type->templateTypes[0];

        return $this->openApiTransformer->transform($dataType);
    }

    private function isPaginated(Type $type): bool
    {
        if (! $type instanceof Generic || ! isset($type->templateTypes[0])) {
            return false;
        }

        $dataType = $type->templateTypes[0];

        if (! $dataType instanceof ScrambleObjectType) {
            return false;
        }

        return $dataType->isInstanceOf(AbstractPaginator::class)
            || $dataType->isInstanceOf(CursorPaginator::class)
            || $dataType->isInstanceOf(ResourceCollection::class);
    }

    private function addPaginationFields(ObjectType $object): void
    {
        $links = new ObjectType;
        $links->addProperty('first', (new StringType)->example('https://api.com'));
        $links->addProperty('last', (new StringType)->example('https://api.com'));
        $links->addProperty('prev', (new StringType)->nullable(false)->example(null));
        $links->addProperty('next', (new StringType)->example('https://api.com'));
        $object->addProperty('links', $links);

        $meta = new ObjectType;
        $meta->addProperty('current_page', (new IntegerType)->example(1));
        $meta->addProperty('from', (new IntegerType)->example(1));
        $meta->addProperty('last_page', (new IntegerType)->example(5));
        $meta->addProperty('path', (new StringType)->example('https://api.com'));
        $meta->addProperty('per_page', (new IntegerType)->example(15));
        $meta->addProperty('to', (new IntegerType)->example(15));
        $meta->addProperty('total', (new IntegerType)->example(75));
        $object->addProperty('meta', $meta);
    }
}
