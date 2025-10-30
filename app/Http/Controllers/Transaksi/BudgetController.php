<?php

namespace App\Http\Controllers\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\MasterData\Budget;
use App\Models\MasterData\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class BudgetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Ambil budget yang bisa diakses user
        $budgets = Budget::accessibleBy(Auth::id())
            ->with(['category', 'category.user'])
            ->orderBy('period_start', 'desc')
            ->orderBy('category_id')
            ->get();
        
        if (request()->wantsJson()) {
            return response()->json($budgets);
        }
        
        return Inertia::render('Transaksi/Budget/Index', [
            'budgets' => $budgets
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
                'period_start' => 'required|date',
                'period_end' => 'required|date|after:period_start',
                'target_amount' => 'required|numeric|min:0',
            ]);

            // Cek apakah kategori bisa diakses user
            $category = Category::findOrFail($validated['category_id']);
            if (!$category->isAccessibleBy(Auth::id())) {
                return redirect()->back()->with([
                    'error' => 'Anda tidak memiliki akses ke kategori yang dipilih.',
                    'type' => 'error'
                ]);
            }

            // Cek apakah sudah ada budget untuk kategori dan periode yang sama
            $existingBudget = Budget::where('category_id', $validated['category_id'])
                ->where('period_start', $validated['period_start'])
                ->first();

            if ($existingBudget) {
                return redirect()->back()->with([
                    'error' => 'Sudah ada budget untuk kategori ini pada periode yang sama.',
                    'type' => 'error'
                ]);
            }

            Budget::create($validated);

            return redirect()->back()->with([
                'success' => 'Budget berhasil dibuat!',
                'type' => 'success'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return redirect()->back()->with([
                'error' => 'Terjadi kesalahan saat menyimpan budget: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Budget $budget)
    {
        try {
            $validated = $request->validate([
                'category_id' => 'required|exists:categories,id',
                'period_start' => 'required|date',
                'period_end' => 'required|date|after:period_start',
                'target_amount' => 'required|numeric|min:0',
            ]);

            // Cek apakah kategori bisa diakses user
            $category = Category::findOrFail($validated['category_id']);
            if (!$category->isAccessibleBy(Auth::id())) {
                return redirect()->back()->with([
                    'error' => 'Anda tidak memiliki akses ke kategori yang dipilih.',
                    'type' => 'error'
                ]);
            }

            // Cek apakah sudah ada budget lain untuk kategori dan periode yang sama
            $existingBudget = Budget::where('category_id', $validated['category_id'])
                ->where('period_start', $validated['period_start'])
                ->where('id', '!=', $budget->id)
                ->first();

            if ($existingBudget) {
                return redirect()->back()->with([
                    'error' => 'Sudah ada budget untuk kategori ini pada periode yang sama.',
                    'type' => 'error'
                ]);
            }

            $budget->update($validated);

            return redirect()->back()->with([
                'success' => 'Budget berhasil diperbarui!',
                'type' => 'success'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return redirect()->back()->with([
                'error' => 'Terjadi kesalahan saat memperbarui budget: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Budget $budget)
    {
        try {
            $budget->delete();

            return redirect()->back()->with([
                'success' => 'Budget berhasil dihapus!',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with([
                'error' => 'Terjadi kesalahan saat menghapus budget: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * Get accessible categories untuk dropdown - UPDATED untuk expense & savings
     */
    public function getCategories()
    {
        try {
            $categories = Category::accessibleBy(Auth::id())
                ->whereIn('budget_type', ['expense']) // Allow both expense and savings
                ->active()
                ->orderBy('budget_type')
                ->orderBy('name')
                ->get();

            return response()->json($categories);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to load categories',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get budget summary untuk dashboard
     */
    public function getSummary()
    {
        try {
            $summary = Budget::getBudgetSummary(Auth::id());
            return response()->json($summary);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Create next month budget berdasarkan budget bulan ini
     */
    public function createNextMonth(Budget $budget)
    {
        try {
            $nextBudget = Budget::createNextMonthBudget($budget);

            return redirect()->back()->with([
                'success' => 'Budget untuk bulan depan berhasil dibuat!',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with([
                'error' => 'Terjadi kesalahan saat membuat budget bulan depan: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * Update spent amount untuk budget tertentu
     */
    public function updateSpentAmount(Budget $budget)
    {
        try {
            $budget->updateSpentAmount();

            return response()->json([
                'success' => 'Spent amount berhasil diupdate',
                'spent_amount' => $budget->spent_amount,
                'remaining_amount' => $budget->remaining_amount,
                'usage_percentage' => $budget->usage_percentage
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get active budgets untuk dropdown
     */
    public function getActiveBudgets()
    {
        try {
            $budgets = Budget::accessibleBy(Auth::id())
                ->active()
                ->with('category')
                ->orderBy('category_id')
                ->get();

            return response()->json($budgets);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}