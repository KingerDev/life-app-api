<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNoteRequest;
use App\Http\Requests\UpdateNoteRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notes = $request->user()
            ->notes()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'data' => $notes->items(),
            'meta' => [
                'current_page' => $notes->currentPage(),
                'last_page'    => $notes->lastPage(),
                'per_page'     => $notes->perPage(),
                'total'        => $notes->total(),
            ],
        ]);
    }

    public function store(StoreNoteRequest $request): JsonResponse
    {
        $note = $request->user()->notes()->create([
            'title'   => $request->input('title', ''),
            'content' => $request->input('content', ''),
            'tags'    => $request->input('tags', []),
        ]);

        return response()->json(['data' => $note], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $note = $request->user()->notes()->findOrFail($id);

        return response()->json(['data' => $note]);
    }

    public function update(UpdateNoteRequest $request, string $id): JsonResponse
    {
        $note = $request->user()->notes()->findOrFail($id);

        $note->update($request->validated());

        return response()->json(['data' => $note->fresh()]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $note = $request->user()->notes()->findOrFail($id);

        $note->delete();

        return response()->json(['message' => 'Note deleted successfully']);
    }
}
