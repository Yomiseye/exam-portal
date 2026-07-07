<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Certification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CertificationController extends Controller
{
    /**
     * Display a listing of certifications.
     */
    public function index(Request $request): View
    {
        $certifications = Certification::query()
            ->withCount('packages')
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = '%'.$request->string('search')->trim()->toString().'%';

                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', $search)
                        ->orWhere('description', 'like', $search);
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->string('status') === 'active'))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('admin.certifications.index', compact('certifications'));
    }

    /**
     * Show the form for creating a certification.
     */
    public function create(): View
    {
        return view('admin.certifications.create', [
            'certification' => null,
        ]);
    }

    /**
     * Store a newly created certification.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedCertificationData($request);
        $data['slug'] = $this->uniqueSlug($data['name']);

        $certification = Certification::create($data);
        $this->syncImage($request, $certification);

        return redirect()
            ->route('admin.certifications.show', $certification)
            ->with('status', 'Certification created successfully.');
    }

    /**
     * Display certification packages.
     */
    public function show(Certification $certification): View
    {
        $certification->load(['packages.exams' => fn ($query) => $query->orderBy('title')]);

        return view('admin.certifications.show', compact('certification'));
    }

    /**
     * Show the form for editing a certification.
     */
    public function edit(Certification $certification): View
    {
        return view('admin.certifications.edit', compact('certification'));
    }

    /**
     * Update the specified certification.
     */
    public function update(Request $request, Certification $certification): RedirectResponse
    {
        $data = $this->validatedCertificationData($request, $certification);
        $data['slug'] = $this->uniqueSlug($data['name'], $certification);

        $certification->update($data);
        $this->syncImage($request, $certification);

        return redirect()
            ->route('admin.certifications.show', $certification)
            ->with('status', 'Certification updated successfully.');
    }

    /**
     * Deactivate the specified certification.
     */
    public function destroy(Certification $certification): RedirectResponse
    {
        $certification->update(['is_active' => false]);

        return redirect()
            ->route('admin.certifications.index')
            ->with('status', 'Certification deactivated successfully.');
    }

    /**
     * Permanently delete a certification when no packages depend on it.
     */
    public function permanentDestroy(Certification $certification): RedirectResponse
    {
        if ($certification->packages()->exists()) {
            return back()->withErrors([
                'certification' => 'This certification has packages attached. Delete or deactivate the packages first.',
            ]);
        }

        DB::transaction(function () use ($certification): void {
            $this->deleteImage($certification);
            $certification->delete();
        });

        return redirect()
            ->route('admin.certifications.index')
            ->with('status', 'Certification permanently deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedCertificationData(Request $request, ?Certification $certification = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('certifications', 'name')->ignore($certification),
            ],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_image' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]) + ['is_active' => false];
    }

    private function syncImage(Request $request, Certification $certification): void
    {
        if ($request->boolean('remove_image')) {
            $this->deleteImage($certification);
            $certification->update(['image_path' => null]);
        }

        if (! $request->hasFile('image')) {
            return;
        }

        $this->deleteImage($certification);

        $certification->update([
            'image_path' => $request->file('image')->store('certification-images', 'public'),
        ]);
    }

    private function deleteImage(Certification $certification): void
    {
        if ($certification->image_path) {
            Storage::disk('public')->delete($certification->image_path);
        }
    }

    private function uniqueSlug(string $name, ?Certification $certification = null): string
    {
        $baseSlug = Str::slug($name) ?: 'certification';
        $slug = $baseSlug;
        $counter = 2;

        while (
            Certification::query()
                ->where('slug', $slug)
                ->when($certification, fn ($query) => $query->whereKeyNot($certification->id))
                ->exists()
        ) {
            $slug = $baseSlug.'-'.$counter++;
        }

        return $slug;
    }
}
