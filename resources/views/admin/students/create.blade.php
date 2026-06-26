<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Register Student
            </h2>
            <a href="{{ route('admin.students.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-indigo-600 hover:text-indigo-900">
                <x-icon name="users" class="h-3.5 w-3.5" />
                Back to Students
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('admin.students.store') }}" class="space-y-6">
                    @csrf

                    <div>
                        <x-input-label for="name" value="Name" icon="user" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required autofocus />
                        <x-input-error class="mt-2" :messages="$errors->get('name')" />
                    </div>

                    <div>
                        <x-input-label for="email" value="Email" icon="mail" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('email')" />
                    </div>

                    <div class="grid gap-6 md:grid-cols-2">
                        <div>
                            <x-input-label for="password" value="Password" icon="lock-keyhole" />
                            <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" required />
                            <x-input-error class="mt-2" :messages="$errors->get('password')" />
                        </div>

                        <div>
                            <x-input-label for="password_confirmation" value="Confirm Password" icon="lock-keyhole" />
                            <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" required />
                        </div>
                    </div>

                    <div class="grid gap-6 md:grid-cols-2">
                        <div>
                            <x-input-label for="student_group_id" value="Student Group" icon="users-round" />
                            <select id="student_group_id" name="student_group_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">No group</option>
                                @foreach ($groups as $group)
                                    <option value="{{ $group->id }}" @selected((string) old('student_group_id') === (string) $group->id)>
                                        {{ $group->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('student_group_id')" />
                        </div>

                        <div>
                            <x-input-label for="new_group_name" value="Or Add New Group" icon="plus" />
                            <x-text-input id="new_group_name" name="new_group_name" type="text" class="mt-1 block w-full" :value="old('new_group_name')" />
                            <x-input-error class="mt-2" :messages="$errors->get('new_group_name')" />
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="portal-button-primary text-xs uppercase tracking-widest">
                            <x-icon name="user-plus" />
                            Register Student
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
