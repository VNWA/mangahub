<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MangaBadge;
use Illuminate\Http\Request;

class BadgeController extends Controller
{
    public function index()
    {
        $badges = MangaBadge::get(['id', 'name','slug' ]);
        return response()->json([
            'ok' => true,
            'data' => $badges,
        ]);
    }


}
