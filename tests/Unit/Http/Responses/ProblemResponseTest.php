<?php

declare(strict_types=1);

use App\Http\Responses\ProblemResponse;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;

covers(ProblemResponse::class);

beforeEach(function () {
    config()->set('errors.docs_url', 'https://docs.example.com/problems');
    config()->set('errors.types.default', 'general-error');
    config()->set('errors.types.validation', 'validation-failed');
});

describe('basic error structure', function () {
    it('returns default error structure', function () {
        $response = new ProblemResponse(detail: 'Something went wrong')->toResponse(new Request);

        expect($response->getStatusCode())->toBe(400)
            ->and($response->headers->get('Content-Type'))->toBe('application/problem+json');

        $data = responseData($response);
        expect($data)->toMatchArray(['type' => 'https://docs.example.com/problems/general-error', 'title' => 'Bad Request', 'status' => 400, 'detail' => 'Something went wrong'])
            ->toHaveKey('timestamp');
    });

    it('uses custom title when provided', function () {
        $response = new ProblemResponse(
            typeKey: 'validation',
            title: 'Validation Failed',
            status: 422,
            detail: 'The given data was invalid.',
        )->toResponse(new Request);

        $data = responseData($response);
        expect($data)->toMatchArray(['title' => 'Validation Failed', 'type' => 'https://docs.example.com/problems/validation-failed', 'status' => 422]);
    });

    it('falls back to HTTP status text when title is empty', function () {
        $response = new ProblemResponse(
            title: '',
            status: 404,
            detail: 'Not found',
        )->toResponse(new Request);

        $data = responseData($response);

        expect($data['title'])->toBe('Not Found');
    });

    it('uses unknown status text fallback', function () {
        $response = new ProblemResponse(
            title: '',
            status: 499,
            detail: 'Custom error',
        )->toResponse(new Request);

        $data = responseData($response);

        expect($data['title'])->toBe('Unknown Error');
    });
});

describe('type URI', function () {
    it('uses about:blank type URI', function () {
        $response = new ProblemResponse(
            typeKey: 'about:blank',
            detail: 'Custom error',
        )->toResponse(new Request);

        $data = responseData($response);

        expect($data['type'])->toBe('about:blank');
    });
});

describe('instance', function () {
    it('includes instance URI', function () {
        $response = new ProblemResponse(
            detail: 'Error',
            instance: '/api/v1/users/123',
        )->toResponse(new Request);

        $data = responseData($response);

        expect($data['instance'])->toBe('/api/v1/users/123');
    });

    it('omits instance when empty', function () {
        $response = new ProblemResponse(detail: 'Error')->toResponse(new Request);

        $data = responseData($response);

        expect($data)->not->toHaveKey('instance');
    });
});

describe('extensions', function () {
    it('includes extension members', function () {
        $response = new ProblemResponse(
            detail: 'Error',
            extensions: ['errors' => ['name' => ['Required']]],
        )->toResponse(new Request);

        $data = responseData($response);

        expect($data['errors'])->toBe(['name' => ['Required']]);
    });

    it('converts Arrayable extensions to array', function () {
        $arrayable = new class implements Arrayable
        {
            public function toArray(): array
            {
                return ['key' => 'value'];
            }
        };

        $response = new ProblemResponse(
            detail: 'Error',
            extensions: ['meta' => $arrayable],
        )->toResponse(new Request);

        $data = responseData($response);

        expect($data['meta'])->toBe(['key' => 'value']);
    });

    it('filters protected keys from extensions', function () {
        $response = new ProblemResponse(
            detail: 'Error',
            extensions: ['status' => 'should_not_override', 'custom' => 'ok'],
        )->toResponse(new Request);

        $data = responseData($response);
        expect($data)->toMatchArray(['custom' => 'ok', 'status' => 400]);
    });
});

describe('headers', function () {
    it('merges custom headers', function () {
        $response = new ProblemResponse(
            detail: 'Error',
            headers: ['X-Request-ID' => 'abc-123'],
        )->toResponse(new Request);

        expect($response->headers->get('X-Request-ID'))->toBe('abc-123')
            ->and($response->headers->get('Content-Type'))->toBe('application/problem+json');
    });
});

describe('snapshots', function () {
    it('matches snapshot for default error', function () {
        $response = new ProblemResponse(detail: 'Something went wrong')->toResponse(new Request);

        expect($response->getContent())->toMatchSnapshot();
    });

    it('matches snapshot with extensions and instance', function () {
        $response = new ProblemResponse(
            typeKey: 'validation',
            title: 'Validation Failed',
            status: 422,
            detail: 'The given data was invalid.',
            extensions: ['errors' => ['email' => ['The email field is required.']]],
            instance: '/api/v1/users',
        )->toResponse(new Request);

        expect($response->getContent())->toMatchSnapshot();
    });
});
