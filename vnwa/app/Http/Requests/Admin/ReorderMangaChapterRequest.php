<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ReorderMangaChapterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'chapters' => ['required', 'array'],
            'chapters.*.id' => ['required', 'exists:manga_chapters,id'],
            'chapters.*.order' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'chapters.required' => 'Danh sách chapters là bắt buộc.',
            'chapters.array' => 'Danh sách chapters phải là mảng.',
            'chapters.*.id.required' => 'ID chapter là bắt buộc.',
            'chapters.*.id.exists' => 'Chapter không tồn tại.',
            'chapters.*.order.required' => 'Thứ tự là bắt buộc.',
            'chapters.*.order.integer' => 'Thứ tự phải là số nguyên.',
            'chapters.*.order.min' => 'Thứ tự phải lớn hơn hoặc bằng 0.',
        ];
    }
}
