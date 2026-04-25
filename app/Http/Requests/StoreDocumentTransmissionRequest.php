<?php

namespace App\Http\Requests;

use App\Models\DocumentTransmission;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreDocumentTransmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'receiver_id' => [
                'required',
                'string',
                Rule::exists('users', 'id'),
                function (string $attribute, mixed $value, Closure $fail) {
                    if ((string) $value === (string) $this->user()?->id) {
                        $fail('Choose someone other than yourself.');

                        return;
                    }

                    $recipient = User::query()
                        ->whereKey($value)
                        ->whereNull('blocked_at')
                        ->whereNotNull('email_verified_at')
                        ->first();

                    if (! $recipient) {
                        $fail('The selected user cannot receive handoffs.');
                    }
                },
            ],
            'purpose' => ['required', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.file' => [
                'required',
                'file',
                'mimes:pdf',
                'max:'.config('uploads.max_size_kb'),
            ],
        ];
    }

    /**
     * @return array{receiver_id: string, purpose: string, items: list<array{label: string, file: UploadedFile}>}
     */
    public function normalized(): array
    {
        $validated = $this->validated();
        /** @var array<int, array{file: UploadedFile}> $raw */
        $raw = $validated['items'];
        $items = collect($raw)
            ->map(function (array $row) {
                $file = $row['file'] ?? null;
                if (! $file instanceof UploadedFile) {
                    return null;
                }

                $name = (string) $file->getClientOriginalName();
                $name = basename(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $name));
                $name = trim($name);
                if ($name === '') {
                    return null;
                }
                if (mb_strlen($name) > 500) {
                    $name = mb_substr($name, 0, 500);
                }

                return [
                    'label' => $name,
                    'file' => $file,
                ];
            })
            ->filter()
            ->values()
            ->all();

        return [
            'receiver_id' => $validated['receiver_id'],
            'purpose' => trim($validated['purpose']),
            'items' => $items,
        ];
    }

    /**
     * @return array<int, Closure(Validator $validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $data = $this->normalized();
                $labels = collect($data['items'])->pluck('label');
                $uniques = $labels->map(fn (string $l) => mb_strtolower($l));
                if ($uniques->count() !== $uniques->unique()->count()) {
                    $validator->errors()->add(
                        'items',
                        'Each document line must have a different title. Remove or rename duplicate lines.',
                    );

                    return;
                }

                if (DocumentTransmission::findPendingDuplicate(
                    (string) $this->user()->id,
                    $data['receiver_id'],
                    $labels->all(),
                ) !== null) {
                    $validator->errors()->add(
                        'items',
                        'You already have a pending handoff to this person with the same set of document titles. Complete or change that handoff first.',
                    );
                }
            },
        ];
    }
}
