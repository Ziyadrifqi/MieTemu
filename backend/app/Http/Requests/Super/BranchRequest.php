<?php

namespace App\Http\Requests\Super;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class BranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dijaga middleware EnsureSuperAdmin
    }

    public function rules(): array
    {
        $branch = $this->route('branch');
        $businessId = $branch?->business_id ?? $this->route('business')->id;

        return [
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('branches', 'name')->where('business_id', $businessId)->ignore($branch?->id),
            ],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]+$/'],
            'hours' => ['sometimes', 'array'],
            'hours.*.open_time' => ['nullable', 'date_format:H:i'],
            'hours.*.close_time' => ['nullable', 'date_format:H:i'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama cabang wajib diisi.',
            'name.unique' => 'Nama cabang ini sudah dipakai di restoran ini.',
            'phone.regex' => 'Nomor telepon hanya boleh berisi angka, spasi, +, -, dan tanda kurung.',
            'hours.*.open_time.date_format' => 'Format jam buka harus JJ:MM.',
            'hours.*.close_time.date_format' => 'Format jam tutup harus JJ:MM.',
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            foreach ($this->input('hours', []) as $day => $row) {
                if (! empty($row['is_closed'])) {
                    continue;
                }

                $label = self::DAYS[(int) $day] ?? "Hari {$day}";
                $open = $row['open_time'] ?? null;
                $close = $row['close_time'] ?? null;

                if (! $open || ! $close) {
                    $validator->errors()->add("hours.{$day}.open_time", "{$label}: isi jam buka dan tutup, atau tandai libur.");
                } elseif ($close <= $open) {
                    $validator->errors()->add("hours.{$day}.close_time", "{$label}: jam tutup harus setelah jam buka.");
                }
            }
        }];
    }

    public const DAYS = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];

    /** Data kolom cabang. */
    public function branchData(): array
    {
        $data = $this->validated();

        return [
            'name' => trim($data['name']),
            'address' => $data['address'] ?? null,
            'phone' => $data['phone'] ?? null,
            'accepts_queue' => $this->has('accepts_queue') ? $this->boolean('accepts_queue') : true,
        ];
    }

    /** Jam operasional per hari, atau null bila form tidak mengirimnya. */
    public function hoursData(): ?array
    {
        if (! $this->has('hours')) {
            return null;
        }

        $hours = [];
        foreach ($this->validated()['hours'] ?? [] as $day => $row) {
            $hours[(int) $day] = [
                'open_time' => $row['open_time'] ?? null,
                'close_time' => $row['close_time'] ?? null,
                'is_closed' => (bool) ($this->input("hours.{$day}.is_closed")),
            ];
        }

        return $hours;
    }
}
