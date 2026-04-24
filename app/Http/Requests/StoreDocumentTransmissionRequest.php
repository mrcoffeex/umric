<?php

namespace App\Http\Requests;

use App\Models\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

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
            'items.*.label' => ['required', 'string', 'max:500'],
            'items.*.file' => [
                'nullable',
                'file',
                'mimes:pdf',
                'max:'.config('uploads.max_size_kb'),
            ],
        ];
    }

    /**
     * @return array{receiver_id: string, purpose: string, items: list<array{label: string, file: ?UploadedFile}>}
     */
    public function normalized(): array
    {
        $validated = $this->validated();
        /** @var array<int, array{label: string, file?: UploadedFile|null}> $raw */
        $raw = $validated['items'];
        $items = collect($raw)
            ->map(function (array $row) {
                return [
                    'label' => trim($row['label']),
                    'file' => $row['file'] ?? null,
                ];
            })
            ->filter(fn (array $row) => $row['label'] !== '')
            ->values()
            ->all();

        return [
            'receiver_id' => $validated['receiver_id'],
            'purpose' => trim($validated['purpose']),
            'items' => $items,
        ];
    }
}
