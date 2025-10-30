<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Models\Project\ItemChecklist;
use App\Models\Project\ProjectItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ItemChecklistController extends Controller
{
    public function store(Request $request, ProjectItem $projectItem)
    {
        $request->validate([
            'task_description' => 'required|string|max:255'
        ]);

        // Check access
        if (!$projectItem->project->category->isAccessibleBy(Auth::id())) {
            return response()->json([
                'error' => 'Anda tidak memiliki akses ke item ini.'
            ], 403);
        }

        $checklist = $projectItem->checklists()->create([
            'task_description' => $request->task_description,
            'is_completed' => false
        ]);

        return response()->json([
            'success' => true,
            'checklist' => $checklist,
            'message' => 'Checklist berhasil ditambahkan! 💫'
        ]);
    }

    public function update(Request $request, ProjectItem $projectItem, ItemChecklist $checklist)
    {
        $request->validate([
            'task_description' => 'required|string|max:255',
        ]);

        // Check access
        if (!$projectItem->project->category->isAccessibleBy(Auth::id())) {
            return response()->json([
                'error' => 'Anda tidak memiliki akses ke checklist ini.'
            ], 403);
        }

        // Verify checklist belongs to project item
        if ($checklist->project_item_id !== $projectItem->id) {
            return response()->json([
                'error' => 'Checklist tidak ditemukan untuk item ini.'
            ], 404);
        }

        $checklist->update([
            'task_description' => $request->task_description
        ]);

        return response()->json([
            'success' => true,
            'checklist' => $checklist->fresh(),
            'message' => 'Checklist berhasil diperbarui! ✨'
        ]);
    }

    public function toggleComplete(Request $request, ProjectItem $projectItem, ItemChecklist $checklist)
    {
        // Check access
        if (!$projectItem->project->category->isAccessibleBy(Auth::id())) {
            return response()->json([
                'error' => 'Anda tidak memiliki akses ke checklist ini.'
            ], 403);
        }

        // Verify checklist belongs to project item
        if ($checklist->project_item_id !== $projectItem->id) {
            return response()->json([
                'error' => 'Checklist tidak ditemukan untuk item ini.'
            ], 404);
        }

        if ($checklist->is_completed) {
            $checklist->update([
                'is_completed' => false,
                'completed_at' => null
            ]);
            $message = 'Checklist ditandai belum selesai 📝';
        } else {
            $checklist->update([
                'is_completed' => true,
                'completed_at' => now()
            ]);
            $message = 'Checklist selesai! 🎉';
        }

        return response()->json([
            'success' => true,
            'checklist' => $checklist->fresh(),
            'message' => $message
        ]);
    }

    public function destroy(ProjectItem $projectItem, ItemChecklist $checklist)
    {
        // Check access
        if (!$projectItem->project->category->isAccessibleBy(Auth::id())) {
            return response()->json([
                'error' => 'Anda tidak memiliki akses ke checklist ini.'
            ], 403);
        }

        // Verify checklist belongs to project item
        if ($checklist->project_item_id !== $projectItem->id) {
            return response()->json([
                'error' => 'Checklist tidak ditemukan untuk item ini.'
            ], 404);
        }

        $checklist->delete();

        return response()->json([
            'success' => true,
            'message' => 'Checklist berhasil dihapus 🗑️'
        ]);
    }
}