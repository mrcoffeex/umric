<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AgendaController;
use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\ApproveUserController;
use App\Http\Controllers\Admin\DefenseCalendarController as AdminDefenseCalendarController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\EvaluationCriteriaController;
use App\Http\Controllers\Admin\ProgramController;
use App\Http\Controllers\Admin\ResearchController;
use App\Http\Controllers\Admin\SchoolClassController;
use App\Http\Controllers\Admin\SdgController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentTransmissionController;
use App\Http\Controllers\Faculty\AllResearchController;
use App\Http\Controllers\Faculty\ClassJoinController;
use App\Http\Controllers\Faculty\DefenseCalendarController as FacultyDefenseCalendarController;
use App\Http\Controllers\PanelDefenseEvaluationController;
use App\Http\Controllers\ResearchPaperController;
use App\Http\Controllers\Student\ClassController;
use App\Http\Controllers\Student\DefenseCalendarController as StudentDefenseCalendarController;
use App\Http\Controllers\Student\HomeController;
use App\Models\Category;
use App\Models\Department;
use App\Models\ResearchPaper;
use App\Models\User;
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
        'stats' => [
            'papers' => ResearchPaper::count(),
            'students' => User::whereHas('profile', fn ($q) => $q->where('role', 'student'))->count(),
            'departments' => Department::count(),
        ],
    ]);
})->name('home');

Route::get('/documentation', function () {
    return Inertia::render('Documentation');
})->name('documentation');

Route::get('/faq', function () {
    return Inertia::render('Faq');
})->name('faq');

Route::get('/registration-pending', function () {
    return Inertia::render('auth/RegistrationPending');
})->name('registration.pending')->middleware('guest');

Route::middleware(['auth', 'verified', 'approved'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('papers/proponents/search', [ResearchPaperController::class, 'searchProponents'])
        ->name('papers.proponents.search');
    Route::resource('papers', ResearchPaperController::class);
    Route::resource('categories', CategoryController::class);

    Route::post('papers/{paper}/files', [ResearchPaperController::class, 'storeFile'])->name('papers.storeFile');
    Route::get('papers/{paper}/qr', [ResearchPaperController::class, 'generateQR'])->name('papers.qr');

    Route::prefix('document-transmissions')->name('document-transmissions.')->group(function () {
        Route::get('/', [DocumentTransmissionController::class, 'index'])->name('index');
        Route::get('/create', [DocumentTransmissionController::class, 'create'])->name('create');
        Route::post('/', [DocumentTransmissionController::class, 'store'])->name('store');
        Route::get('/recipients/search', [DocumentTransmissionController::class, 'searchRecipients'])->name('recipients.search');
        Route::get('/claim/{token}', [DocumentTransmissionController::class, 'claim'])->name('claim');
        Route::get('/{transmission}/items/{item}/file', [DocumentTransmissionController::class, 'downloadItemFile'])->name('items.file');
        Route::post('/{transmission}/receive', [DocumentTransmissionController::class, 'receive'])->name('receive');
        Route::get('/{transmission}/forward', [DocumentTransmissionController::class, 'createForward'])->name('forward.create');
        Route::post('/{transmission}/forward', [DocumentTransmissionController::class, 'storeForward'])->name('forward.store');
        Route::get('/{transmission}', [DocumentTransmissionController::class, 'show'])->name('show');
    });

    // Admin routes (admin + staff)
    Route::middleware(['can:accessAdmin,App\Models\User', 'admin-actions-throttle'])->prefix('admin')->name('admin.')->group(function () {
        Route::resource('departments', DepartmentController::class)->except(['create', 'edit', 'show']);
        Route::resource('programs', ProgramController::class)->except(['create', 'edit', 'show', 'index']);
        Route::resource('subjects', SubjectController::class)->except(['create', 'edit', 'show']);
        Route::resource('classes', SchoolClassController::class)->except(['create', 'edit']);
        Route::post('classes/{class}/join-code', [SchoolClassController::class, 'generateJoinCode'])->name('classes.generate-join-code');
        Route::delete('classes/{class}/join-code', [SchoolClassController::class, 'revokeJoinCode'])->name('classes.revoke-join-code');
        Route::delete('classes/{class}/students/{student}', [SchoolClassController::class, 'removeStudent'])->name('classes.remove-student');
        Route::resource('sdgs', SdgController::class)->except(['create', 'edit', 'show']);
        Route::resource('agendas', AgendaController::class)->except(['create', 'edit', 'show']);
        // Admin Research
        Route::get('research', [ResearchController::class, 'index'])->name('research.index');
        Route::get('research/{paper}', [ResearchController::class, 'show'])->name('research.show');
        Route::patch('research/{paper}/step', [ResearchController::class, 'updateStep'])->name('research.update-step');
        Route::post('research/{paper}/assign', [ResearchController::class, 'assign'])->name('research.assign');
        Route::post('research/{paper}/comments', [ResearchController::class, 'storeComment'])->name('research.store-comment');
        Route::get('research/{paper}/receive', [ResearchController::class, 'receive'])->name('research.receive');
        Route::post('research/{paper}/panel-defenses', [ResearchController::class, 'storePanelDefense'])->name('research.panel-defenses.store');
        Route::delete('research/{paper}/panel-defenses/{panelDefense}', [ResearchController::class, 'destroyPanelDefense'])->name('research.panel-defenses.destroy');

        // Admin Announcements
        Route::resource('announcements', AnnouncementController::class)->except(['create', 'edit', 'show']);

        Route::post('users/{user}/approve', [ApproveUserController::class, 'approve'])->name('users.approve');
        Route::post('users/{user}/reject', [ApproveUserController::class, 'reject'])->name('users.reject');

        // Admin-only: user management
        Route::middleware('can:adminOnly,App\Models\User')->group(function () {
            Route::resource('users', UserController::class)->except(['edit', 'show']);
            Route::post('users/{user}/block', [UserController::class, 'block'])->name('users.block');
        });

        // Admin Defense Calendar
        Route::get('defense-calendar', [AdminDefenseCalendarController::class, 'index'])->name('defense-calendar.index');

        Route::get('evaluation', [PanelDefenseEvaluationController::class, 'index'])->name('evaluation.index');
        Route::get('evaluation/evaluations/{panelDefenseEvaluation}/edit', [PanelDefenseEvaluationController::class, 'edit'])->name('evaluation.edit');
        Route::get('evaluation/{panelDefense}/evaluate', [PanelDefenseEvaluationController::class, 'evaluate'])->name('evaluation.evaluate');
        Route::post('evaluation/{panelDefense}', [PanelDefenseEvaluationController::class, 'store'])->name('evaluation.store');
        Route::patch('evaluation/evaluations/{panelDefenseEvaluation}', [PanelDefenseEvaluationController::class, 'update'])->name('evaluation.update');

        Route::resource('evaluation-criteria', EvaluationCriteriaController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->parameters(['evaluation-criteria' => 'evaluation_criterion']);

        // Admin Activity Log
        Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
    });

    // Faculty class management
    Route::middleware('role:faculty')->prefix('faculty')->name('faculty.')->group(function () {
        Route::get('classes', [App\Http\Controllers\Faculty\SchoolClassController::class, 'index'])->name('classes.index');
        Route::post('classes', [App\Http\Controllers\Faculty\SchoolClassController::class, 'store'])->name('classes.store');
        Route::get('classes/{class}', [App\Http\Controllers\Faculty\SchoolClassController::class, 'show'])->name('classes.show');
        Route::patch('classes/{class}', [App\Http\Controllers\Faculty\SchoolClassController::class, 'update'])->name('classes.update');
        Route::delete('classes/{class}', [App\Http\Controllers\Faculty\SchoolClassController::class, 'destroy'])->name('classes.destroy');
        Route::post('classes/{class}/join-code', [App\Http\Controllers\Faculty\SchoolClassController::class, 'generateJoinCode'])->name('classes.generate-join-code');
        Route::delete('classes/{class}/join-code', [App\Http\Controllers\Faculty\SchoolClassController::class, 'revokeJoinCode'])->name('classes.revoke-join-code');
        Route::delete('classes/{class}/students/{student}', [App\Http\Controllers\Faculty\SchoolClassController::class, 'removeStudent'])->name('classes.remove-student');

        // Faculty Research
        Route::get('research', [AllResearchController::class, 'index'])->name('research.index');
        Route::get('research/{paper}', [AllResearchController::class, 'show'])->name('research.show');
        Route::get('classes/{class}/research', [App\Http\Controllers\Faculty\ResearchController::class, 'index'])->name('classes.research.index');
        Route::get('classes/{class}/research/{paper}', [App\Http\Controllers\Faculty\ResearchController::class, 'show'])->name('classes.research.show');
        Route::post('classes/{class}/research/{paper}/comments', [App\Http\Controllers\Faculty\ResearchController::class, 'storeComment'])->name('classes.research.store-comment');
        Route::patch('classes/{class}/research/{paper}/step', [App\Http\Controllers\Faculty\ResearchController::class, 'updateStep'])->name('classes.research.update-step');
        Route::post('classes/{class}/research/{paper}/approve', [App\Http\Controllers\Faculty\ResearchController::class, 'approve'])->name('classes.research.approve');

        // Faculty Defense Calendar
        Route::get('defense-calendar', [FacultyDefenseCalendarController::class, 'index'])->name('defense-calendar.index');

        Route::get('evaluation', [PanelDefenseEvaluationController::class, 'index'])->name('evaluation.index');
        Route::get('evaluation/{panelDefense}/evaluate', [PanelDefenseEvaluationController::class, 'evaluate'])->name('evaluation.evaluate');
        Route::post('evaluation/{panelDefense}', [PanelDefenseEvaluationController::class, 'store'])->name('evaluation.store');
    });

    Route::middleware(['auth', 'verified', 'role:student'])->prefix('student')->group(function () {
        Route::get('home', HomeController::class)->name('student.home');
        Route::get('research', [App\Http\Controllers\Student\ResearchController::class, 'index'])->name('student.research.index');
        Route::get('research/create', [App\Http\Controllers\Student\ResearchController::class, 'create'])->name('student.research.create');
        Route::post('research/extract-metadata', [App\Http\Controllers\Student\ResearchController::class, 'extractMetadata'])->name('student.research.extract-metadata');
        Route::post('research', [App\Http\Controllers\Student\ResearchController::class, 'store'])->name('student.research.store');
        Route::get('research/{paper}', [App\Http\Controllers\Student\ResearchController::class, 'show'])->name('student.research.show');
        Route::get('research/{paper}/edit', [App\Http\Controllers\Student\ResearchController::class, 'edit'])->name('student.research.edit');
        Route::put('research/{paper}', [App\Http\Controllers\Student\ResearchController::class, 'update'])->name('student.research.update');
        Route::delete('research/{paper}', [App\Http\Controllers\Student\ResearchController::class, 'destroy'])->name('student.research.destroy');
        Route::get('classes', [ClassController::class, 'index'])->name('student.classes.index');
        Route::get('classes/{class}', [ClassController::class, 'show'])->name('student.classes.show');
        Route::get('defense-calendar', [StudentDefenseCalendarController::class, 'index'])->name('student.defense-calendar.index');
    });

    // Student class join (students only)
    Route::middleware('role:student')->group(function () {
        Route::get('classes/join/{code}', [ClassJoinController::class, 'show'])->name('classes.join.show');
        Route::post('classes/join/{code}', [ClassJoinController::class, 'store'])->name('classes.join.store');
    });
});

Route::get('track/{trackingId}', [ResearchPaperController::class, 'publicTracking'])->name('papers.publicTracking');

Route::get('auth/google', [SocialAuthController::class, 'redirect'])->name('auth.google');
Route::get('auth/google/callback', [SocialAuthController::class, 'callback'])->name('auth.google.callback');

require __DIR__.'/settings.php';
