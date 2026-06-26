<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Categories
            </h2>
            <a href="{{ route('admin.categories.create') }}" class="portal-button-primary text-xs uppercase tracking-widest">
                <x-icon name="plus" />
                Create Category
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-md bg-green-50 p-4 text-sm text-green-700">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-md bg-red-50 p-4 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="mb-6 bg-white p-4 shadow-sm sm:rounded-lg">
                <form method="GET" action="{{ route('admin.categories.index') }}" class="grid gap-4 md:grid-cols-3">
                    <div>
                        <x-input-label for="search" value="Search" icon="search" />
                        <x-text-input
                            id="search"
                            name="search"
                            type="search"
                            class="mt-1 block w-full"
                            :value="request('search')"
                            placeholder="Category, parent, description"
                        />
                    </div>

                    <div>
                        <x-input-label for="status" value="Status" icon="filter" />
                        <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All statuses</option>
                            <option value="active" @selected(request('status') === 'active')>Active</option>
                            <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                        </select>
                    </div>

                    <div class="flex items-end gap-3">
                        <x-primary-button>
                            <x-icon name="filter" />
                            Filter
                        </x-primary-button>
                        <a href="{{ route('admin.categories.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">Reset</a>
                    </div>
                </form>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Parent</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($categories as $category)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $category->parent ? '— '.$category->name : $category->name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $category->parent?->name ?? 'Top level' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        {{ $category->description ?: 'No description' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium {{ $category->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                            <x-icon name="{{ $category->is_active ? 'check-circle' : 'x-circle' }}" class="h-3 w-3" />
                                            {{ $category->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="{{ route('admin.categories.edit', $category) }}" class="inline-flex items-center gap-1.5 text-xs text-indigo-600 hover:text-indigo-900">
                                            <x-icon name="pencil" class="h-3.5 w-3.5" />
                                            Edit
                                        </a>

                                        @if ($category->is_active)
                                            <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" class="inline ms-4">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex items-center gap-1.5 text-xs text-red-600 hover:text-red-900" onclick="return confirm('Deactivate this category?')">
                                                    <x-icon name="x-circle" class="h-3.5 w-3.5" />
                                                    Deactivate
                                                </button>
                                            </form>
                                        @endif

                                        <form method="POST" action="{{ route('admin.categories.permanent-destroy', $category) }}" class="inline ms-4">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center gap-1.5 text-xs text-red-700 hover:text-red-950" onclick="return confirm('Permanently delete this category? This only works when it has no subcategories, questions, or exams attached.')">
                                                <x-icon name="trash" class="h-3.5 w-3.5" />
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">
                                        <x-empty-state
                                            icon="tag"
                                            title="No categories yet"
                                            message="Create a category to organize questions and exams."
                                        >
                                            <a href="{{ route('admin.categories.create') }}" class="portal-button-primary text-xs uppercase tracking-widest">
                                                <x-icon name="plus" />
                                                Create Category
                                            </a>
                                        </x-empty-state>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($categories->hasPages())
                    <div class="border-t border-gray-200 px-6 py-4">
                        {{ $categories->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
