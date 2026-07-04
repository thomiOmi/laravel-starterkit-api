<?php

declare(strict_types=1);

namespace App\Support\Scramble\Extensions;

use App\Support\Scramble\Response;
use Dedoc\Scramble\Extensions\ExceptionToResponseExtension;
use Dedoc\Scramble\Support\Generator\Reference;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\ArrayType;
use Dedoc\Scramble\Support\Generator\Types\IntegerType;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\Type\ObjectType as ScrambleObjectType;
use Dedoc\Scramble\Support\Type\Type;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\RecordsNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\LengthRequiredHttpException;
use Symfony\Component\HttpKernel\Exception\LockedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;
use Symfony\Component\HttpKernel\Exception\PreconditionRequiredHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;

class ProblemDetailsExceptionToResponseExtension extends ExceptionToResponseExtension
{
    public function shouldHandle(Type $type): bool
    {
        return $type instanceof ScrambleObjectType && (
            $type->isInstanceOf(ValidationException::class)
            || $type->isInstanceOf(AuthenticationException::class)
            || $type->isInstanceOf(AuthorizationException::class)
            || $type->isInstanceOf(RecordsNotFoundException::class)
            || $type->isInstanceOf(NotFoundHttpException::class)
            || $type->isInstanceOf(HttpException::class)
        );
    }

    /**
     * @return Response|null
     */
    public function toResponse(Type $type)
    {
        if ($type->isInstanceOf(ValidationException::class)) {
            return $this->validationResponse();
        }

        $code = $this->resolveStatusCode($type);

        if ($code === null) {
            return null;
        }

        $description = $this->describeCode($code);
        $detail = $this->detailExample($code);

        $example = $this->problemExample($code, $description, $detail);
        $problemSchema = $this->buildProblemSchema($code, $description, $detail, $example);

        $response = new Response($code);
        $response->setDescription($description);
        $response->setContent('application/problem+json', $this->toSchema($problemSchema));
        $response->setMediaTypeExample('application/problem+json', $example);

        return $response;
    }

    public function reference(ScrambleObjectType $type): Reference
    {
        return new Reference('responses', $type->name, $this->components, $type->name);
    }

    private function validationResponse(): Response
    {
        $example = $this->validationExample();

        $validationSchema = $this->buildValidationSchema($example);

        $response = new Response(422);
        $response->setDescription('Validation Error (RFC 9457 Problem Details)');
        $response->setContent('application/problem+json', $this->toSchema($validationSchema));
        // $response->setMediaTypeExample('application/problem+json', $example);

        return $response;
    }

    private function toSchema(ObjectType $type): Schema
    {
        $schema = new Schema;
        $schema->type = $type;

        return $schema;
    }

    /**
     * @param  array<string, mixed>  $example
     */
    private function buildProblemSchema(int $code, string $description, string $detail, array $example): ObjectType
    {
        $schema = new ObjectType;

        $typeProp = new StringType;
        $typeProp->setDescription('A URI reference that identifies the problem type.');
        $typeProp->examples(['https://example.com/error', 'about:blank']);
        $schema->addProperty('type', $typeProp);

        $titleProp = new StringType;
        $titleProp->setDescription('A short, human-readable summary of the problem type.');
        $titleProp->example($description);
        $schema->addProperty('title', $titleProp);

        $statusProp = new IntegerType;
        $statusProp->setDescription('The HTTP status code generated by the origin server.');
        $statusProp->example($code);
        $schema->addProperty('status', $statusProp);

        $detailProp = new StringType;
        $detailProp->setDescription('A human-readable explanation specific to this occurrence.');
        $detailProp->example($detail);
        $schema->addProperty('detail', $detailProp);

        $instanceProp = new StringType;
        $instanceProp->setDescription('A URI reference that identifies the specific occurrence of the problem.');
        $instanceProp->example('/api/v1/resource');
        $schema->addProperty('instance', $instanceProp);

        $timestampProp = new StringType;
        $timestampProp->setDescription('The time the error occurred in ISO 8601 format.');
        $timestampProp->example('2026-07-04T05:50:00Z');
        $schema->addProperty('timestamp', $timestampProp);

        $schema->setRequired(['type', 'title', 'status', 'detail', 'instance', 'timestamp']);
        $schema->example($example);

        return $schema;
    }

    /**
     * @param  array<string, mixed>  $example
     */
    private function buildValidationSchema(array $example): ObjectType
    {
        $schema = new ObjectType;

        $typeProp = new StringType;
        $typeProp->setDescription('A URI reference that identifies the validation problem type.');
        $typeProp->examples(['https://example.com/validation-error', 'about:blank']);
        $schema->addProperty('type', $typeProp);

        $titleProp = new StringType;
        $titleProp->setDescription('A short, human-readable summary of the validation error.');
        $titleProp->example('Unprocessable Entity');
        $schema->addProperty('title', $titleProp);

        $statusProp = new IntegerType;
        $statusProp->setDescription('The HTTP status code generated by the origin server.');
        $statusProp->example(422);
        $schema->addProperty('status', $statusProp);

        $detailProp = new StringType;
        $detailProp->setDescription('A human-readable explanation specific to this validation occurrence.');
        $detailProp->example('The given data was invalid.');
        $schema->addProperty('detail', $detailProp);

        $instanceProp = new StringType;
        $instanceProp->setDescription('A URI reference that identifies the specific occurrence of the problem.');
        $instanceProp->example('/api/v1/users');
        $schema->addProperty('instance', $instanceProp);

        $timestampProp = new StringType;
        $timestampProp->setDescription('The time the error occurred in ISO 8601 format.');
        $timestampProp->example('2026-07-04T05:50:00Z');
        $schema->addProperty('timestamp', $timestampProp);

        $itemsType = new ArrayType;
        $itemsType->setItems(new StringType);

        $errorsType = new ObjectType;
        $errorsType->setDescription('A detailed description of each field that failed validation.');
        $errorsType->additionalProperties($itemsType);
        $schema->addProperty('errors', $errorsType);

        $schema->setRequired(['type', 'title', 'status', 'detail', 'timestamp', 'errors']);
        $schema->example($example);

        return $schema;
    }

    /**
     * @return array<string, mixed>
     */
    private function problemExample(int $code, string $description, string $detail): array
    {
        return [
            'type' => 'https://example.com/error',
            'title' => $description,
            'status' => $code,
            'detail' => $detail,
            'instance' => '/api/v1/resource',
            'timestamp' => '2026-07-04T05:50:00Z',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validationExample(): array
    {
        return [
            'type' => 'https://example.com/validation-error',
            'title' => 'Unprocessable Entity',
            'status' => 422,
            'detail' => 'The email field is required.',
            'instance' => '/api/v1/users',
            'timestamp' => '2026-07-04T05:50:00Z',
            'errors' => ['email' => ['The email field is required.']],
        ];
    }

    private function resolveStatusCode(Type $type): ?int
    {
        if (! $type instanceof ScrambleObjectType) {
            return null;
        }

        if ($type->isInstanceOf(AuthenticationException::class)) {
            return 401;
        }

        if ($type->isInstanceOf(AuthorizationException::class)) {
            return 403;
        }

        if ($type->isInstanceOf(RecordsNotFoundException::class) || $type->isInstanceOf(NotFoundHttpException::class)) {
            return 404;
        }

        if ($type->isInstanceOf(HttpException::class)) {
            return $this->httpExceptionCode($type);
        }

        return null;
    }

    private function httpExceptionCode(ScrambleObjectType $type): ?int
    {
        return match (true) {
            $type->isInstanceOf(BadRequestHttpException::class) => 400,
            $type->isInstanceOf(UnauthorizedHttpException::class) => 401,
            $type->isInstanceOf(AccessDeniedHttpException::class) => 403,
            $type->isInstanceOf(MethodNotAllowedHttpException::class) => 405,
            $type->isInstanceOf(NotAcceptableHttpException::class) => 406,
            $type->isInstanceOf(ConflictHttpException::class) => 409,
            $type->isInstanceOf(GoneHttpException::class) => 410,
            $type->isInstanceOf(LengthRequiredHttpException::class) => 411,
            $type->isInstanceOf(PreconditionFailedHttpException::class) => 412,
            $type->isInstanceOf(UnsupportedMediaTypeHttpException::class) => 415,
            $type->isInstanceOf(UnprocessableEntityHttpException::class) => 422,
            $type->isInstanceOf(LockedHttpException::class) => 423,
            $type->isInstanceOf(PreconditionRequiredHttpException::class) => 428,
            $type->isInstanceOf(TooManyRequestsHttpException::class) => 429,
            $type->isInstanceOf(ServiceUnavailableHttpException::class) => 503,
            default => null,
        };
    }

    private function describeCode(int $code): string
    {
        return match ($code) {
            400 => 'Bad Request',
            401 => 'Unauthenticated',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            415 => 'Unsupported Media Type',
            422 => 'Unprocessable Entity',
            423 => 'Locked',
            428 => 'Precondition Required',
            429 => 'Too Many Requests',
            503 => 'Service Unavailable',
            default => 'HTTP Error',
        };
    }

    private function detailExample(int $code): string
    {
        return match ($code) {
            401 => 'Authentication is required.',
            403 => 'You do not have permission to perform this action.',
            404 => 'The requested resource was not found.',
            default => 'An error occurred.',
        };
    }
}
