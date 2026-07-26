<?php

declare(strict_types=1);

use App\Http\Requests\PaginationRequest;

beforeEach(function () {
    config()->set('pagination.default_per_page', 10);
    config()->set('pagination.min_per_page', 1);
    config()->set('pagination.max_per_page', 100);
});

describe('PaginationRequest', function () {

    describe('per page', function () {
        it('uses default per page when no page size given', function () {
            expect(PaginationRequest::create('/')->getPerPage())->toBe(10);
        });

        it('uses custom per page from request', function () {
            $request = PaginationRequest::create('/', 'GET', ['page' => ['size' => 25]]);

            expect($request->getPerPage())->toBe(25);
        });

        it('uses provided default when request has no value', function () {
            expect(PaginationRequest::create('/')->getPerPage(default: 50))->toBe(50);
        });
    });

    describe('page number', function () {
        it('returns page number 1 when not specified', function () {
            expect(PaginationRequest::create('/')->getPage())->toBe(1);
        });

        it('returns custom page number from request', function () {
            $request = PaginationRequest::create('/', 'GET', ['page' => ['number' => 3]]);

            expect($request->getPage())->toBe(3);
        });
    });

    describe('rules', function () {
        it('contain between validation for page size', function () {
            expect((new PaginationRequest)->rules()['page.size'][2])->toBe('between:1,100');
        });

        it('reflect config min per page', function () {
            config()->set('pagination.min_per_page', 5);
            config()->set('pagination.max_per_page', 50);

            expect((new PaginationRequest)->rules()['page.size'][2])->toBe('between:5,50');
        });

        it('reflect config max per page', function () {
            config()->set('pagination.min_per_page', 5);
            config()->set('pagination.max_per_page', 200);

            expect((new PaginationRequest)->rules()['page.size'][2])->toBe('between:5,200');
        });
    });

    it('authorizes all requests', function () {
        expect(PaginationRequest::create('/')->authorize())->toBeTrue();
    });

});
