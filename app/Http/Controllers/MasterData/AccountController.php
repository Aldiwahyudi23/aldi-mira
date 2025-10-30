<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\MasterData\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Ambil akun yang bisa diakses user (personal + joint dalam family)
        $accounts = Account::accessibleBy(Auth::id())
            ->with('user') // load data user yang membuat
            ->orderBy('type')
            ->orderBy('name')
            ->get();
        
        if (request()->wantsJson()) {
            return response()->json($accounts);
        }
        
        return Inertia::render('MasterData/Account/Index', [
            'accounts' => $accounts
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:100|unique:accounts,name,NULL,id,user_id,' . Auth::id(),
                'type' => 'required|in:personal,joint',
                'current_balance' => 'required|numeric|min:0',
            ]);

            // Tambahkan user_id dari user yang login
            $validated['user_id'] = Auth::id();
            
            Account::create($validated);

            return redirect()->back()->with([
                'success' => 'Akun berhasil dibuat!',
                'type' => 'success'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return redirect()->back()->with([
                'error' => 'Terjadi kesalahan saat menyimpan akun.',
                'type' => 'error'
            ]);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Account $account)
    {
        try {
            // Cek authorization - hanya creator yang bisa update
            if ($account->user_id !== Auth::id()) {
                return redirect()->back()->with([
                    'error' => 'Anda tidak memiliki akses untuk mengubah akun ini.',
                    'type' => 'error'
                ]);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:100|unique:accounts,name,' . $account->id . ',id,user_id,' . Auth::id(),
                'type' => 'required|in:personal,joint',
                'current_balance' => 'required|numeric|min:0',
            ]);

            $account->update($validated);

            return redirect()->back()->with([
                'success' => 'Akun berhasil diperbarui!',
                'type' => 'success'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return redirect()->back()->with([
                'error' => 'Terjadi kesalahan saat memperbarui akun.',
                'type' => 'error'
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Account $account)
    {
        try {
            // Cek authorization - hanya creator yang bisa delete
            if ($account->user_id !== Auth::id()) {
                return redirect()->back()->with([
                    'error' => 'Anda tidak memiliki akses untuk menghapus akun ini.',
                    'type' => 'error'
                ]);
            }

            // Check if account has related transactions
            if ($account->transactions()->exists()) {
                return redirect()->back()->with([
                    'error' => 'Tidak dapat menghapus akun karena memiliki data transaksi terkait!',
                    'type' => 'error'
                ]);
            }

            $account->delete();

            return redirect()->back()->with([
                'success' => 'Akun berhasil dihapus!',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with([
                'error' => 'Terjadi kesalahan saat menghapus akun.',
                'type' => 'error'
            ]);
        }
    }

    /**
     * Get accounts by type untuk dropdown forms
     */
    public function getByType($type)
    {
        $accounts = Account::accessibleBy(Auth::id())
            ->byType($type)
            ->active()
            ->orderBy('name')
            ->get();

        return response()->json($accounts);
    }

    /**
     * Get personal accounts untuk dropdown forms
     */
    public function getPersonal()
    {
        $accounts = Account::personal(Auth::id())
            ->active()
            ->orderBy('name')
            ->get();

        return response()->json($accounts);
    }

    /**
     * Get joint accounts untuk dropdown forms
     */
    public function getJoint()
    {
        $accounts = Account::joint(Auth::id())
            ->active()
            ->orderBy('name')
            ->get();

        return response()->json($accounts);
    }

    /**
     * Get account balance summary untuk dashboard
     */
    public function getBalanceSummary()
    {
        $userId = Auth::id();
        
        $summary = [
            'total_balance' => Account::getTotalBalance($userId),
            'personal_balance' => Account::getPersonalBalance($userId),
            'joint_balance' => Account::getJointBalance($userId),
            'total_accounts' => Account::accessibleBy($userId)->count(),
            'personal_accounts' => Account::personal($userId)->count(),
            'joint_accounts' => Account::joint($userId)->count(),
        ];

        return response()->json($summary);
    }

    /**
     * Update account balance (untuk manual adjustment)
     */
    public function updateBalance(Request $request, Account $account)
    {
        try {
            // Cek authorization - hanya creator yang bisa update balance
            if ($account->user_id !== Auth::id()) {
                return response()->json([
                    'error' => 'Anda tidak memiliki akses untuk mengubah saldo akun ini.'
                ], 403);
            }

            $validated = $request->validate([
                'current_balance' => 'required|numeric|min:0',
            ]);

            $account->update($validated);

            return redirect()->back()->with([
                    'success' => 'Saldo akun berhasil diperbarui!',
                    'type' => 'success',
                    'new_balance' => $account->current_balance,
                    'formatted_balance' => $account->formatted_balance
                ]);
            } catch (\Illuminate\Validation\ValidationException $e) {
                return redirect()->back()->withErrors($e->errors())->withInput();
            } catch (\Exception $e) {
                return redirect()->back()->with([
                    'error' => 'erjadi kesalahan saat memperbarui saldo akun.',
                    'type' => 'error'
                ]);
            }
    }
}

// =================================

// Cara memnaggil di frontend (contoh menggunakan axios):

// // Load accounts
// const accounts = await axios.get('/master-data/accounts');

// // Load hanya joint accounts
// const jointAccounts = await axios.get('/accounts/joint');

// // Load balance summary untuk dashboard
// const balanceSummary = await axios.get('/accounts/balance-summary');

// // Update balance manual
// await axios.put(`/accounts/${accountId}/update-balance`, {
//     current_balance: newBalance
// });