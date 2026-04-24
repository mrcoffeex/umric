<?php

namespace App\Http\Requests;

use App\Models\DocumentTransmission;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreDocumentForwardRequest extends FormRequest
{
    public function authorize(): bool
    {
        $transmission = $this->route('transmission');

        return $transmission instanceof DocumentTransmission
            && $this->user() !== null
            && $this->user()->can('forward', $transmission);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var DocumentTransmission $transmission */
        $transmission = $this->route('transmission');

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
            'item_ids' => ['required', 'array', 'min:1', 'max:100'],
            'item_ids.*' => [
                'required',
                'string',
                Rule::exists('document_transmission_items', 'id')
                    ->where('document_transmission_id', $transmission->id),
            ],
        ];
    }

    public function purposeNormalized(): string
    {
        return trim($this->validated('purpose'));
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

                $transmission = $this->route('transmission');
                if (! $transmission instanceof DocumentTransmission) {
                    return;
                }

                $transmission->load(['items' => fn ($q) => $q->orderBy('sort_order')]);
                $selectedIds = collect($this->validated('item_ids'))->unique();
                $labels = $transmission->items
                    ->filter(fn ($item) => $selectedIds->contains($item->id))
                    ->sortBy('sort_order')
                    ->pluck('label')
                    ->values()
                    ->all();

                if ($labels === []) {
                    $validator->errors()->add(
                        'item_ids',
                        'Select at least one document to forward.',
                    );

                    return;
                }

                if (DocumentTransmission::findPendingDuplicate(
                    (string) $this->user()->id,
                    $this->validated('receiver_id'),
                    $labels,
                ) !== null) {
                    $validator->errors()->add(
                        'receiver_id',
                        'You already have a pending handoff to this person with the same set of document titles. Choose another recipient or complete the existing handoff first.',
                    );
                }
            },
        ];
    }
}
