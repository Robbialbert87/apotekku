<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('setting.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'apotek_name' => ['required', 'string', 'max:150'],
            'apotek_address' => ['nullable', 'string', 'max:1000'],
            'apotek_phone' => ['nullable', 'string', 'max:30'],
            'apotek_email' => ['nullable', 'email', 'max:150'],
            'invoice_due_days' => ['required', 'integer', 'min:1', 'max:365'],
            'report_footer' => ['nullable', 'string', 'max:255'],
        ];
    }
}
