<?php

declare(strict_types=1);

use App\Http\Responses\ProblemResponse;
use App\Http\Responses\SuccessResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;
use Modules\User\Models\User;

uses(RefreshDatabase::class);

describe('SuccessResponse', function () {
    it('returns a flat structure with status, title, detail, and data', function () {
        Route::get('/api/v1/_test/success', function () {
            return new SuccessResponse(
                title: 'OK',
                detail: 'Request completed successfully.',
                data: ['id' => 1, 'name' => 'Test'],
                status: 200,
            );
        });

        $response = $this->getJson('/api/v1/_test/success');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'title',
            'detail',
            'data',
        ]);

        $json = $response->json();

        expect($json['status'])->toBe(200)
            ->and($json['title'])->toBe('OK')
            ->and($json['detail'])->toBe('Request completed successfully.')
            ->and($json['data'])->toBe(['id' => 1, 'name' => 'Test']);
    });

    it('handles paginated collection with links and meta at root level', function () {
        User::factory()->count(15)->create();

        Route::get('/api/v1/_test/paginate', function () {
            $users = User::paginate(5, ['*'], 'page', 1);

            return new SuccessResponse(
                title: 'OK',
                detail: 'Paginated list of users.',
                data: $users,
                status: 200,
            );
        });

        $response = $this->getJson('/api/v1/_test/paginate');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'title',
            'detail',
            'data',
            'links',
            'meta' => ['current_page', 'from', 'last_page', 'per_page', 'to', 'total'],
        ]);

        $json = $response->json();

        expect($json['links'])->toHaveKeys(['first', 'last', 'next'])
            ->and($json['links'])->not->toHaveKey('prev');

        expect($json['status'])->toBe(200)
            ->and($json['title'])->toBe('OK')
            ->and($json['detail'])->toBe('Paginated list of users.')
            ->and($json['data'])->toHaveCount(5)
            ->and($json['meta']['current_page'])->toBe(1)
            ->and($json['meta']['per_page'])->toBe(5)
            ->and($json['meta']['total'])->toBe(15)
            ->and($json['meta']['last_page'])->toBe(3);

        expect($json)->not->toHaveKey('pagination');
    });

    it('merges extra fields at the root level', function () {
        Route::get('/api/v1/_test/extra', function () {
            return new SuccessResponse(
                title: 'Created',
                detail: 'Resource created.',
                data: ['id' => 1],
                status: 201,
                extra: ['location' => '/api/v1/resources/1'],
            );
        });

        $response = $this->getJson('/api/v1/_test/extra');

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'status',
            'title',
            'detail',
            'data',
            'location',
        ]);

        expect($response->json('location'))->toBe('/api/v1/resources/1');
    });
});

describe('ProblemResponse (RFC 9457)', function () {
    it('renders problem details response with correct headers', function () {
        $response = new ProblemResponse(
            title: 'Not Found',
            status: 404,
            detail: 'The requested resource was not found.',
            typeKey: 'about:blank',
            instance: '/api/v1/users/missing',
        );

        expect($response->headers->get('Content-Type'))->toBe('application/problem+json');

        /** @var JsonResponse $response */
        $payload = $response->getData(true);

        expect($payload)
            ->toHaveKey('type')
            ->toHaveKey('title')
            ->toHaveKey('status')
            ->toHaveKey('detail')
            ->toHaveKey('instance')
            ->and($payload['type'])->toBe('about:blank')
            ->and($payload['title'])->toBe('Not Found')
            ->and($payload['status'])->toBe(404)
            ->and($payload['detail'])->toBe('The requested resource was not found.')
            ->and($payload['instance'])->toBe('/api/v1/users/missing');
    });

    it('returns about:blank when typeKey is about:blank', function () {
        $response = new ProblemResponse(
            title: 'No Content',
            status: 204,
            typeKey: 'about:blank',
        );

        /** @var JsonResponse $response */
        $payload = $response->getData(true);

        expect($payload['type'])->toBe('about:blank');
    });

    it('resolves the type URI from config', function () {
        config(['errors.problem_type_url' => 'https://docs.example.com/errors']);

        $response = new ProblemResponse(
            title: 'Validation Error',
            status: 422,
            detail: 'The given data was invalid.',
            typeKey: 'validation',
            errors: ['email' => ['The email field is required.']],
            instance: 'https://api.example.com/auth/register',
        );

        /** @var JsonResponse $response */
        $payload = $response->getData(true);

        expect($payload['type'])->toBe('https://docs.example.com/errors/problems/validation-failed')
            ->and($payload['status'])->toBe(422)
            ->and($payload['errors'])->toBe(['email' => ['The email field is required.']])
            ->and($payload['instance'])->toBe('https://api.example.com/auth/register');
    });

    it('includes error details for validation problem', function () {
        $response = new ProblemResponse(
            title: 'Validation Error',
            status: 422,
            detail: 'The given data was invalid.',
            typeKey: 'validation',
            errors: ['email' => ['The email has already been taken.']],
        );

        /** @var JsonResponse $response */
        $payload = $response->getData(true);

        expect($payload)
            ->toHaveKey('errors')
            ->and($payload['errors']['email'][0])->toBe('The email has already been taken.');
    });

    it('uses about:blank when typeKey is about:blank despite config', function () {
        config(['errors.problem_type_url' => 'https://docs.example.com/errors']);

        $response = new ProblemResponse(
            title: 'Custom Error',
            status: 400,
            typeKey: 'about:blank',
        );

        /** @var JsonResponse $response */
        $payload = $response->getData(true);

        expect($payload['type'])->toBe('about:blank');
    });
});

describe('Exception Handler', function () {
    it('returns RFC 9457 compliant payload for validation errors', function () {
        $response = $this->postJson('/api/v1/auth/register', []);

        $response->assertStatus(422);
        $response->assertHeader('Content-Type', 'application/problem+json');
        $response->assertJsonStructure([
            'type',
            'title',
            'status',
            'detail',
            'errors',
        ]);
        $response->assertJsonPath('status', 422);
        $response->assertJsonPath('title', 'Validation Failed');
    });

    it('returns RFC 9457 compliant payload for 404 routes with clean instance URL', function () {
        $response = $this->getJson('/api/v1/non-existent-resource');

        $response->assertStatus(404);
        $response->assertHeader('Content-Type', 'application/problem+json');
        $response->assertJsonStructure([
            'type',
            'title',
            'status',
            'detail',
            'instance',
        ]);

        $payload = $response->json();

        expect($payload['instance'])->toBe(url('/api/v1/non-existent-resource'));
        expect($payload['instance'])->not->toContain('?');
    });

    it('reflects config changes in the type field dynamically', function () {
        config(['errors.problem_type_url' => 'https://test.com/docs']);

        $response = $this->postJson('/api/v1/auth/register', []);

        $response->assertStatus(422);
        $response->assertJsonPath('type', 'https://test.com/docs/problems/validation-failed');
    });
});
