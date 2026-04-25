<?php

namespace App\Http\Requests;

use App\Models\DocumentTransmission;
use App\Rules\HandoffPngSignature;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConfirmDocumentTransmissionReceiptRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->has('embed_esignature')) {
            $this->merge(['embed_esignature' => true]);
        }
    }

    public function authorize(): bool
    {
        /** @var DocumentTransmission $transmission */
        $transmission = $this->route('transmission');

        return $this->user()?->can('receive', $transmission) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var DocumentTransmission $transmission */
        $transmission = $this->route('transmission');

        return [
            'item_ids' => ['present', 'array'],
            'item_ids.*' => [
                'bail',
                'required',
                'string',
                'distinct',
                Rule::exists('document_transmission_items', 'id')
                    ->where('document_transmission_id', $transmission->id),
            ],
            'embed_esignature' => ['boolean'],
            'signature' => [
                Rule::requiredIf(function () {
                    if (! $this->boolean('embed_esignature')) {
                        return false;
                    }

                    return ! ($this->user()?->hasAccountEsignature() ?? false);
                }),
                'nullable',
                'string',
                new HandoffPngSignature,
            ],
        ];
    }
}
