<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Certifications
            </h2>
            <a href="{{ route('admin.certifications.create') }}" class="portal-button-primary text-xs uppercase tracking-widest">
                <x-icon name="plus" />
                Create Certification
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
                <form method="GET" action="{{ route('admin.certifications.index') }}" class="grid gap-4 md:grid-cols-3">
                    <div>
                        <x-input-label for="search" value="Search" icon="search" />
                        <x-text-input
                            id="search"
                            name="search"
                            type="search"
                            class="mt-1 block w-full"
                            :value="request('search')"
                            placeholder="Certification name or description"
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
                        <button type="submit" class="portal-button-primary text-xs uppercase tracking-widest">
                            <x-icon name="filter" />
                            Filter
                        </button>
                        <a href="{{ route('admin.certifications.index') }}" class="portal-button-secondary text-xs uppercase tracking-widest">
                            <x-icon name="rotate-ccw" />
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Certification</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Packages</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse ($certifications as $certification)
                                <tr>
                                    <td class="px-6 py-4 text-sm">
                                        <div class="flex items-start gap-3">
                                            @if ($certification->imageUrl())
                                                <img src="{{ $certification->imageUrl() }}" alt="" class="h-12 w-12 rounded-md object-cover">
                                            @else
                                                <div class="flex h-12 w-12 items-center justify-center rounded-md bg-gray-100 text-gray-500">
                                                    <x-icon name="tag" />
                                                </div>
                                            @endif
                                            <div>
                                                <div class="font-semibold text-gray-900">{{ $certification->name }}</div>
                                                @if ($certification->description)
                                                    <div class="mt-1 max-w-xl text-gray-500">{{ \Illuminate\Support\Str::limit($certification->description, 120) }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $certification->packages_count }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium {{ $certification->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                            <x-icon name="{{ $certification->is_active ? 'check-circle' : 'x-circle' }}" class="h-3 w-3" />
                                            {{ $certification->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end gap-2 text-xs">
                                            <a href="{{ route('admin.certifications.show', $certification) }}" class="inline-flex items-center gap-1.5 text-teal-700 hover:text-teal-900">
                                                <x-icon name="eye" class="h-3.5 w-3.5" />
                                                Packages
                                            </a>
                                            <a href="{{ route('admin.certifications.edit', $certification) }}" class="inline-flex items-center gap-1.5 text-indigo-600 hover:text-indigo-900">
                                                <x-icon name="pencil" class="h-3.5 w-3.5" />
                                                Edit
                                            </a>

                                            @if ($certification->is_active)
                                                <form method="POST" action="{{ route('admin.certifications.destroy', $certification) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="inline-flex items-center gap-1.5 text-red-600 hover:text-red-900" onclick="return confirm('Deactivate this certification?')">
                                                        <x-icon name="x-circle" class="h-3.5 w-3.5" />
                                                        Deactivate
                                                    </button>
                                                </form>
                                            @endif

                                            <form method="POST" action="{{ route('admin.certifications.permanent-destroy', $certification) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex items-center gap-1.5 text-red-700 hover:text-red-950" onclick="return confirm('Permanently delete this certification? This only works when it has no packages.')">
                                                    <x-icon name="trash" class="h-3.5 w-3.5" />
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4">
                                        <x-empty-state
                                            icon="tag"
                                            title="No certifications yet"
                                            message="Create certification tracks such as PMP, PMI-ACP, CBAP, or Power BI."
                                        >
                                            <a href="{{ route('admin.certifications.create') }}" class="portal-button-primary text-xs uppercase tracking-widest">
                                                <x-icon name="plus" />
                                                Create Certification
                                            </a>
                                        </x-empty-state>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($certifications->hasPages())
                    <div class="border-t border-gray-200 px-6 py-4">
                        {{ $certifications->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
