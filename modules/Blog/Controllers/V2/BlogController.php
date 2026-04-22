<?php

declare(strict_types=1);

namespace Modules\Blog\Controllers\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Blog\Repositories\BlogRepository;

class BlogController extends Controller
{
    public function __construct(
        protected BlogRepository $repository
    ) {}

    /**
     * In V2, we return a simpler list or a different structure.
     */
    public function index(): JsonResponse
    {
        $blogs = $this->repository->getLatestTitles();

        return $this->successResponse([
            'items' => $blogs,
            'v2' => true,
            'message' => 'This is a simplified V2 response',
        ]);
    }
}
