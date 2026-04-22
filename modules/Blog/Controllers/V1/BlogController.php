<?php

declare(strict_types=1);

namespace Modules\Blog\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Blog\DTOs\BlogDTO;
use Modules\Blog\Models\Blog;
use Modules\Blog\Requests\BlogRequest;
use Modules\Blog\Resources\BlogResource;

class BlogController extends Controller
{
    /**
     * Display a paginated listing of the blogs.
     */
    public function index(): JsonResponse
    {
        $blogs = Blog::with('user')->latest()->paginate();

        return $this->paginateResponse($blogs, BlogResource::class, 'Blogs retrieved successfully');
    }

    /**
     * Store a newly created blog in storage.
     */
    public function store(BlogRequest $request): JsonResponse
    {
        $dto = BlogDTO::fromRequest($request);

        $blog = Blog::create([
            'title' => $dto->title,
            'content' => $dto->content,
            'user_id' => $dto->user_id,
        ]);

        return $this->successResponse(new BlogResource($blog->load('user')), 'Blog created successfully', 201);
    }

    /**
     * Display the specified blog.
     */
    public function show(Blog $blog): JsonResponse
    {
        return $this->successResponse(new BlogResource($blog->load('user')), 'Blog retrieved successfully');
    }
}
