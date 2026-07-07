<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Certification;
use App\Models\CertificationPackage;
use App\Models\Exam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CertificationPackageController extends Controller
{
    /**
     * Show the form for creating a certification package.
     */
    public function create(Certification $certification): View
    {
        return view('admin.certification-packages.create', [
            'certification' => $certification,
            'package' => null,
            'exams' => $this->activeExams(),
        ]);
    }

    /**
     * Store a newly created certification package.
     */
    public function store(Request $request, Certification $certification): RedirectResponse
    {
        $data = $this->validatedPackageData($request, $certification);
        $data['package']['certification_id'] = $certification->id;
        $data['package']['slug'] = $this->uniqueSlug($certification, $data['package']['name']);

        DB::transaction(function () use ($data): void {
            $package = CertificationPackage::create($data['package']);
            $package->exams()->sync($data['exam_ids']);
        });

        return redirect()
            ->route('admin.certifications.show', $certification)
            ->with('status', 'Package created successfully.');
    }

    /**
     * Show the form for editing a certification package.
     */
    public function edit(CertificationPackage $certificationPackage): View
    {
        $certificationPackage->load('certification', 'exams');

        return view('admin.certification-packages.edit', [
            'certification' => $certificationPackage->certification,
            'package' => $certificationPackage,
            'exams' => $this->activeExams($certificationPackage),
        ]);
    }

    /**
     * Update the specified certification package.
     */
    public function update(Request $request, CertificationPackage $certificationPackage): RedirectResponse
    {
        $certificationPackage->load('certification');

        $data = $this->validatedPackageData($request, $certificationPackage->certification, $certificationPackage);
        $data['package']['slug'] = $this->uniqueSlug(
            $certificationPackage->certification,
            $data['package']['name'],
            $certificationPackage,
        );

        DB::transaction(function () use ($certificationPackage, $data): void {
            $certificationPackage->update($data['package']);
            $certificationPackage->exams()->sync($data['exam_ids']);
        });

        return redirect()
            ->route('admin.certifications.show', $certificationPackage->certification)
            ->with('status', 'Package updated successfully.');
    }

    /**
     * Deactivate the specified certification package.
     */
    public function destroy(CertificationPackage $certificationPackage): RedirectResponse
    {
        $certificationPackage->update(['is_active' => false]);

        return back()->with('status', 'Package deactivated successfully.');
    }

    /**
     * Permanently delete the specified certification package.
     */
    public function permanentDestroy(CertificationPackage $certificationPackage): RedirectResponse
    {
        $certificationPackage->delete();

        return redirect()
            ->route('admin.certifications.show', $certificationPackage->certification_id)
            ->with('status', 'Package permanently deleted successfully.');
    }

    /**
     * @return array{package: array<string, mixed>, exam_ids: array<int, int>}
     */
    private function validatedPackageData(
        Request $request,
        Certification $certification,
        ?CertificationPackage $package = null,
    ): array {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'badge' => ['nullable', 'string', 'max:80'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'exam_ids' => ['nullable', 'array'],
            'exam_ids.*' => [
                'integer',
                Rule::exists('exams', 'id')->where('is_active', true),
            ],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        return [
            'package' => [
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'price' => $validated['price'],
                'duration_days' => $validated['duration_days'],
                'badge' => $validated['badge'] ?? null,
                'sort_order' => $validated['sort_order'] ?? 0,
                'is_active' => $request->boolean('is_active'),
            ],
            'exam_ids' => array_values(array_unique(array_map('intval', $validated['exam_ids'] ?? []))),
        ];
    }

    private function activeExams(?CertificationPackage $package = null)
    {
        return Exam::query()
            ->where(function ($query) use ($package): void {
                $query->where('is_active', true);

                if ($package) {
                    $query->orWhereIn('id', $package->exams->pluck('id'));
                }
            })
            ->orderBy('title')
            ->get();
    }

    private function uniqueSlug(
        Certification $certification,
        string $name,
        ?CertificationPackage $package = null,
    ): string {
        $baseSlug = Str::slug($name) ?: 'package';
        $slug = $baseSlug;
        $counter = 2;

        while (
            CertificationPackage::query()
                ->where('certification_id', $certification->id)
                ->where('slug', $slug)
                ->when($package, fn ($query) => $query->whereKeyNot($package->id))
                ->exists()
        ) {
            $slug = $baseSlug.'-'.$counter++;
        }

        return $slug;
    }
}
