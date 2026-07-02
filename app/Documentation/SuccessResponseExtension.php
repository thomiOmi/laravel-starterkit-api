<?php

declare(strict_types=1);

namespace App\Documentation;

use App\Http\Responses\SuccessResponse;
use Dedoc\Scramble\Extensions\TypeToSchemaExtension;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Types\IntegerType;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\Generator\Types\Type as OpenApiType;
use Dedoc\Scramble\Support\Type\Generic;
use Dedoc\Scramble\Support\Type\ObjectType as ScrambleObjectType;
use Dedoc\Scramble\Support\Type\Type;

class SuccessResponseExtension extends TypeToSchemaExtension
{
    public function shouldHandle(Type $type): bool
    {
        return $type instanceof ScrambleObjectType && $type->isInstanceOf(SuccessResponse::class);
    }

    public function toSchema(Type $type): ObjectType
    {
        $objectType = new ObjectType;
        $required = ['status', 'title', 'detail'];

        $objectType->addProperty('status', new IntegerType);
        $objectType->addProperty('title', new StringType);
        $objectType->addProperty('detail', new StringType);
        $objectType->addProperty('data', $this->resolveDataType($type));

        $objectType->setRequired($required);

        return $objectType;
    }

    public function toResponse(Type $type): ?Response
    {
        return null;
    }

    private function resolveDataType(Type $type): OpenApiType
    {
        if ($type instanceof Generic && isset($type->templateTypes[0])) {
            return $this->openApiTransformer->transform($type->templateTypes[0]);
        }

        return new ObjectType;
    }
}
