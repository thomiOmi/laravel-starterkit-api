<?php

declare(strict_types=1);

use App\Http\Responses\SuccessResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

covers(SuccessResponse::class);

describe('basic response structure', function () {
    it('returns basic data without title or detail', function () {
        $response = (new SuccessResponse(data: ['id' => 1]))->toResponse(new Request);

        expect($response->getStatusCode())->toBe(200);
        expect($response->getData(true))->toMatchArray([
            'status' => 200,
            'data' => ['id' => 1],
        ]);
    });

    it('includes title and detail when provided', function () {
        $response = (new SuccessResponse(
            data: ['id' => 1],
            title: 'Created',
            detail: 'Resource created successfully',
        ))->toResponse(new Request);

        $data = $response->getData(true);

        expect($data['title'])->toBe('Created');
        expect($data['detail'])->toBe('Resource created successfully');
    });

    it('returns no content for 204 status', function () {
        $response = (new SuccessResponse(status: 204))->toResponse(new Request);

        expect($response->getStatusCode())->toBe(204);
        expect($response->getContent())->toBe('');
    });

    it('returns no content for 205 status', function () {
        $response = (new SuccessResponse(status: 205))->toResponse(new Request);

        expect($response->getStatusCode())->toBe(205);
        expect($response->getContent())->toBe('');
    });
});

describe('pagination meta', function () {
    it('includes pagination meta for LengthAwarePaginator', function () {
        $items = [['id' => 1], ['id' => 2]];
        $paginator = new LengthAwarePaginator($items, 10, 2, 1);

        $response = (new SuccessResponse(data: $paginator))->toResponse(new Request);
        $data = $response->getData(true);

        expect($data['data'])->toBe($items);
        expect($data['meta'])->toMatchArray([
            'current_page' => 1,
            'last_page' => 5,
            'per_page' => 2,
            'total' => 10,
            'has_more' => true,
        ]);
    });

    it('includes pagination meta for simple Paginator', function () {
        $items = [['id' => 1], ['id' => 2]];
        $paginator = new Paginator($items, 2, 1);

        $response = (new SuccessResponse(data: $paginator))->toResponse(new Request);
        $data = $response->getData(true);

        expect($data['data'])->toBe($items);
        expect($data['meta'])->toMatchArray([
            'current_page' => 1,
            'per_page' => 2,
            'has_more' => false,
        ]);
        expect($data['meta'])->not->toHaveKey('last_page');
        expect($data['meta'])->not->toHaveKey('total');
    });

    it('includes pagination meta for CursorPaginator', function () {
        $items = [['id' => 1]];
        $paginator = new CursorPaginator($items, 2, cursor: null);

        $response = (new SuccessResponse(data: $paginator))->toResponse(new Request);
        $data = $response->getData(true);

        expect($data['data'])->toBe($items);
        expect($data['meta'])->toMatchArray([
            'per_page' => 2,
            'has_more' => false,
        ]);
        expect($data['meta'])->toHaveKey('next_cursor');
        expect($data['meta'])->toHaveKey('prev_cursor');
    });

    it('paginates with ResourceCollection containing paginator', function () {
        $items = [['id' => 1]];
        $paginator = new LengthAwarePaginator($items, 5, 1, 1);

        $resource = new class($paginator) extends ResourceCollection
        {
            public function toArray($request): array
            {
                return $this->collection->toArray();
            }
        };

        $response = (new SuccessResponse(data: $resource))->toResponse(new Request);
        $data = $response->getData(true);

        expect($data['meta'])->toHaveKey('current_page');
        expect($data['meta']['total'])->toBe(5);
    });
});

describe('extra features', function () {
    it('resolves data from ResourceCollection', function () {
        $resource = new class(collect([['id' => 1]])) extends ResourceCollection
        {
            public function toArray($request): array
            {
                return $this->collection->toArray();
            }
        };

        $response = (new SuccessResponse(data: $resource))->toResponse(new Request);
        $data = $response->getData(true);

        expect($data['data'])->toBe([['id' => 1]]);
    });

    it('filters protected keys from extra data', function () {
        $extra = ['custom_key' => 'value', 'status' => 'should_not_override'];

        $response = (new SuccessResponse(
            data: ['id' => 1],
            extra: $extra,
        ))->toResponse(new Request);

        $data = $response->getData(true);

        expect($data['custom_key'])->toBe('value');
        expect($data['status'])->toBe(200);
    });

    it('merges custom headers', function () {
        $response = (new SuccessResponse(
            data: ['id' => 1],
            headers: ['X-Custom' => 'test-value'],
        ))->toResponse(new Request);

        expect($response->headers->get('X-Custom'))->toBe('test-value');
    });
});

describe('snapshots', function () {
    it('matches snapshot for basic response', function () {
        $response = (new SuccessResponse(data: ['id' => 1]))->toResponse(new Request);

        expect($response->getContent())->toMatchSnapshot();
    });

    it('matches snapshot for paginated response', function () {
        $items = [['id' => 1], ['id' => 2]];
        $paginator = new LengthAwarePaginator($items, 10, 2, 1);

        $response = (new SuccessResponse(data: $paginator))->toResponse(new Request);

        expect($response->getContent())->toMatchSnapshot();
    });

    it('matches snapshot with title and detail', function () {
        $response = (new SuccessResponse(
            data: ['id' => 1],
            title: 'Created',
            detail: 'Resource created successfully',
        ))->toResponse(new Request);

        expect($response->getContent())->toMatchSnapshot();
    });
});
