<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreMangaChapterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'manga_id' => ['required', 'exists:mangas,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'manga_id.required' => 'Manga là bắt buộc.',
            'manga_id.exists' => 'Manga không tồn tại.',
            'name.required' => 'Tên chapter là bắt buộc.',
            'name.string' => 'Tên chapter phải là chuỗi.',
            'name.max' => 'Tên chapter không được vượt quá 255 ký tự.',
        ];
    }
}
