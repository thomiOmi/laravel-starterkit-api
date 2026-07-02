<?php

declare(strict_types=1);

namespace App\Documentation;

use App\Http\Responses\ProblemResponse;
use Dedoc\Scramble\Extensions\TypeToSchemaExtension;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\ArrayType;
use Dedoc\Scramble\Support\Generator\Types\BooleanType;
use Dedoc\Scramble\Support\Generator\Types\IntegerType;
use Dedoc\Scramble\Support\Generator\Types\NumberType;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\Generator\Types\Type as ScrambleType;
use Dedoc\Scramble\Support\Type\Type;

class ProblemResponseExtension extends TypeToSchemaExtension
{
    public function shouldHandle(Type $type): bool
    {
        $name = $type->toString();

        return $name === ProblemResponse::class
            || $name === \Symfony\Component\HttpFoundation\Response::class;
    }

    public function toSchema(Type $type): Schema
    {
        $objectType = $this->buildObjectTypeFromConstructor();

        $schema = new Schema;
        $schema->type = $objectType;

        return $schema;
    }

    public function toResponse(Type $type): ?Response
    {
        return null;
    }

    private function buildObjectTypeFromConstructor(): ObjectType
    {
        $objectType = new ObjectType;
        $required = [];

        $reflection = new \ReflectionClass(ProblemResponse::class);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return $objectType;
        }

        foreach ($constructor->getParameters() as $param) {
            $name = $param->getName();

            if ($name === 'typeKey' || $name === 'instance') {
                continue;
            }

            $scrambleType = $this->phpTypeToScrambleType($param);

            $objectType->addProperty($name, $scrambleType);

            if (! $param->isDefaultValueAvailable()) {
                $required[] = $name;
            }
        }

        if ($required !== []) {
            $objectType->setRequired($required);
        }

        return $objectType;
    }

    private function phpTypeToScrambleType(\ReflectionParameter $param): ScrambleType
    {
        $type = $param->getType();

        if ($type === null) {
            return new StringType;
        }

        $typeName = $type instanceof \ReflectionNamedType ? $type->getName() : 'mixed';
        $allowsNull = $type->allowsNull();

        /** @var ScrambleType $built */
        $built = match ($typeName) {
            'int', 'integer' => new IntegerType,
            'float', 'double' => new NumberType,
            'bool', 'boolean' => new BooleanType,
            'array' => (new ArrayType)->setItems(new StringType),
            'mixed', 'object' => new ObjectType,
            default => new StringType,
        };

        if ($allowsNull) {
            $built->nullable(true);
        }

        return $built;
    }
}
