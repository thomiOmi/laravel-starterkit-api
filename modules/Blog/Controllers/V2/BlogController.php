<?php

declare(strict_types=1);

namespace Modules\Blog\Controllers\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Blog\Models\Blog;

class BlogController extends Controller
{
    /**
     * In V2, we return a simpler list or a different structure.
     */
    public function index(): JsonResponse
    {
        $blogs = Blog::latest()->get(['id', 'title']);

        return $this->successResponse([
            'items' => $blogs,
            'v2' => true,
            'message' => 'This is a simplified V2 response',
        ]);
    }
}
