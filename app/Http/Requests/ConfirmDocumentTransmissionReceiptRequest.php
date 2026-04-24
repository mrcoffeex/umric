<?php

namespace App\Http\Requests;

use App\Models\DocumentTransmission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConfirmDocumentTransmissionReceiptRequest extends FormRequest
{
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
        ];
    }
}
