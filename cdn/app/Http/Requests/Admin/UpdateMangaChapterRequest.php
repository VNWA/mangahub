<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMangaChapterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $chapterId = $this->route('chapter');

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('manga_chapters')->ignore($chapterId)],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Tên chapter là bắt buộc.',
            'name.string' => 'Tên chapter phải là chuỗi.',
            'name.max' => 'Tên chapter không được vượt quá 255 ký tự.',
            'slug.unique' => 'Slug đã tồn tại.',
        ];
    }
}
