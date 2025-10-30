<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Models\Project\Project;
use App\Models\MasterData\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Ambil proyek yang bisa diakses user melalui kategori
        $projects = Project::with(['category', 'category.user'])
            ->whereHas('category', function ($query) {
                $query->accessibleBy(Auth::id());
            })
            ->orderBy('status')
            ->orderBy('target_completion_date')
            ->get()
            ->map(function ($project) {
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'category' => $project->category->name ?? 'Uncategorized',
                    'category_id' => $project->category_id,
                    'target_total_amount' => $project->target_total_amount,
                    'formatted_target_amount' => number_format($project->target_total_amount, 0, ',', '.'),
                    'target_completion_date' => $project->target_completion_date?->format('Y-m-d'),
                    'formatted_completion_date' => $project->target_completion_date?->format('d M Y'),
                    'status' => $project->status,
                    'status_badge' => $this->getStatusBadge($project->status),
                    'category_user_id' => $project->category->user_id,
                    'category_user' => $project->category->user,
                    'created_at' => $project->created_at->format('d M Y'),
                    'items_count' => $project->items()->count(),
                    'progress_percentage' => $this->calculateProgress($project),
                    'is_owner' => $project->category->user_id === Auth::id(),
                ];
            });

        if (request()->wantsJson()) {
            return response()->json([
                'projects' => $projects
            ]);
        }

        return Inertia::render('Project/Index', [
            'projects' => $projects
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'category_id' => 'required|exists:categories,id',
                'name' => 'required|string|max:255',
                'target_total_amount' => 'required|numeric|min:0',
                'target_completion_date' => 'required|date|after:today',
                'status' => 'required|in:planning,on_going,completed',
            ]);

            // Cek apakah kategori bisa diakses user dan type savings
            $category = Category::findOrFail($validated['category_id']);
            if (!$category->isAccessibleBy(Auth::id())) {
                return redirect()->back()->with([
                    'error' => 'Anda tidak memiliki akses ke kategori yang dipilih.',
                    'type' => 'error'
                ]);
            }

            if ($category->budget_type !== 'savings') {
                return redirect()->back()->with([
                    'error' => 'Hanya kategori dengan type savings yang bisa digunakan untuk proyek.',
                    'type' => 'error'
                ]);
            }

            DB::beginTransaction();

            Project::create($validated);

            DB::commit();

            return redirect()->back()->with([
                'success' => 'Proyek berhasil dibuat!',
                'type' => 'success'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with([
                'error' => 'Terjadi kesalahan saat menyimpan proyek: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Project $project)
    {
        try {
            // Cek authorization melalui kategori
            if (!$project->category->isAccessibleBy(Auth::id())) {
                return redirect()->back()->with([
                    'error' => 'Anda tidak memiliki akses untuk mengubah proyek ini.',
                    'type' => 'error'
                ]);
            }

            // Cek ownership - hanya creator kategori yang bisa update
            if ($project->category->user_id !== Auth::id()) {
                return redirect()->back()->with([
                    'error' => 'Hanya pemilik kategori yang dapat mengubah proyek.',
                    'type' => 'error'
                ]);
            }

            $validated = $request->validate([
                'category_id' => 'required|exists:categories,id',
                'name' => 'required|string|max:255',
                'target_total_amount' => 'required|numeric|min:0',
                'target_completion_date' => 'required|date',
                'status' => 'required|in:planning,on_going,completed',
            ]);

            // Cek kategori baru jika berubah
            if ($validated['category_id'] != $project->category_id) {
                $newCategory = Category::findOrFail($validated['category_id']);
                if (!$newCategory->isAccessibleBy(Auth::id()) || $newCategory->type !== 'savings') {
                    return redirect()->back()->with([
                        'error' => 'Kategori yang dipilih tidak valid atau tidak dapat diakses.',
                        'type' => 'error'
                    ]);
                }
            }

            DB::beginTransaction();

            $project->update($validated);

            DB::commit();

            return redirect()->back()->with([
                'success' => 'Proyek berhasil diperbarui!',
                'type' => 'success'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with([
                'error' => 'Terjadi kesalahan saat memperbarui proyek: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project)
    {
        try {
            // Cek authorization melalui kategori
            if (!$project->category->isAccessibleBy(Auth::id())) {
                return redirect()->back()->with([
                    'error' => 'Anda tidak memiliki akses untuk menghapus proyek ini.',
                    'type' => 'error'
                ]);
            }

            // Cek ownership - hanya creator kategori yang bisa delete
            if ($project->category->user_id !== Auth::id()) {
                return redirect()->back()->with([
                    'error' => 'Hanya pemilik kategori yang dapat menghapus proyek.',
                    'type' => 'error'
                ]);
            }

            DB::beginTransaction();

            // Hapus item proyek terlebih dahulu
            $project->items()->delete();
            $project->delete();

            DB::commit();

            return redirect()->back()->with([
                'success' => 'Proyek berhasil dihapus!',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with([
                'error' => 'Terjadi kesalahan saat menghapus proyek: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * Get accessible categories untuk dropdown - HANYA SAVINGS
     */
/**
 * Get accessible categories untuk dropdown - HANYA SAVINGS dan BELUM ADA PROJECT
 */
/**
 * Get accessible categories untuk dropdown - HANYA SAVINGS dan BELUM ADA PROJECT ATAU PROJECT SUDAH COMPLETED
 */
public function getCategories()
{
    try {
        // Ambil kategori yang memiliki project dengan status SELAIN completed
        $usedCategoryIds = Project::where('status', '!=', 'completed')
            ->pluck('category_id')
            ->toArray();

        $categories = Category::where('budget_type', 'savings')
            ->whereNotIn('id', $usedCategoryIds) // Kecualikan kategori yang memiliki project aktif (non-completed)
            ->accessibleBy(Auth::id())
            ->active()
            ->orderBy('name')
            ->get()
            ->map(function ($category) use ($usedCategoryIds) {
                $hasActiveProject = in_array($category->id, $usedCategoryIds);
                
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'type' => $category->type,
                    'budget_type' => $category->budget_type,
                    'user_id' => $category->user_id,
                    'is_owner' => $category->user_id === Auth::id(),
                    'has_active_project' => $hasActiveProject, // Untuk info di frontend
                ];
            });

        return response()->json($categories);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Failed to load categories',
            'message' => $e->getMessage()
        ], 500);
    }
}


    /**
     * Get project summary untuk dashboard
     */
    public function getProjectSummary()
    {
        try {
            $userId = Auth::id();
            
            $projects = Project::with('category')
                ->whereHas('category', function ($query) use ($userId) {
                    $query->accessibleBy($userId);
                })
                ->get();

            $summary = [
                'total_projects' => $projects->count(),
                'planning_projects' => $projects->where('status', 'planning')->count(),
                'ongoing_projects' => $projects->where('status', 'on_going')->count(),
                'completed_projects' => $projects->where('status', 'completed')->count(),
                'total_budget' => $projects->sum('target_total_amount'),
                'planning_budget' => $projects->where('status', 'planning')->sum('target_total_amount'),
                'ongoing_budget' => $projects->where('status', 'on_going')->sum('target_total_amount'),
                'completed_budget' => $projects->where('status', 'completed')->sum('target_total_amount'),
                'total_items' => $projects->sum('items_count'),
            ];

            return response()->json($summary);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get status badge class
     */
    private function getStatusBadge($status)
    {
        return match($status) {
            'planning' => 'bg-blue-100 text-blue-800',
            'on_going' => 'bg-yellow-100 text-yellow-800',
            'completed' => 'bg-green-100 text-green-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Calculate project progress percentage
     */
    private function calculateProgress($project)
    {
        $totalItems = $project->items()->count();
        if ($totalItems === 0) return 0;

        $completedItems = $project->items()->where('status', 'completed')->count();
        return ($completedItems / $totalItems) * 100;
    }

    // Di ProjectController.php
    public function encryptId(Request $request)
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id'
        ]);

        return response()->json([
            'encrypted_id' => Crypt::encrypt($request->project_id)
        ]);
    }
}