<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    protected function adminPageSize(Request $request): int
    {
        $allowed = [10, 25, 50, 100];

        if ($request->filled('per_page')) {
            $perPage = $request->integer('per_page');

            if (in_array($perPage, $allowed, true)) {
                $request->session()->put('admin_per_page', $perPage);

                return $perPage;
            }
        }

        $perPage = (int) $request->session()->get('admin_per_page', 10);

        return in_array($perPage, $allowed, true) ? $perPage : 10;
    }
}
