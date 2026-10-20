<?php

namespace App\Http\Requests\Queue;

use Illuminate\Foundation\Http\FormRequest;

class StoreQueueTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'party_size' => ['required', 'integer', 'min:1', 'max:' . config('mietemu.queue.max_party_size')],
        ];
    }
}
