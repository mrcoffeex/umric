<?php

use App\Http\Controllers\Admin\AgendaController;
use App\Http\Controllers\Admin\ApproveUserController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\ProgramController;
use App\Http\Controllers\Admin\SchoolClassController;
use App\Http\Controllers\Admin\SdgController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ResearchPaperController;
use App\Models\Category;
use App\Models\ResearchPaper;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
        'featuredPapers' => ResearchPaper::with('category')
            ->where('status', 'published')
            ->latest()
            ->take(6)
            ->get(),
        'categories' => Category::all(),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('papers/proponents/search', [ResearchPaperController::class, 'searchProponents'])
        ->name('papers.proponents.search');
    Route::resource('papers', ResearchPaperController::class);
    Route::resource('categories', CategoryController::class);

    Route::post('papers/{paper}/files', [ResearchPaperController::class, 'storeFile'])->name('papers.storeFile');
    Route::get('papers/{paper}/qr', [ResearchPaperController::class, 'generateQR'])->name('papers.qr');

    // Admin routes (admin + staff)
    Route::middleware('can:accessAdmin,App\Models\User')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('departments', DepartmentController::class)->except(['create', 'edit', 'show']);
        Route::resource('programs', ProgramController::class)->except(['create', 'edit', 'show', 'index']);
        Route::resource('subjects', SubjectController::class)->except(['create', 'edit', 'show']);
        Route::resource('classes', SchoolClassController::class)->except(['create', 'edit', 'show']);
        Route::resource('sdgs', SdgController::class)->except(['create', 'edit', 'show']);
        Route::resource('agendas', AgendaController::class)->except(['create', 'edit', 'show']);
        Route::resource('users', UserController::class)->except(['create', 'edit', 'show', 'store']);

        Route::post('users/{user}/approve', [ApproveUserController::class, 'approve'])->name('users.approve');
        Route::post('users/{user}/reject', [ApproveUserController::class, 'reject'])->name('users.reject');
        Route::post('users/{user}/block', [UserController::class, 'block'])->name('users.block');
    });
});

Route::get('track/{trackingId}', [ResearchPaperController::class, 'publicTracking'])->name('papers.publicTracking');

Route::get('auth/google', [SocialAuthController::class, 'redirect'])->name('auth.google');
Route::get('auth/google/callback', [SocialAuthController::class, 'callback'])->name('auth.google.callback');

require __DIR__.'/settings.php';
