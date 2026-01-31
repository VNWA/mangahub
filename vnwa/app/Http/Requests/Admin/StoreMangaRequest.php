<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMangaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:mangas,slug'],
            'description' => ['nullable', 'string'],
            'manga_author_id' => ['nullable', 'exists:manga_authors,id'],
            'manga_badge_id' => ['nullable', 'exists:manga_badges,id'],
            'status' => ['required', 'in:ongoing,completed,hiatus,cancelled'],
            'avatar' => ['nullable', 'image', 'max:2048'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['exists:manga_categories,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Tên manga là bắt buộc.',
            'name.string' => 'Tên manga phải là chuỗi.',
            'name.max' => 'Tên manga không được vượt quá 255 ký tự.',
            'slug.unique' => 'Slug đã tồn tại.',
            'manga_author_id.exists' => 'Tác giả không tồn tại.',
            'manga_badge_id.exists' => 'Badge không tồn tại.',
            'status.required' => 'Trạng thái là bắt buộc.',
            'status.in' => 'Trạng thái không hợp lệ.',
            'avatar.image' => 'Avatar phải là file ảnh.',
            'avatar.max' => 'Avatar không được vượt quá 2MB.',
            'categories.*.exists' => 'Category không tồn tại.',
        ];
    }
}
