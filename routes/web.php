<?php

use App\Actions\Fortify\DestroyTheme;
use App\Actions\Fortify\UpdateThemeSettings;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Route::get('/', function () {
//     return Inertia::render('Welcome', [
//         'canLogin' => Route::has('login'),
//         'canRegister' => Route::has('register'),
//         'laravelVersion' => Application::VERSION,
//         'phpVersion' => PHP_VERSION,
//     ]);
// });

use App\Http\Controllers\Auth\KodeLoginController;
use App\Http\Controllers\Home\DashboardController;
use App\Http\Controllers\MasterData\AccountController;
use App\Http\Controllers\MasterData\CategoryController;
use App\Http\Controllers\Project\ItemChecklistController;
use App\Http\Controllers\Project\ProjectController;
use App\Http\Controllers\Project\ProjectItemController;
use App\Http\Controllers\Project\ProjectPaymentController;
use App\Http\Controllers\Transaksi\BudgetController;
use App\Http\Controllers\Transaksi\TransactionController;
use Illuminate\Http\Request;

Route::get('/login', [KodeLoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [KodeLoginController::class, 'login'])->name('kode.login');
// Route::post('/logout', [KodeLoginController::class, 'logout'])->name('logout');


Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'role:Suami|Istri'
])->group(function () {
    
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('/dashboard', DashboardController::class);

    Route::get('/profile', function () {
        return Inertia::render('Profile/Index');
    })->name('profile');

    // ==============🎨 Update Tema (Settings)===================
        Route::post('/user/theme', function (Request $request, UpdateThemeSettings $updater) {
            $updater->update($request->user(), $request->all());
            return back()->with('status', 'theme-updated');
        })->name('user-theme.update');

        // ==============🗑️ Hapus Tema dari Database ===================
        // Hapus section tertentu
        Route::delete('/user/theme/section', function (Request $request, DestroyTheme $destroyer) {
            return $destroyer->destroySection($request);
        })->name('user-theme.destroy-section');

        // Hapus semua theme
        Route::delete('/user/theme', function (Request $request, DestroyTheme $destroyer) {
            return $destroyer->destroyAll($request);
        })->name('user-theme.destroy-all');
    // ==========================Maaster Data===========================

    Route::prefix('master-data')->name('master-data.')->group(function () {
         // Category Resource Route
        Route::resource('categories', CategoryController::class);
        // ACcount Resource Route
        Route::resource('accounts', AccountController::class);
            Route::get('accounts/by-type/{type}', [AccountController::class, 'getByType'])->name('accounts.by-type');
            Route::get('accounts/personal', [AccountController::class, 'getPersonal'])->name('accounts.personal');
            Route::get('accounts/joint', [AccountController::class, 'getJoint'])->name('accounts.joint');
            Route::get('accounts/balance-summary', [AccountController::class, 'getBalanceSummary'])->name('accounts.balance-summary');
            Route::put('accounts/{account}/update-balance', [AccountController::class, 'updateBalance'])->name('accounts.update-balance');
    });

    // =========================Transaksi==========================

    // Transactions routes
    Route::prefix('transactions')->name('transactions.')->group(function () {
        Route::get('/', [TransactionController::class, 'index'])->name('index');
        Route::post('/', [TransactionController::class, 'store'])->name('store');
        Route::put('/{transaction}', [TransactionController::class, 'update'])->name('update');
        Route::delete('/{transaction}', [TransactionController::class, 'destroy'])->name('destroy');
        
        // API routes untuk frontend validation
        Route::get('/api/accounts', [TransactionController::class, 'getAccounts'])->name('api.accounts');
        Route::get('/api/accounts/{accountId}/balance', [TransactionController::class, 'getAccountBalance'])->name('api.account-balance');
        Route::post('/api/check-feasibility', [TransactionController::class, 'checkTransactionFeasibility'])->name('api.check-feasibility');
        Route::get('/api/categories', [TransactionController::class, 'getCategories'])->name('api.categories');
        Route::get('/api/categories/{budgetType}', [TransactionController::class, 'getCategoriesByBudgetType'])->name('api.categories-by-budget-type');
    });

    // Budget routes
    Route::prefix('budgets')->name('budgets.')->group(function () {
        Route::get('/', [BudgetController::class, 'index'])->name('index');
        Route::post('/', [BudgetController::class, 'store'])->name('store');
        Route::put('/{budget}', [BudgetController::class, 'update'])->name('update');
        Route::delete('/{budget}', [BudgetController::class, 'destroy'])->name('destroy');

        Route::get('/initialize-balances', [TransactionController::class, 'initializeAccountBalances']);
        
        // API routes
        Route::get('/api/categories', [BudgetController::class, 'getCategories'])->name('api.categories');
        Route::get('/api/summary', [BudgetController::class, 'getSummary'])->name('api.summary');
        Route::get('/api/active', [BudgetController::class, 'getActiveBudgets'])->name('api.active');
        Route::post('/{budget}/next-month', [BudgetController::class, 'createNextMonth'])->name('next-month');
        Route::post('/{budget}/update-spent', [BudgetController::class, 'updateSpentAmount'])->name('update-spent');
        
    });

    // =========================Project==========================
   Route::prefix('projects')->name('projects.')->group(function () {
        Route::get('/', [ProjectController::class, 'index'])->name('index');
        Route::post('/', [ProjectController::class, 'store'])->name('store');
        Route::put('/{project}', [ProjectController::class, 'update'])->name('update');
        Route::delete('/{project}', [ProjectController::class, 'destroy'])->name('destroy');
        
        // API routes
        Route::get('/api/categories', [ProjectController::class, 'getCategories'])->name('api.categories');
        Route::get('/api/summary', [ProjectController::class, 'getProjectSummary'])->name('api.summary');
        Route::post('/api/encrypt-id', [ProjectController::class, 'encryptId'])->name('api.encrypt-id');
    });

    Route::prefix('projects/{project}')->name('projects.')->group(function () {
        Route::prefix('items')->name('items.')->group(function () {
            Route::get('/', [ProjectItemController::class, 'index'])->name('index');
            Route::get('/{item}', [ProjectItemController::class, 'show'])->name('show');
            Route::post('/', [ProjectItemController::class, 'store'])->name('store');
            Route::put('/{item}', [ProjectItemController::class, 'update'])->name('update');
            Route::delete('/{item}', [ProjectItemController::class, 'destroy'])->name('destroy');
            
            // API routes
            Route::get('/api/summary', [ProjectItemController::class, 'getItemSummary'])->name('api.summary');
        });
    });

// Checklist routes
Route::prefix('project-items')->name('project-items.')->group(function () {
    Route::get('/{projectItem}/details', [ProjectItemController::class, 'getItemDetails'])->name('details');
    
    Route::prefix('{projectItem}')->group(function () {
        Route::prefix('checklists')->name('checklists.')->group(function () {
            Route::post('/', [ItemChecklistController::class, 'store'])->name('store');
            Route::put('/{checklist}', [ItemChecklistController::class, 'update'])->name('update');
            Route::patch('/{checklist}/toggle', [ItemChecklistController::class, 'toggleComplete'])->name('toggle');
            Route::delete('/{checklist}', [ItemChecklistController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('payments')->name('payments.')->group(function () {
            Route::get('/', [ProjectPaymentController::class, 'index'])->name('index');
            Route::post('/', [ProjectPaymentController::class, 'store'])->name('store');
            Route::get('/{payment}', [ProjectPaymentController::class, 'show'])->name('show');
            Route::put('/{payment}', [ProjectPaymentController::class, 'update'])->name('update');
            Route::delete('/{payment}', [ProjectPaymentController::class, 'destroy'])->name('destroy');
    
            Route::post('/purchase', [ProjectPaymentController::class, 'purchase'])->name('purchase');
        });
    });
    Route::get('/api/accounts', [ProjectPaymentController::class, 'getAccounts'])->name('api.accounts');
});


});
