<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value'];

    protected $casts = [
        'value' => 'array',
    ];


    /**
     * Lấy setting từ cache
     */
    public static function getValue(string $key, $default = [])
    {
        return Cache::rememberForever("setting_{$key}", function () use ($key, $default) {
            $record = static::where('key', $key)->first();
            return $record ? $record->value : $default;
        });
    }

    /**
     * Cập nhật setting và cache lại
     */
    public static function setValue(string $key, $value)
    {
        $record = static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        Cache::forever("setting_{$key}", $value);

        return $record;
    }

    /**
     * Khi xóa thì cũng clear cache
     */
    protected static function booted()
    {
        static::updated(function ($setting) {
            Cache::forever("setting_{$setting->key}", $setting->value);
        });

        static::deleted(function ($setting) {
            Cache::forget("setting_{$setting->key}");
        });
    }
}
