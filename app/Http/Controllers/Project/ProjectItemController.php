<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Models\Project\Project;
use App\Models\Project\ProjectItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class ProjectItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Project $project)
    {
        $userId = Auth::id();
        // Cek akses ke project melalui kategori
        if (!$project->category->isAccessibleBy(Auth::id())) {
            return redirect()->back()->with([
                'error' => 'Anda tidak memiliki akses ke project ini.',
                'type' => 'error'
            ]);
        }

       $items = $project->items()
            ->orderBy('item_type')
            ->orderBy('item_category') // TAMBAHKAN ORDER BY CATEGORY
            ->orderBy('status')
            ->orderBy('name')
            ->get()
            ->map(function ($item) use ($userId) {
                $categoryOwner = $item->project->category->user;
                $isOwner = $categoryOwner->id === $userId;
                $isPartner = $this->isPartnerInSameFamily($categoryOwner, $userId);
                
                return [
                    'id' => $item->id,
                    'project_id' => $item->project_id,
                    'item_type' => $item->item_type,
                    'item_type_badge' => $this->getItemTypeBadge($item->item_type),
                    'item_type_icon' => $this->getItemTypeIcon($item->item_type),
                    'item_category' => $item->item_category, // TAMBAHKAN INI
                    'name' => $item->name,
                    'description' => $item->description,
                    'planned_amount' => $item->planned_amount,
                    'formatted_planned_amount' => $item->formatted_planned_amount,
                    'actual_spent' => $item->actual_spent,
                    'formatted_actual_spent' => $item->formatted_actual_spent,
                    'remaining_amount' => $item->remaining_amount,
                    'formatted_remaining_amount' => number_format($item->remaining_amount, 0, ',', '.'),
                    'status' => $item->status,
                    'status_badge' => $this->getStatusBadge($item->status),
                    'status_icon' => $this->getStatusIcon($item->status),
                    'progress_percentage' => $item->progress_percentage,
                    'details' => $item->details,
                    'created_at' => $item->created_at->format('d M Y'),
                    'updated_at' => $item->updated_at->format('d M Y'),
                    'is_owner' => $item->project->category->user_id === Auth::id(),
                    'is_partner' => $isPartner,
                    'can_edit' => $isOwner || $isPartner,
                ];
            });


        if (request()->wantsJson()) {
            return response()->json([
                'items' => $items,
                'project' => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'category' => $project->category->name,
                ]
            ]);
        }

        return Inertia::render('Project/Item/Index', [
            'items' => $items,
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'category' => $project->category->name,
                'target_total_amount' => $project->target_total_amount,
                'formatted_target_amount' => $project->formatted_target_amount,
                'status' => $project->status,
            ]
        ]);
    }

    //  public function index($projects)
    // {
    //     try {
    //         // Decrypt ID
    //         $id = Crypt::decrypt($projects);
    //         $project = Project::with('category')->findOrFail($id);

    //         // Cek akses ke project melalui kategori (sama seperti method index biasa)
    //         if (!$project->category->isAccessibleBy(Auth::id())) {
    //             return redirect()->route('projects.index')->with([
    //                 'error' => 'Anda tidak memiliki akses ke project ini.',
    //                 'type' => 'error'
    //             ]);
    //         }

    //         $userId = Auth::id();
            
    //         $items = $project->items()
    //             ->orderBy('item_type')
    //             ->orderBy('status')
    //             ->orderBy('name')
    //             ->get()
    //             ->map(function ($item) use ($userId) {
    //                 $categoryOwner = $item->project->category->user;
    //                 $isOwner = $categoryOwner->id === $userId;
    //                 $isPartner = $this->isPartnerInSameFamily($categoryOwner, $userId);
                    
    //                 return [
    //                     'id' => $item->id,
    //                     'project_id' => $item->project_id,
    //                     'item_type' => $item->item_type,
    //                     'item_type_badge' => $this->getItemTypeBadge($item->item_type),
    //                     'item_type_icon' => $this->getItemTypeIcon($item->item_type),
    //                     'name' => $item->name,
    //                     'description' => $item->description,
    //                     'planned_amount' => $item->planned_amount,
    //                     'formatted_planned_amount' => $item->formatted_planned_amount,
    //                     'actual_spent' => $item->actual_spent,
    //                     'formatted_actual_spent' => $item->formatted_actual_spent,
    //                     'remaining_amount' => $item->remaining_amount,
    //                     'formatted_remaining_amount' => number_format($item->remaining_amount, 0, ',', '.'),
    //                     'status' => $item->status,
    //                     'status_badge' => $this->getStatusBadge($item->status),
    //                     'status_icon' => $this->getStatusIcon($item->status),
    //                     'progress_percentage' => $item->progress_percentage,
    //                     'details' => $item->details,
    //                     'created_at' => $item->created_at->format('d M Y'),
    //                     'updated_at' => $item->updated_at->format('d M Y'),
    //                     'is_owner' => $item->project->category->user_id === Auth::id(),
    //                     'is_partner' => $isPartner,
    //                     'can_edit' => $isOwner || $isPartner,
    //                 ];
    //             });

    //         return Inertia::render('Project/Item/Index', [
    //             'items' => $items,
    //             'project' => [
    //                 'id' => $project->id,
    //                 'name' => $project->name,
    //                 'category' => $project->category->name,
    //                 'target_total_amount' => $project->target_total_amount,
    //                 'formatted_target_amount' => $project->formatted_target_amount,
    //                 'status' => $project->status,
    //             ]
    //         ]);

    //     } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
    //         return redirect()->route('projects.index')->with([
    //             'error' => 'Link project tidak valid atau telah kadaluarsa.',
    //             'type' => 'error'
    //         ]);
    //     } catch (\Exception $e) {
    //         return redirect()->route('projects.index')->with([
    //             'error' => 'Project tidak ditemukan.',
    //             'type' => 'error'
    //         ]);
    //     }
    // }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Project $project)
    {
        try {
            // Cek akses dan ownership
            if (!$project->category->isAccessibleBy(Auth::id())) {
                return redirect()->back()->with([
                    'error' => 'Anda tidak memiliki akses ke project ini.',
                    'type' => 'error'
                ]);
            }

            // Cek authorization - pemilik kategori ATAU partner dalam family yang sama
            if (!$this->canModifyProject($project)) {
                return redirect()->back()->with([
                    'error' => 'Anda tidak memiliki akses untuk menambah item.',
                    'type' => 'error'
                ]);
            }

            $validated = $request->validate([
                'item_type' => 'required|in:goods,service,document,task,material',
                'item_category' => 'nullable|string|max:150', // TAMBAHKAN VALIDASI
                'name' => 'required|string|max:150',
                'description' => 'nullable|string',
                'planned_amount' => 'nullable|numeric|min:0',
                'actual_spent' => 'nullable|numeric|min:0',
                'status' => 'required|in:needed,in_progress,ready,complete,cancelled',
                'details' => 'nullable|array',
            ]);

            DB::beginTransaction();

            $item = $project->items()->create($validated);

            DB::commit();

            return redirect()->back()->with([
                'success' => 'Item berhasil ditambahkan!',
                'type' => 'success'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with([
                'error' => 'Terjadi kesalahan saat menyimpan item: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    // Di ProjectItemController.php
    public function show(Project $project, ProjectItem $item)
    {
        $userId = Auth::id();
        
        // Cek akses ke project
        if (!$project->category->isAccessibleBy($userId)) {
            return redirect()->route('projects.items.index', $project->id)->with([
                'error' => 'Anda tidak memiliki akses ke project ini.',
                'type' => 'error'
            ]);
        }

        // Cek apakah item belongs to project
        if ($item->project_id !== $project->id) {
            return redirect()->route('projects.items.index', $project->id)->with([
                'error' => 'Item tidak ditemukan dalam project ini.',
                'type' => 'error'
            ]);
        }

        // Load data dengan eager loading
        $item->load([
            'project.category.user',
            'checklists' => function($query) {
                $query->orderBy('is_completed')->orderBy('created_at');
            }
            // nanti bisa ditambah: 'payments', 'documents', dll
        ]);

        $categoryOwner = $item->project->category->user;
        $isOwner = $categoryOwner->id === $userId;
        $isPartner = $this->isPartnerInSameFamily($categoryOwner, $userId);

        // Format item data
        $itemData = [
            'id' => $item->id,
            'project_id' => $item->project_id,
            'item_type' => $item->item_type,
            'item_type_badge' => $this->getItemTypeBadge($item->item_type),
            'item_type_icon' => $this->getItemTypeIcon($item->item_type),
            'item_category' => $item->item_category, // TAMBAHKAN INI
            'name' => $item->name,
            'description' => $item->description,
            'planned_amount' => $item->planned_amount,
            'formatted_planned_amount' => $item->formatted_planned_amount,
            'actual_spent' => $item->actual_spent,
            'formatted_actual_spent' => $item->formatted_actual_spent,
            'remaining_amount' => $item->remaining_amount,
            'formatted_remaining_amount' => number_format($item->remaining_amount, 0, ',', '.'),
            'status' => $item->status,
            'status_badge' => $this->getStatusBadge($item->status),
            'status_icon' => $this->getStatusIcon($item->status),
            'progress_percentage' => $item->progress_percentage,
            'details' => $item->details,
            'created_at' => $item->created_at->format('d M Y'),
            'updated_at' => $item->updated_at->format('d M Y'),
            'is_owner' => $isOwner,
            'is_partner' => $isPartner,
            'can_edit' => $isOwner || $isPartner,
            
            // Checklists data
            'checklists' => $item->checklists->map(function($checklist) {
                return [
                    'id' => $checklist->id,
                    'task_description' => $checklist->task_description,
                    'is_completed' => $checklist->is_completed,
                    'completed_at' => $checklist->completed_at?->format('d M Y H:i'),
                    'created_at' => $checklist->created_at->format('d M Y H:i'),
                    'updated_at' => $checklist->updated_at->format('d M Y H:i'),
                ];
            }),

            'payments' => $item->payments->map(function($payment) {
                return [
                    'id' => $payment->id,
                    'amount' => $payment->amount,
                    'formatted_amount' => number_format($payment->amount, 0, ',', '.'),
                    'payment_method' => $payment->payment_method,
                    'status' => $payment->status,
                    'note' => $payment->note,
                    'created_at' => $payment->created_at->format('d M Y H:i'),
                    'updated_at' => $payment->updated_at->format('d M Y H:i'),
                ];
            }),

            
            // Project info untuk breadcrumb
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'category' => $project->category->name,
            ]
        ];

        return Inertia::render('Project/Item/Show', [
            'item' => $itemData,
            'project' => $project->only(['id', 'name', 'category', 'target_total_amount', 'status']),
            'canEdit' => $isOwner || $isPartner,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Project $project, ProjectItem $item)
    {
        try {
            // Cek akses dan ownership
            if (!$project->category->isAccessibleBy(Auth::id())) {
                return redirect()->back()->with([
                    'error' => 'Anda tidak memiliki akses ke project ini.',
                    'type' => 'error'
                ]);
            }

            // Cek authorization - pemilik kategori ATAU partner dalam family yang sama
            if (!$this->canModifyProject($project)) {
                return redirect()->back()->with([
                    'error' => 'Anda tidak memiliki akses untuk mengubah item.',
                    'type' => 'error'
                ]);
            }

            $validated = $request->validate([
                'item_type' => 'required|in:goods,service,document,task,material',
                'item_category' => 'nullable|string|max:150', // TAMBAHKAN VALIDASI
                'name' => 'required|string|max:150',
                'description' => 'nullable|string',
                'planned_amount' => 'nullable|numeric|min:0',
                'actual_spent' => 'nullable|numeric|min:0',
                'status' => 'required|in:needed,in_progress,ready,complete,cancelled',
                'details' => 'nullable|array',
            ]);

            DB::beginTransaction();

            $item->update($validated);

            DB::commit();

            return redirect()->back()->with([
                'success' => 'Item berhasil diperbarui!',
                'type' => 'success'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with([
                'error' => 'Terjadi kesalahan saat memperbarui item: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project, ProjectItem $item)
    {
        try {
            // Cek akses dan ownership
            if (!$project->category->isAccessibleBy(Auth::id())) {
                return redirect()->back()->with([
                    'error' => 'Anda tidak memiliki akses ke project ini.',
                    'type' => 'error'
                ]);
            }

              // Cek authorization - pemilik kategori ATAU partner dalam family yang sama
            if (!$this->canModifyProject($project)) {
                return redirect()->back()->with([
                    'error' => 'Anda tidak memiliki akses untuk menghapus item.',
                    'type' => 'error'
                ]);
            }

            DB::beginTransaction();

            $item->delete();

            DB::commit();

            return redirect()->back()->with([
                'success' => 'Item berhasil dihapus!',
                'type' => 'success'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with([
                'error' => 'Terjadi kesalahan saat menghapus item: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    private function isPartnerInSameFamily($categoryOwner, $userId)
    {
        $user = \App\Models\User::find($userId);
        
        if (!$user || !$categoryOwner) {
            return false;
        }
        
        // Cek melalui family_id
        if ($user->family_id && $categoryOwner->family_id === $user->family_id) {
            return true;
        }
        
        // Cek melalui partner relationship
        if ($user->partner && $categoryOwner->partner) {
            return $user->partner->id === $categoryOwner->id || 
                $categoryOwner->partner->id === $user->id;
        }
        
        return false;
    }

        /**
     * Check if user can modify project items
     * - Pemilik kategori ATAU
     * - Partner dalam family yang sama
     */
    private function canModifyProject(Project $project)
    {
        $user = Auth::user();
        
        // 1. Jika user adalah pemilik kategori
        if ($project->category->user_id === $user->id) {
            return true;
        }

        // 2. Jika user adalah partner dalam family yang sama
        if ($user->partner && $project->category->user->partner) {
            // Cek apakah kedua user memiliki family_id yang sama
            $categoryOwner = $project->category->user;
            
            // Jika category owner memiliki family dan user juga dalam family yang sama
            if ($categoryOwner->family_id && $user->family_id === $categoryOwner->family_id) {
                return true;
            }
            
            // Alternatif: Cek melalui partner relationship
            if ($user->partner->id === $categoryOwner->id || $categoryOwner->partner->id === $user->id) {
                return true;
            }
        }

        // 3. Cek melalui family relationship langsung
        if ($user->family_id && $project->category->user->family_id === $user->family_id) {
            return true;
        }

        return false;
    }

    /**
     * Get item summary untuk project
     */
public function getItemSummary(Project $project)
{
    try {
        // Cek akses
        if (!$project->category->isAccessibleBy(Auth::id())) {
            return response()->json([
                'error' => 'Anda tidak memiliki akses ke project ini.',
                'success' => false
            ], 403);
        }

        $items = $project->items;

        $summary = [
            'total_items' => $items->count(),
            'goods_count' => $items->where('item_type', 'goods')->count(),
            'services_count' => $items->where('item_type', 'service')->count(),
            'documents_count' => $items->where('item_type', 'document')->count(),
            'tasks_count' => $items->where('item_type', 'task')->count(),
            'materials_count' => $items->where('item_type', 'material')->count(),
            
            // Tambahkan summary per kategori
            'categories_count' => $items->whereNotNull('item_category')->pluck('item_category')->unique()->count(),
            'uncategorized_count' => $items->whereNull('item_category')->orWhere('item_category', '')->count(),
            
            'total_planned' => $items->sum('planned_amount'),
            'total_spent' => $items->sum('actual_spent'),
            'total_remaining' => $items->sum('planned_amount') - $items->sum('actual_spent'),
            
            'needed_count' => $items->where('status', 'needed')->count(),
            'in_progress_count' => $items->where('status', 'in_progress')->count(),
            'ready_count' => $items->where('status', 'ready')->count(),
            'complete_count' => $items->where('status', 'complete')->count(),
            'cancelled_count' => $items->where('status', 'cancelled')->count(),
        ];

        return response()->json($summary);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

    // Di ProjectItemController.php
public function getItemSummaryEncrypted($projects)
{
    try {
        // Decrypt ID project
        $projectId = Crypt::decrypt($projects);
        
        // Cari project berdasarkan decrypted ID
        $project = Project::findOrFail($projectId);

        // Cek akses
        if (!$project->category->isAccessibleBy(Auth::id())) {
            return response()->json([
                'error' => 'Anda tidak memiliki akses ke project ini.',
                'success' => false
            ], 403);
        }

        // Gunakan logic yang sama dengan getItemSummary biasa
        $items = $project->items;
        
        $summary = [
            'total_items' => $items->count(),
            'goods_count' => $items->where('item_type', 'goods')->count(),
            'services_count' => $items->where('item_type', 'service')->count(),
            'documents_count' => $items->where('item_type', 'document')->count(),
            'tasks_count' => $items->where('item_type', 'task')->count(),
            'materials_count' => $items->where('item_type', 'material')->count(),
            'total_planned' => $items->sum('planned_amount'),
            'total_spent' => $items->sum('actual_spent'),
            'total_remaining' => $items->sum('remaining_amount'),
        ];

        return response()->json($summary);

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Gagal memuat summary items',
            'success' => false
        ], 500);
    }
}

    /**
     * Helper methods untuk badges dan icons
     */
    private function getItemTypeBadge($type)
    {
        return match($type) {
            'goods' => 'bg-purple-100 text-purple-800',
            'service' => 'bg-blue-100 text-blue-800',
            'document' => 'bg-green-100 text-green-800',
            'task' => 'bg-yellow-100 text-yellow-800',
            'material' => 'bg-orange-100 text-orange-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    private function getItemTypeIcon($type)
    {
        return match($type) {
            'goods' => '🛍️',
            'service' => '👨‍💼',
            'document' => '📄',
            'task' => '✅',
            'material' => '🏗️',
            default => '📝',
        };
    }

    private function getStatusBadge($status)
    {
        return match($status) {
            'needed' => 'bg-gray-100 text-gray-800',
            'in_progress' => 'bg-yellow-100 text-yellow-800',
            'ready' => 'bg-blue-100 text-blue-800',
            'complete' => 'bg-green-100 text-green-800',
            'cancelled' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    private function getStatusIcon($status)
    {
        return match($status) {
            'needed' => '⏳',
            'in_progress' => '🚧',
            'ready' => '📦',
            'complete' => '✅',
            'cancelled' => '❌',
            default => '📝',
        };
    }

    /**
     * Get all unique categories for a project
     */
public function getCategories(Project $project)
{
    try {
        // Cek akses
        if (!$project->category->isAccessibleBy(Auth::id())) {
            return response()->json([
                'error' => 'Anda tidak memiliki akses ke project ini.',
                'success' => false
            ], 403);
        }

        $categories = $project->items()
            ->select('item_category')
            ->whereNotNull('item_category')
            ->where('item_category', '!=', '')
            ->distinct()
            ->orderBy('item_category')
            ->pluck('item_category');

        // Pastikan selalu return array, bahkan jika kosong
        return response()->json([
            'categories' => $categories->toArray(), // TAMBAHKAN ->toArray()
            'success' => true
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Gagal memuat kategori: ' . $e->getMessage(),
            'success' => false
        ], 500);
    }
}

    /**
     * Get all unique categories for encrypted project
     */
    public function getCategoriesEncrypted($projects)
    {
        try {
            // Decrypt ID project
            $projectId = Crypt::decrypt($projects);
            $project = Project::findOrFail($projectId);

            // Cek akses
            if (!$project->category->isAccessibleBy(Auth::id())) {
                return response()->json([
                    'error' => 'Anda tidak memiliki akses ke project ini.',
                    'success' => false
                ], 403);
            }

            $categories = $project->items()
                ->select('item_category')
                ->whereNotNull('item_category')
                ->where('item_category', '!=', '')
                ->distinct()
                ->orderBy('item_category')
                ->pluck('item_category');

            return response()->json([
                'categories' => $categories,
                'success' => true
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Gagal memuat kategori',
                'success' => false
            ], 500);
        }
    }

}