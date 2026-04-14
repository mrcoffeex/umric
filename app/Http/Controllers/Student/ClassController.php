<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClassController extends Controller
{
    public function index(Request $request): Response
    {
        if (! $request->user()->isStudent()) {
            abort(403);
        }

        $classes = SchoolClass::query()
            ->whereHas('members', fn ($query) => $query->where('student_id', $request->user()->id))
            ->with(['subjects.program', 'faculty', 'researchPapers' => fn ($q) => $q->where('user_id', $request->user()->id)])
            ->get();

        return Inertia::render('student/Classes/Index', [
            'classes' => $classes,
        ]);
    }
}
