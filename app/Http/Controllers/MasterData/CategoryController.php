<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\MasterData\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Ambil kategori yang bisa diakses user (personal + joint dalam family)
        $categories = Category::accessibleBy(Auth::id())
            ->with('user') // load data user yang membuat
            ->orderBy('type')
            ->orderBy('name')
            ->get();
        
        if (request()->wantsJson()) {
            return response()->json($categories);
        }
        
        return Inertia::render('MasterData/Category/Index', [
            'categories' => $categories
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:100|unique:categories,name,NULL,id,user_id,' . Auth::id(),
                'type' => 'required|in:personal,joint',
                'budget_type' => 'required|in:income,expense,savings',
                'description' => 'nullable|string|max:500',
                'is_active' => 'boolean'
            ]);

            // Tambahkan user_id dari user yang login
            $validated['user_id'] = Auth::id();
            
            Category::create($validated);

            return redirect()->back()->with([
                'success' => 'Kategori berhasil dibuat!',
                'type' => 'success'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return redirect()->back()->with([
                'error' => 'Terjadi kesalahan saat menyimpan kategori.',
                'type' => 'error'
            ]);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        try {
            // Cek authorization - hanya creator yang bisa update
            if ($category->user_id !== Auth::id()) {
                return redirect()->back()->with([
                    'error' => 'Anda tidak memiliki akses untuk mengubah kategori ini.',
                    'type' => 'error'
                ]);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:100|unique:categories,name,' . $category->id . ',id,user_id,' . Auth::id(),
                'type' => 'required|in:personal,joint',
                'budget_type' => 'required|in:income,expense,savings',
                'description' => 'nullable|string|max:500',
                'is_active' => 'boolean'
            ]);

            $category->update($validated);

            return redirect()->back()->with([
                'success' => 'Kategori berhasil diperbarui!',
                'type' => 'success'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return redirect()->back()->with([
                'error' => 'Terjadi kesalahan saat memperbarui kategori.',
                'type' => 'error'
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        try {
            // Cek authorization - hanya creator yang bisa delete
            if ($category->user_id !== Auth::id()) {
                return redirect()->back()->with([
                    'error' => 'Anda tidak memiliki akses untuk menghapus kategori ini.',
                    'type' => 'error'
                ]);
            }

            // Check if category has related data
            if ($category->budgets()->exists()) {
                return redirect()->back()->with([
                    'error' => 'Tidak dapat menghapus kategori karena memiliki data anggaran terkait!',
                    'type' => 'error'
                ]);
            }

            if ($category->transactions()->exists()) {
                return redirect()->back()->with([
                    'error' => 'Tidak dapat menghapus kategori karena memiliki data transaksi terkait!',
                    'type' => 'error'
                ]);
            }

            $category->delete();

            return redirect()->back()->with([
                'success' => 'Kategori berhasil dihapus!',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with([
                'error' => 'Terjadi kesalahan saat menghapus kategori.',
                'type' => 'error'
            ]);
        }
    }

    /**
     * Get categories by budget type untuk dropdown forms
     */
    public function getByBudgetType($budgetType)
    {
        $categories = Category::accessibleBy(Auth::id())
            ->byBudgetType($budgetType)
            ->active()
            ->orderBy('name')
            ->get();

        return response()->json($categories);
    }

    /**
     * Get personal categories untuk dropdown forms
     */
    public function getPersonal()
    {
        $categories = Category::personal(Auth::id())
            ->active()
            ->orderBy('name')
            ->get();

        return response()->json($categories);
    }

    /**
     * Get joint categories untuk dropdown forms
     */
    public function getJoint()
    {
        $categories = Category::joint(Auth::id())
            ->active()
            ->orderBy('name')
            ->get();

        return response()->json($categories);
    }
}