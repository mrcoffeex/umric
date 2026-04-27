<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EvaluationCriterion;
use App\Models\EvaluationFormat;
use App\Support\EvaluationPdfSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class EvaluationFormatController extends Controller
{
    public function index(): Response
    {
        $formats = EvaluationFormat::query()
            ->orderBy('name')
            ->withCount('panelDefenses')
            ->get()
            ->map(function (EvaluationFormat $f) {
                $total = (int) $f->criteria()->sum('max_points');
                $pdfSettings = EvaluationPdfSettings::forFormat($f);

                return [
                    'id' => (string) $f->id,
                    'name' => $f->name,
                    'evaluation_type' => $f->evaluation_type,
                    'use_weights' => (bool) $f->use_weights,
                    'can_change_type' => ! $f->criteria()->exists()
                        && (int) $f->panel_defenses_count === 0,
                    'can_change_use_weights' => $f->isScoring() && ! $f->criteria()->exists(),
                    'panel_defenses_count' => (int) $f->panel_defenses_count,
                    'total_max' => $total,
                    'target_total' => EvaluationCriterion::MAX_TOTAL,
                    'is_ready' => $f->isReady(),
                    'pdf_settings' => $pdfSettings,
                    'pdf_logo_url' => $this->pdfLogoUrl($pdfSettings),
                ];
            });

        return Inertia::render('admin/EvaluationFormats/Index', [
            'formats' => $formats,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'evaluation_type' => ['required', 'string', Rule::in(EvaluationFormat::TYPES)],
            'use_weights' => ['nullable', 'boolean'],
            'pdf_settings' => ['nullable', 'array'],
            'pdf_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
        ]);

        $type = $validated['evaluation_type'];
        $useWeights = $type === EvaluationFormat::TYPE_SCORING && $request->boolean('use_weights');
        $pdf = $request->input('pdf_settings');
        $pdf = is_array($pdf) ? $this->withUploadedPdfLogo($request, $pdf) : null;
        EvaluationFormat::query()->create([
            'name' => $validated['name'],
            'evaluation_type' => $type,
            'use_weights' => $useWeights,
            'pdf_settings' => $pdf,
        ]);

        $hint = $type === EvaluationFormat::TYPE_CHECKLIST
            ? __('Add one or more checklist items (yes / no).')
            : ($useWeights
                ? __('Add criteria so weights total 100%.')
                : __('Add criteria. Each is scored 1–100; the total is the average.'));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Evaluation format created. :hint', ['hint' => $hint]),
        ]);

        return back();
    }

    public function update(Request $request, EvaluationFormat $evaluationFormat): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'evaluation_type' => ['sometimes', 'string', Rule::in(EvaluationFormat::TYPES)],
            'use_weights' => ['nullable', 'boolean'],
            'pdf_settings' => ['nullable', 'array'],
            'pdf_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
        ]);

        $data = ['name' => $validated['name']];
        if ($request->has('pdf_settings')) {
            $pdf = $request->input('pdf_settings');
            $data['pdf_settings'] = is_array($pdf)
                ? $this->withUploadedPdfLogo($request, $pdf, $evaluationFormat)
                : null;
        }
        if (array_key_exists('evaluation_type', $validated)) {
            if (
                $evaluationFormat->criteria()->exists()
                || $evaluationFormat->panelDefenses()->exists()
            ) {
                return back()->withErrors([
                    'evaluation_type' => __('The evaluation type can only be changed when this format has no criteria and is not used on any scheduled defense.'),
                ]);
            }
            $data['evaluation_type'] = $validated['evaluation_type'];
        }
        if ($request->has('use_weights') && $evaluationFormat->isScoring()) {
            if ($evaluationFormat->criteria()->exists()) {
                return back()->withErrors([
                    'use_weights' => __('Weight mode can only be changed before any criteria are added — remove criteria first, or create a new format.'),
                ]);
            }
            $data['use_weights'] = $request->boolean('use_weights');
        }
        if (isset($data['evaluation_type']) && $data['evaluation_type'] === EvaluationFormat::TYPE_CHECKLIST) {
            $data['use_weights'] = false;
        }
        $evaluationFormat->update($data);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Evaluation format updated.'),
        ]);

        return back();
    }

    public function destroy(EvaluationFormat $evaluationFormat): RedirectResponse
    {
        if ($evaluationFormat->panelDefenses()->exists()) {
            return back()->withErrors([
                'name' => __('This format is used by one or more defense schedules. Reassign or remove those first.'),
            ]);
        }

        $evaluationFormat->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Evaluation format removed.'),
        ]);

        return back();
    }

    /**
     * @param  array<string, mixed>  $pdf
     * @return array<string, mixed>
     */
    private function withUploadedPdfLogo(Request $request, array $pdf, ?EvaluationFormat $format = null): array
    {
        if (! $request->hasFile('pdf_logo')) {
            return $pdf;
        }

        $existingSettings = is_array($format?->pdf_settings ?? null)
            ? $format?->pdf_settings
            : [];
        $oldPath = (string) ($existingSettings['logo_path'] ?? '');
        $this->deleteStoredPdfLogo($oldPath);

        $file = $request->file('pdf_logo');
        if ($file === null) {
            return $pdf;
        }

        $path = $file->store('evaluation-format-logos', 'public');
        $pdf['logo_path'] = 'storage/'.$path;

        return $pdf;
    }

    private function deleteStoredPdfLogo(string $path): void
    {
        $prefix = 'storage/evaluation-format-logos/';
        if (! str_starts_with($path, $prefix)) {
            return;
        }

        Storage::disk('public')->delete(substr($path, strlen('storage/')));
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function pdfLogoUrl(array $settings): ?string
    {
        $path = trim((string) ($settings['logo_path'] ?? ''));
        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset(ltrim($path, '/'));
    }
}
