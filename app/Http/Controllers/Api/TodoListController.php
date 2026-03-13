<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTodoListRequest;
use App\Http\Requests\UpdateTodoListRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TodoListController extends Controller
{
    /**
     * List all lists for authenticated user
     * GET /api/todo-lists
     */
    public function index(Request $request): JsonResponse
    {
        $lists = $request->user()
            ->todoLists()
            ->withCount('todos')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'data' => $lists,
        ]);
    }

    /**
     * Create new list
     * POST /api/todo-lists
     */
    public function store(StoreTodoListRequest $request): JsonResponse
    {
        $list = $request->user()->todoLists()->create($request->validated());

        return response()->json([
            'data' => $list,
        ], 201);
    }

    /**
     * Get single list
     * GET /api/todo-lists/{todoList}
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $list = $request->user()
            ->todoLists()
            ->findOrFail($id);

        return response()->json([
            'data' => $list,
        ]);
    }

    /**
     * Update list
     * PATCH /api/todo-lists/{todoList}
     */
    public function update(UpdateTodoListRequest $request, string $id): JsonResponse
    {
        $list = $request->user()
            ->todoLists()
            ->findOrFail($id);

        $list->update($request->validated());

        return response()->json([
            'data' => $list->fresh(),
        ]);
    }

    /**
     * Delete list (sets list_id to null on todos)
     * DELETE /api/todo-lists/{todoList}
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $list = $request->user()
            ->todoLists()
            ->findOrFail($id);

        $list->delete();

        return response()->json([
            'success' => true,
        ]);
    }
}
