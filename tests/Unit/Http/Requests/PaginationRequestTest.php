<?php

declare(strict_types=1);

use App\Http\Requests\PaginationRequest;

beforeEach(function () {
    config()->set('pagination.default_per_page', 10);
    config()->set('pagination.min_per_page', 1);
    config()->set('pagination.max_per_page', 100);
});

test('authorizes all requests', function () {
    $request = PaginationRequest::create('/');

    expect($request->authorize())->toBeTrue();
});

test('uses default per page when no page size given', function () {
    $request = PaginationRequest::create('/');

    expect($request->getPerPage())->toBe(10);
});

test('uses custom per page from request', function () {
    $request = PaginationRequest::create('/', 'GET', ['page' => ['size' => 25]]);

    expect($request->getPerPage())->toBe(25);
});

test('uses provided default when request has no value', function () {
    $request = PaginationRequest::create('/');

    expect($request->getPerPage(default: 50))->toBe(50);
});

test('returns page number 1 when not specified', function () {
    $request = PaginationRequest::create('/');

    expect($request->getPage())->toBe(1);
});

test('returns custom page number from request', function () {
    $request = PaginationRequest::create('/', 'GET', ['page' => ['number' => 3]]);

    expect($request->getPage())->toBe(3);
});

test('rules contain between validation for page size', function () {
    $request = new PaginationRequest;
    $rules = $request->rules();

    expect($rules['page.size'][2])->toBe('between:1,100');
});

test('rules reflect config min per page', function () {
    config()->set('pagination.min_per_page', 5);
    config()->set('pagination.max_per_page', 50);

    $request = new PaginationRequest;
    $rules = $request->rules();

    expect($rules['page.size'][2])->toBe('between:5,50');
});

test('rules reflect config max per page', function () {
    config()->set('pagination.min_per_page', 5);
    config()->set('pagination.max_per_page', 200);

    $request = new PaginationRequest;
    $rules = $request->rules();

    expect($rules['page.size'][2])->toBe('between:5,200');
});
