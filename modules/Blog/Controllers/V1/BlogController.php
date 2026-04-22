<?php

declare(strict_types=1);

namespace Modules\Blog\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Blog\Actions\CreateBlogAction;
use Modules\Blog\DTOs\BlogDTO;
use Modules\Blog\Repositories\BlogRepository;
use Modules\Blog\Requests\BlogRequest;
use Modules\Blog\Resources\BlogResource;

class BlogController extends Controller
{
    public function __construct(
        protected BlogRepository $repository
    ) {}

    /**
     * Display a paginated listing of the blogs.
     */
    public function index(): JsonResponse
    {
        $blogs = $this->repository->paginate(relations: ['user']);

        return $this->paginateResponse($blogs, BlogResource::class, 'Blogs retrieved successfully');
    }

    /**
     * Store a newly created blog in storage.
     */
    public function store(BlogRequest $request, CreateBlogAction $action): JsonResponse
    {
        $dto = BlogDTO::fromRequest($request);
        $blog = $action->execute($dto);

        return $this->successResponse(new BlogResource($blog->load('user')), 'Blog created successfully', 201);
    }

    /**
     * Display the specified blog.
     */
    public function show(string|int $id): JsonResponse
    {
        $blog = $this->repository->findById($id, relations: ['user']);

        return $this->successResponse(new BlogResource($blog), 'Blog retrieved successfully');
    }
}
