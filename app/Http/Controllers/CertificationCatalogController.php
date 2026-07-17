<?php

namespace App\Http\Controllers;

use App\Models\Certification;
use Illuminate\View\View;

class CertificationCatalogController extends Controller
{
    /**
     * Display public certification tracks.
     */
    public function index(): View
    {
        $certifications = Certification::query()
            ->with([
                'activePackages' => fn ($query) => $query
                    ->orderBy('sort_order')
                    ->orderBy('name'),
            ])
            ->withCount(['activePackages as packages_count'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('certifications.index', compact('certifications'));
    }

    /**
     * Display public package options for a certification.
     */
    public function show(Certification $certification): View
    {
        abort_unless($certification->is_active, 404);

        $certification->load([
            'activePackages' => fn ($query) => $query->orderBy('sort_order')->orderBy('name'),
        ]);

        return view('certifications.show', compact('certification'));
    }
}
