<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories.
     */
    public function index(): View
    {
        $categories = Category::query()
            ->with('parent')
            ->orderByRaw('parent_id is not null')
            ->orderBy('name')
            ->paginate(10);

        return view('admin.categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a category.
     */
    public function create(): View
    {
        return view('admin.categories.create', [
            'parentCategories' => $this->parentCategories(),
        ]);
    }

    /**
     * Store a newly created category.
     */
    public function store(Request $request): RedirectResponse
    {
        Category::create($this->validatedCategoryData($request));

        return redirect()
            ->route('admin.categories.index')
            ->with('status', 'Category created successfully.');
    }

    /**
     * Show the form for editing a category.
     */
    public function edit(Category $category): View
    {
        return view('admin.categories.edit', [
            'category' => $category,
            'parentCategories' => $this->parentCategories($category),
        ]);
    }

    /**
     * Update the specified category.
     */
    public function update(Request $request, Category $category): RedirectResponse
    {
        $category->update($this->validatedCategoryData($request, $category));

        return redirect()
            ->route('admin.categories.index')
            ->with('status', 'Category updated successfully.');
    }

    /**
     * Deactivate the specified category.
     */
    public function destroy(Category $category): RedirectResponse
    {
        $category->update(['is_active' => false]);

        return redirect()
            ->route('admin.categories.index')
            ->with('status', 'Category deactivated successfully.');
    }

    /**
     * Validate category form data.
     *
     * @return array<string, mixed>
     */
    private function validatedCategoryData(Request $request, ?Category $category = null): array
    {
        return $request->validate([
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where('is_active', true),
                function (string $attribute, mixed $value, \Closure $fail) use ($category): void {
                    if (! $category || ! $value) {
                        return;
                    }

                    if ((int) $value === $category->id || in_array((int) $value, $this->descendantIds($category), true)) {
                        $fail('Choose a valid parent category.');
                    }
                },
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->ignore($category),
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]) + ['is_active' => false];
    }

    private function parentCategories(?Category $category = null)
    {
        $excludedIds = $category
            ? array_merge([$category->id], $this->descendantIds($category))
            : [];

        return Category::query()
            ->with('parent')
            ->where('is_active', true)
            ->when($excludedIds !== [], fn ($query) => $query->whereNotIn('id', $excludedIds))
            ->orderByRaw('parent_id is not null')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array<int, int>
     */
    private function descendantIds(Category $category): array
    {
        $category->loadMissing('subcategories.subcategories');

        return $category->subcategories
            ->flatMap(fn (Category $subcategory) => array_merge([$subcategory->id], $this->descendantIds($subcategory)))
            ->all();
    }
}
