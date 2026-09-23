<?php

declare(strict_types=1);

use App\Http\Requests\PaginationRequest;

covers(PaginationRequest::class);

beforeEach(function (): void {
    config()->set('pagination.default_per_page', 10);
    config()->set('pagination.min_per_page', 1);
    config()->set('pagination.max_per_page', 100);
});

describe('PaginationRequest', function (): void {

    describe('per page', function (): void {
        it('uses default per page when no page size given', function (): void {
            expect(PaginationRequest::create('/')->getPerPage())->toBe(10);
        });

        it('uses custom per page from request', function (): void {
            $request = PaginationRequest::create('/', 'GET', ['page' => ['size' => 25]]);

            expect($request->getPerPage())->toBe(25);
        });

        it('uses provided default when request has no value', function (): void {
            expect(PaginationRequest::create('/')->getPerPage(default: 50))->toBe(50);
        });
    });

    describe('page number', function (): void {
        it('returns page number 1 when not specified', function (): void {
            expect(PaginationRequest::create('/')->getPage())->toBe(1);
        });

        it('returns custom page number from request', function (): void {
            $request = PaginationRequest::create('/', 'GET', ['page' => ['number' => 3]]);

            expect($request->getPage())->toBe(3);
        });
    });

    describe('rules', function (): void {
        it('contain between validation for page size', function (): void {
            expect((new PaginationRequest)->rules()['page.size'][2])->toBe('between:1,100');
        });

        it('reflect config min per page', function (): void {
            config()->set('pagination.min_per_page', 5);
            config()->set('pagination.max_per_page', 50);

            expect((new PaginationRequest)->rules()['page.size'][2])->toBe('between:5,50');
        });

        it('reflect config max per page', function (): void {
            config()->set('pagination.min_per_page', 5);
            config()->set('pagination.max_per_page', 200);

            expect((new PaginationRequest)->rules()['page.size'][2])->toBe('between:5,200');
        });
    });

    it('authorizes all requests', function (): void {
        expect(PaginationRequest::create('/')->authorize())->toBeTrue();
    });

});
