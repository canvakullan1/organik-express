<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Certificate;

class CertificateController extends Controller
{
    public function index()
    {
        return view('storefront.certificates', [
            'certificates' => Certificate::active()->orderBy('sort_order')->get(),
        ]);
    }
}
