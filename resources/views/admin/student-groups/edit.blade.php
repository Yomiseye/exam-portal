<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Edit Group
            </h2>
            <a href="{{ route('admin.student-groups.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-indigo-600 hover:text-indigo-900">
                <x-icon name="users-round" class="h-3.5 w-3.5" />
                Back to Groups
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('admin.student-groups.update', $group) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    @include('admin.student-groups.partials.form', ['group' => $group])

                    <div class="flex justify-end">
                        <x-primary-button>
                            <x-icon name="save" />
                            Save Changes
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
