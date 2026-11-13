<?php

namespace App\Http\Requests;

use App\Enums\MonitorType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MonitorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $type = $this->input('type');

        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'expected_keyword' => $this->filled('expected_keyword') ? $this->input('expected_keyword') : null,
            'expected_status' => $this->filled('expected_status') ? $this->input('expected_status') : null,
            'notes' => $this->filled('notes') ? $this->input('notes') : null,
        ]);

        if ($type === MonitorType::Port->value) {
            $this->merge([
                'url' => null,
                'method' => null,
                'expected_status' => null,
                'expected_keyword' => null,
            ]);
        } else {
            $this->merge([
                'host' => null,
                'port' => null,
                'method' => strtoupper((string) ($this->input('method') ?: 'GET')),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $isPort = $this->input('type') === MonitorType::Port->value;
        $needsUrl = in_array($this->input('type'), [MonitorType::Http->value, MonitorType::Service->value], true);

        return [
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::enum(MonitorType::class)],
            'server_id' => [
                'nullable',
                'integer',
                Rule::exists('servers', 'id')->where(fn ($query) => $query->where('user_id', $this->user()->id)),
            ],
            'url' => [Rule::requiredIf($needsUrl), 'nullable', 'url:http,https', 'max:2048'],
            'host' => [Rule::requiredIf($isPort), 'nullable', 'string', 'max:255'],
            'port' => [Rule::requiredIf($isPort), 'nullable', 'integer', 'between:1,65535'],
            'method' => ['nullable', Rule::in(['GET', 'HEAD'])],
            'expected_status' => ['nullable', 'integer', 'between:100,599'],
            'expected_keyword' => ['nullable', 'string', 'max:255'],
            'interval_seconds' => ['required', 'integer', 'min:60', 'max:86400'],
            'timeout_seconds' => ['required', 'integer', 'min:1', 'max:60'],
            'confirmation_threshold' => ['required', 'integer', 'min:1', 'max:10'],
            'recovery_threshold' => ['required', 'integer', 'min:1', 'max:10'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'url.required' => 'URL wajib diisi untuk monitor website atau layanan.',
            'url.url' => 'URL harus diawali http:// atau https://.',
            'host.required' => 'Host wajib diisi untuk monitor port.',
            'port.required' => 'Port wajib diisi untuk monitor port.',
            'interval_seconds.min' => 'Interval minimal 60 detik.',
            'timeout_seconds.max' => 'Batas waktu maksimal 60 detik.',
        ];
    }
}
