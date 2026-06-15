<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Create Group
            </h2>
            <a href="{{ route('admin.student-groups.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-900">
                Back to Groups
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('admin.student-groups.store') }}" class="space-y-6">
                    @csrf

                    @include('admin.student-groups.partials.form', ['group' => $group])

                    <div class="flex justify-end">
                        <x-primary-button>Create Group</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
