<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Storage;

class FileController extends Controller
{
    //
    public function show($path)
    {
        return Storage::url($path);
    }
}
