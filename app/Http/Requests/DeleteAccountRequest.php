<?php

namespace App\Http\Requests;

use App\Models\LearnerProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null || LearnerProfile::completedFor(null, $this->cookie('artaswaha_device')) !== null;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return ['confirmation' => ['required', 'string', Rule::in(['Hapus Akun Saya'])]];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['confirmation.required' => 'Ketik Hapus Akun Saya untuk mengonfirmasi.', 'confirmation.in' => 'Konfirmasi harus sama dengan Hapus Akun Saya.'];
    }
}
