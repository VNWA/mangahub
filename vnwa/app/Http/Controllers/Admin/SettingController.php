<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingController extends Controller
{
    public function index()
    {
        return redirect()->route('config.settings.file', 'appearance');
    }
    public function file($key)
    {
        return Inertia::render('admin/setting/pages/'.$key, [
            'key' => $key,
        ]);
    }
    public function loadData($key, Request $request)
    {
        $data = Setting::getValue($key); // mặc định trả mảng rỗng nếu chưa tồn tại

        return response()->json([
            'data' => $data,
            'message' => 'success',
        ]);
    }

    public function update(Request $request, $key)
    {
        $data = $request->except(['_token', '_method', 'locale']);

        Setting::setValue($key, $data); // lưu + refresh cache

        return response()->json([
            'message' => 'Update Setting Success',
            'key' => $key,
            'data' => $data
        ]);
    }
    public function updateAppearance(Request $request)
    {


        return response()->json([
            'message' => 'Update Appearance Success',
        ]);
    }
}
