<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBrandingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->can('accessAdmin', User::class);
    }

    protected function prepareForValidation(): void
    {
        $name = $this->input('site_name');
        if (is_array($name)) {
            $name = null;
        } elseif (is_string($name)) {
            $name = trim($name);
        } elseif (is_scalar($name) && $name !== null) {
            $name = trim((string) $name);
        }

        $tagline = $this->input('tagline');
        if (is_array($tagline)) {
            $tagline = null;
        } elseif (is_string($tagline)) {
            $t = trim($tagline);
            $tagline = $t === '' ? null : $t;
        } elseif ($tagline === '') {
            $tagline = null;
        }

        $this->merge([
            'site_name' => $name,
            'tagline' => $tagline,
        ]);
    }

    /**
     * @return array<string, array<int, \Closure|string|object>>
     */
    public function rules(): array
    {
        return [
            'site_name' => ['bail', 'required', 'string', 'max:100'],
            'tagline' => ['nullable', 'string', 'max:500'],
            'logo' => ['nullable', 'file', 'max:2048', 'mimes:jpeg,jpg,png,webp,svg'],
            'remove_logo' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'site_name' => 'site name',
            'tagline' => 'tagline',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'site_name.required' => 'Enter a site name.',
            'site_name.max' => 'The site name may not be longer than 100 characters.',
            'tagline.max' => 'The tagline may not be longer than 500 characters.',
        ];
    }
}
