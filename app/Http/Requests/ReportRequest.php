<?php

namespace App\Http\Requests;

use App\Enums\WasteCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'min:10', 'max:5000'],
            'category' => ['required', Rule::enum(WasteCategory::class)],
            'address' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'photo' => [$this->route('report') ? 'nullable' : 'required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120', 'dimensions:max_width=8000,max_height=8000'],
        ];
    }

    public function attributes(): array
    {
        return ['title' => 'judul', 'description' => 'deskripsi', 'photo' => 'foto', 'address' => 'alamat'];
    }
}
