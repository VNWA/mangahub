<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UploadZipMangaChapterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'manga_id' => ['required', 'exists:mangas,id'],
            'zip_file' => ['required', 'file', 'mimes:zip', 'max:102400'], // 100MB max
        ];
    }

    public function messages(): array
    {
        return [
            'manga_id.required' => 'Manga là bắt buộc.',
            'manga_id.exists' => 'Manga không tồn tại.',
            'zip_file.required' => 'File ZIP là bắt buộc.',
            'zip_file.file' => 'Phải là file hợp lệ.',
            'zip_file.mimes' => 'File phải có định dạng ZIP.',
            'zip_file.max' => 'File không được vượt quá 100MB.',
        ];
    }
}
