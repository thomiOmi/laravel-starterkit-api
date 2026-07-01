<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure;

use App\Http\Responses\ProblemResponse;
use App\Http\Responses\SuccessResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Route;

test('ProblemResponse follows RFC 9457 via HTTP lifecycle', function () {
    Route::get('/test-problem', fn () => new ProblemResponse(
        title: 'Validation Failed',
        status: 422,
        detail: 'The email field is required.',
        typeKey: 'validation-error',
        errors: ['email' => ['Required']]
    ));

    $response = $this->get('/test-problem');
    expect($response)->toBeProblemResponse(status: 422);
    expect($response->json('detail'))->toBe('The email field is required.');
    expect($response->json('errors.email.0'))->toBe('Required');
});

test('SuccessResponse follows standardized schema via HTTP lifecycle', function () {
    Route::get('/test-success', fn () => new SuccessResponse(
        title: 'OK',
        detail: 'Operation successful.',
        data: ['id' => '123']
    ));

    $response = $this->get('/test-success');
    expect($response)->toBeSuccessResponse(status: 200, title: 'OK');
    expect($response->json('detail'))->toBe('Operation successful.');
    expect($response->json('data.id'))->toBe('123');
});

test('SuccessResponse handles pagination via HTTP lifecycle', function () {
    Route::get('/test-pagination', function () {
        $items = collect([['id' => 1], ['id' => 2]]);
        $paginator = new LengthAwarePaginator($items, 10, 2, 1);
        $paginator->setPath('http://localhost/test-pagination');

        return new SuccessResponse(
            title: 'OK',
            detail: 'List retrieved.',
            data: $paginator
        );
    });

    expect($this->get('/test-pagination'))
        ->toBeSuccessResponse()
        ->toBePaginated();
});
