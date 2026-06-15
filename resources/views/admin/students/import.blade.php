<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Import Students
            </h2>
            <a href="{{ route('admin.students.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-900">
                Back to Students
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto space-y-6 sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('admin.students.import.store') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf

                    @if (! empty($sheets))
                        <input type="hidden" name="import_path" value="{{ $importPath }}">

                        <div class="rounded-md bg-blue-50 p-4 text-sm text-blue-700">
                            The uploaded workbook{{ $importFileName ? ' ('.$importFileName.')' : '' }} has multiple sheets. Choose the sheet you want to import.
                        </div>

                        <div>
                            <x-input-label for="sheet_index" value="Worksheet" />
                            <select id="sheet_index" name="sheet_index" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Choose worksheet</option>
                                @foreach ($sheets as $sheet)
                                    <option value="{{ $sheet['index'] }}" @selected((string) old('sheet_index') === (string) $sheet['index'])>
                                        {{ $sheet['name'] }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('sheet_index')" />
                            @if ($sheetError)
                                <div class="mt-2 text-sm text-red-600">{{ $sheetError }}</div>
                            @endif
                        </div>
                    @else
                        <div>
                            <x-input-label for="students_file" value="Students Excel File" />
                            <input
                                id="students_file"
                                name="students_file"
                                type="file"
                                accept=".xlsx"
                                required
                                class="mt-2 block w-full text-sm text-gray-700 file:me-4 file:rounded-md file:border-0 file:bg-gray-800 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-gray-700"
                            >
                            <p class="mt-1 text-sm text-gray-500">Upload a .xlsx file. Maximum size: 5MB. If the file has multiple sheets, you will be asked which sheet to import.</p>
                            <x-input-error class="mt-2 whitespace-pre-line" :messages="$errors->get('students_file')" />
                        </div>
                    @endif

                    <div class="rounded-md bg-gray-50 p-4">
                        <h3 class="text-sm font-semibold text-gray-900">Excel Columns</h3>
                        <div class="mt-3 overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead>
                                    <tr class="text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        <th class="py-2 pe-4">name</th>
                                        <th class="py-2 pe-4">email</th>
                                        <th class="py-2 pe-4">password</th>
                                        <th class="py-2 pe-4">group</th>
                                        <th class="py-2 pe-4">is_active</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 text-gray-700">
                                    <tr>
                                        <td class="py-2 pe-4">Ade Ola</td>
                                        <td class="py-2 pe-4">ade@example.com</td>
                                        <td class="py-2 pe-4">Password123!</td>
                                        <td class="py-2 pe-4">June Cohort</td>
                                        <td class="py-2 pe-4">yes</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p class="mt-3 text-sm text-gray-500">Required: name, email, password. Optional: group, is_active. Missing groups are created automatically.</p>
                    </div>

                    <div class="flex justify-end">
                        <x-primary-button>{{ ! empty($sheets) ? 'Import Selected Sheet' : 'Import Students' }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
