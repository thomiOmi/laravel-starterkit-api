<?php

declare(strict_types=1);

namespace App\Extensions;

use App\Http\Responses\SuccessResponse;
use Dedoc\Scramble\Extensions\TypeToSchemaExtension;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\IntegerType;
use Dedoc\Scramble\Support\Generator\Types\NullType;
use Dedoc\Scramble\Support\Generator\Types\ObjectType as OpenApiObjectType;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\Generator\Types\Type as OpenApiType;
use Dedoc\Scramble\Support\Type\Generic;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\Type;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SuccessResponseTypeToSchema extends TypeToSchemaExtension
{
    public function shouldHandle(Type $type): bool
    {
        return $type instanceof Generic
            && $type->isInstanceOf(SuccessResponse::class);
    }

    public function toResponse(Type $type): ?Response
    {
        $dataType = $type instanceof Generic ? ($type->templateTypes[0] ?? null) : null;

        $objectType = new OpenApiObjectType;

        $objectType->addProperty('status', (new IntegerType)->example(200));
        $objectType->addProperty('title', (new StringType)->example('OK'));
        $objectType->addProperty('detail', new StringType);

        if ($dataType) {
            $dataSchema = $this->openApiTransformer->transform($dataType);
            $objectType->addProperty('data', $dataSchema);
        } else {
            $objectType->addProperty('data', new NullType);
        }

        if ($dataType instanceof ObjectType && $dataType->isInstanceOf(ResourceCollection::class)) {
            $objectType->addProperty('links', self::buildLinksType());
            $objectType->addProperty('meta', self::buildMetaType());
        }

        $objectType->setRequired(['status', 'title', 'detail', 'data']);

        /** @var Schema $schema */
        $schema = Schema::fromType($objectType);
        $response = new Response(200);
        $response->setContent('application/json', $schema);
        $response->setDescription('Successful response');

        return $response;
    }

    private static function buildLinksType(): OpenApiType
    {
        $links = new OpenApiObjectType;
        $links->addProperty('first', (new StringType)->nullable(true));
        $links->addProperty('last', (new StringType)->nullable(true));
        $links->addProperty('prev', (new StringType)->nullable(true));
        $links->addProperty('next', (new StringType)->nullable(true));

        return $links;
    }

    private static function buildMetaType(): OpenApiType
    {
        $meta = new OpenApiObjectType;
        $meta->addProperty('current_page', new IntegerType);
        $meta->addProperty('from', (new IntegerType)->nullable(true));
        $meta->addProperty('last_page', new IntegerType);
        $meta->addProperty('path', new StringType);
        $meta->addProperty('per_page', new IntegerType);
        $meta->addProperty('to', (new IntegerType)->nullable(true));
        $meta->addProperty('total', new IntegerType);

        return $meta;
    }
}
