<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $diagnosticos = $request->user()
            ->diagnosticos()
            ->latest()
            ->limit(20)
            ->get();

        return view('dashboard', [
            'diagnosticos' => $diagnosticos,
        ]);
    }
}
