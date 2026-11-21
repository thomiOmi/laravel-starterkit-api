<?php

declare(strict_types=1);

use App\Http\Requests\BulkActionRequest;
use Illuminate\Support\Facades\Validator;
use Modules\IAM\Database\Seeders\IAMSeeder;

covers(BulkActionRequest::class);

dataset('authorized bulk actions', [
    'user delete' => ['api/v1/users', 'delete'],
    'user restore' => ['api/v1/users', 'restore'],
    'role delete' => ['api/v1/roles', 'delete'],
]);

dataset('bulk actions without permission', [
    'user delete' => ['api/v1/users', 'delete'],
    'user restore' => ['api/v1/users', 'restore'],
]);

dataset('mismatched bulk actions', [
    'user delete with restore action' => ['api/v1/users', 'delete', 'restore'],
    'role delete with restore action' => ['api/v1/roles', 'delete', 'restore'],
]);

describe('bulk authorization', function (): void {
    it('authorizes bulk action when the user has the matching permission', function (string $prefix, string $action): void {
        $this->seed(IAMSeeder::class);
        loginAsAdmin();

        $response = $this->postJson("/{$prefix}/bulk/{$action}", [
            'ids' => ['01ARZ3NDEKTSV4RRFFQ69G5FAV'],
            'action' => $action,
        ]);

        assertSuccessResponse($response, 200);
    })->with('authorized bulk actions')->group('module:iam');

    it('denies bulk action when the user lacks the permission', function (string $prefix, string $action): void {
        $this->seed(IAMSeeder::class);
        loginAsUser();

        $response = $this->postJson("/{$prefix}/bulk/{$action}", [
            'ids' => ['01ARZ3NDEKTSV4RRFFQ69G5FAV'],
            'action' => $action,
        ]);

        assertProblemResponse($response, 403);
    })->with('bulk actions without permission')->group('module:iam');

    it('denies bulk action when the action does not match the route action', function (string $prefix, string $routeAction, string $bodyAction): void {
        $this->seed(IAMSeeder::class);
        loginAsAdmin();

        $response = $this->postJson("/{$prefix}/bulk/{$routeAction}", [
            'ids' => ['01ARZ3NDEKTSV4RRFFQ69G5FAV'],
            'action' => $bodyAction,
        ]);

        assertProblemResponse($response, 403);
    })->with('mismatched bulk actions')->group('module:iam');
});

describe('bulk action validation', function (): void {
    it('rejects payloads that violate the ids rules', function (array $payload, string $field, string $message): void {
        $this->seed(IAMSeeder::class);
        loginAsAdmin();

        $response = $this->postJson('/api/v1/users/bulk/delete', $payload);

        assertProblemResponse($response, 422);
        $response->assertJsonValidationErrors([$field => $message]);
    })->with([
        'missing ids' => [[], 'ids', 'The ids field is required.'],
        'ids is not an array' => [['ids' => 'not-an-array'], 'ids', 'The ids field must be an array.'],
        'ids is empty' => [['ids' => []], 'ids', 'The ids field is required.'],
        'ids contains null' => [['ids' => [null]], 'ids.0', 'The ids.0 field is required.'],
        'ids contains a non-ulid' => [['ids' => ['invalid-id']], 'ids.0', 'The ids.0 field must be a valid ULID.'],
        'ids exceeds 50 entries' => [
            ['ids' => array_fill(0, 51, '01ARZ3NDEKTSV4RRFFQ69G5FAV')],
            'ids',
            'The ids field must not have more than 50 items.',
        ],
    ])->group('module:iam');

    it('rejects an unsupported action value', function (): void {
        // Over HTTP the form request's authorize() rejects a mismatched action
        // before validation runs, so the "in" rule is only reachable directly.
        $validator = Validator::make(
            ['ids' => ['01ARZ3NDEKTSV4RRFFQ69G5FAV'], 'action' => 'destroy'],
            (new BulkActionRequest)->rules(),
        );

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->first('action'))->toBe('The selected action is invalid.');
    })->group('module:iam');
});
