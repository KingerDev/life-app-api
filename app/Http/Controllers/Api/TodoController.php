<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTodoRequest;
use App\Http\Requests\UpdateTodoRequest;
use App\Http\Requests\StoreTodoItemRequest;
use App\Http\Requests\UpdateTodoItemRequest;
use App\Models\Todo;
use App\Models\TodoItem;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TodoController extends Controller
{
    private const PRIORITY_COLORS = [
        'none'   => '#6b7280',
        'low'    => '#3b82f6',
        'medium' => '#f59e0b',
        'high'   => '#ef4444',
    ];

    /**
     * List active (non-archived) todos for authenticated user
     * GET /api/todos?list_id=xxx
     */
    public function index(Request $request): JsonResponse
    {
        $query = $request->user()
            ->todos()
            ->where('is_archived', false)
            ->orderBy('is_completed', 'asc')
            ->orderByRaw("CASE priority WHEN 'high' THEN 0 WHEN 'medium' THEN 1 WHEN 'low' THEN 2 ELSE 3 END")
            ->orderBy('due_date', 'asc')
            ->orderBy('sort_order', 'asc')
            ->orderBy('created_at', 'desc');

        if ($request->has('list_id')) {
            $query->where('list_id', $request->input('list_id'));
        }

        $todos = $query->paginate(30);

        return response()->json([
            'data' => $todos->items(),
            'meta' => [
                'current_page' => $todos->currentPage(),
                'last_page'    => $todos->lastPage(),
                'per_page'     => $todos->perPage(),
                'total'        => $todos->total(),
            ],
        ]);
    }

    /**
     * Create new todo
     * POST /api/todos
     */
    public function store(StoreTodoRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $todo = $request->user()->todos()->create([
            'list_id'     => $validated['list_id'] ?? null,
            'title'       => $validated['title'],
            'description' => $validated['description'] ?? null,
            'due_date'    => $validated['due_date'] ?? null,
            'priority'    => $validated['priority'] ?? 'none',
            'aspect_id'   => $validated['aspect_id'] ?? null,
            'is_completed'=> false,
            'is_archived' => false,
        ]);

        return response()->json([
            'data' => $todo,
        ], 201);
    }

    /**
     * Get single todo with items
     * GET /api/todos/{todo}
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $todo = $request->user()
            ->todos()
            ->with('items')
            ->findOrFail($id);

        return response()->json([
            'data' => $todo,
        ]);
    }

    /**
     * Update todo
     * PATCH /api/todos/{todo}
     */
    public function update(UpdateTodoRequest $request, string $id): JsonResponse
    {
        $todo = $request->user()
            ->todos()
            ->findOrFail($id);

        $validated = $request->validated();

        // Auto-set completed_at when marking complete
        if (isset($validated['is_completed'])) {
            if ($validated['is_completed'] && !$todo->is_completed) {
                $validated['completed_at'] = now();
            } elseif (!$validated['is_completed'] && $todo->is_completed) {
                $validated['completed_at'] = null;
            }
        }

        $todo->update($validated);

        return response()->json([
            'data' => $todo->fresh(),
        ]);
    }

    /**
     * Delete todo (cascades to items)
     * DELETE /api/todos/{todo}
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $todo = $request->user()
            ->todos()
            ->findOrFail($id);

        $todo->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Get overdue + today's todos
     * GET /api/todos/today
     */
    public function today(Request $request): JsonResponse
    {
        $today = Carbon::today()->toDateString();

        $todos = $request->user()
            ->todos()
            ->where('is_archived', false)
            ->where('is_completed', false)
            ->where(function ($q) use ($today) {
                $q->whereDate('due_date', '<=', $today)
                  ->orWhereNull('due_date');
            })
            ->orderByRaw("CASE priority WHEN 'high' THEN 0 WHEN 'medium' THEN 1 WHEN 'low' THEN 2 ELSE 3 END")
            ->orderBy('due_date', 'asc')
            ->orderBy('sort_order', 'asc')
            ->get();

        $overdue = $todos->filter(fn($t) => $t->due_date && $t->due_date->lt(Carbon::today()))->values();
        $dueToday = $todos->filter(fn($t) => !$t->due_date || $t->due_date->equalTo(Carbon::today()))->values();

        // Also include todos completed today
        $completedToday = $request->user()
            ->todos()
            ->where('is_archived', false)
            ->where('is_completed', true)
            ->whereDate('completed_at', $today)
            ->orderBy('completed_at', 'desc')
            ->get();

        return response()->json([
            'data' => [
                'overdue'        => $overdue,
                'due_today'      => $dueToday,
                'completed_today'=> $completedToday,
            ],
        ]);
    }

    /**
     * Dashboard summary
     * GET /api/todos/summary
     */
    public function summary(Request $request): JsonResponse
    {
        $today = Carbon::today()->toDateString();
        $user = $request->user();

        $totalActive = $user->todos()->where('is_archived', false)->where('is_completed', false)->count();
        $completedToday = $user->todos()->where('is_archived', false)->where('is_completed', true)->whereDate('completed_at', $today)->count();
        $overdueCount = $user->todos()->where('is_archived', false)->where('is_completed', false)->whereDate('due_date', '<', $today)->count();
        $listsCount = $user->todoLists()->count();

        // Top 3 upcoming todos (due today or overdue, highest priority first)
        $topTodos = $user->todos()
            ->where('is_archived', false)
            ->where('is_completed', false)
            ->where(function ($q) use ($today) {
                $q->whereDate('due_date', '<=', $today)->orWhereNull('due_date');
            })
            ->orderByRaw("CASE priority WHEN 'high' THEN 0 WHEN 'medium' THEN 1 WHEN 'low' THEN 2 ELSE 3 END")
            ->orderBy('due_date', 'asc')
            ->limit(3)
            ->get();

        return response()->json([
            'data' => [
                'total_active'    => $totalActive,
                'completed_today' => $completedToday,
                'overdue_count'   => $overdueCount,
                'lists_count'     => $listsCount,
                'top_todos'       => $topTodos,
            ],
        ]);
    }

    /**
     * Mark todo as complete
     * POST /api/todos/{todo}/complete
     */
    public function complete(Request $request, string $id): JsonResponse
    {
        $todo = $request->user()->todos()->findOrFail($id);
        $todo->update([
            'is_completed' => true,
            'completed_at' => now(),
        ]);

        return response()->json(['data' => $todo->fresh()]);
    }

    /**
     * Mark todo as incomplete
     * POST /api/todos/{todo}/incomplete
     */
    public function incomplete(Request $request, string $id): JsonResponse
    {
        $todo = $request->user()->todos()->findOrFail($id);
        $todo->update([
            'is_completed' => false,
            'completed_at' => null,
        ]);

        return response()->json(['data' => $todo->fresh()]);
    }

    /**
     * Get subtasks for a todo
     * GET /api/todos/{todo}/items
     */
    public function getItems(Request $request, string $id): JsonResponse
    {
        $todo = $request->user()->todos()->findOrFail($id);

        return response()->json([
            'data' => $todo->items,
        ]);
    }

    /**
     * Create subtask
     * POST /api/todos/{todo}/items
     */
    public function storeItem(StoreTodoItemRequest $request, string $id): JsonResponse
    {
        $todo = $request->user()->todos()->findOrFail($id);

        $maxOrder = $todo->items()->max('sort_order') ?? -1;
        $item = $todo->items()->create([
            'title'      => $request->validated()['title'],
            'sort_order' => $maxOrder + 1,
        ]);

        return response()->json(['data' => $item], 201);
    }

    /**
     * Update subtask
     * PATCH /api/todos/{todo}/items/{item}
     */
    public function updateItem(UpdateTodoItemRequest $request, string $todoId, string $itemId): JsonResponse
    {
        $todo = $request->user()->todos()->findOrFail($todoId);
        $item = $todo->items()->findOrFail($itemId);

        $item->update($request->validated());

        return response()->json(['data' => $item->fresh()]);
    }

    /**
     * Delete subtask
     * DELETE /api/todos/{todo}/items/{item}
     */
    public function destroyItem(Request $request, string $todoId, string $itemId): JsonResponse
    {
        $todo = $request->user()->todos()->findOrFail($todoId);
        $item = $todo->items()->findOrFail($itemId);

        $item->delete();

        return response()->json(['success' => true]);
    }
}
