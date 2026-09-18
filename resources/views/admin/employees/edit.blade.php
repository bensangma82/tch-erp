<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Edit Employee / Staff
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Update the hospital employee record. ERP login access remains separate under User Management.
                </p>
            </div>

            <a
                href="{{ route('admin.employees.index') }}"
                class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
            >
                Back to Employees
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">

            @if ($errors->any())
                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <div class="font-semibold">
                        Please correct the following:
                    </div>

                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form
                method="POST"
                action="{{ route('admin.employees.update', $employee) }}"
                class="space-y-6"
            >
                @csrf
                @method('PUT')

                <div class="rounded-xl bg-white p-6 shadow-sm">

                    <div class="mb-5">
                        <h3 class="text-base font-semibold text-gray-900">
                            Basic Information
                        </h3>

                        <p class="mt-1 text-sm text-gray-500">
                            Update the employee's identity and employment details.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                        <div>
                            <label
                                for="employee_code"
                                class="mb-1 block text-sm font-medium text-gray-700"
                            >
                                Employee Code
                                <span class="text-red-600">*</span>
                            </label>

                            <input
                                type="text"
                                name="employee_code"
                                id="employee_code"
                                value="{{ old('employee_code', $employee->employee_code) }}"
                                required
                                maxlength="50"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                        </div>

                        <div>
                            <label
                                for="title"
                                class="mb-1 block text-sm font-medium text-gray-700"
                            >
                                Title
                            </label>

                            <select
                                name="title"
                                id="title"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="">Select Title</option>
                                <option value="Dr." @selected(old('title', $employee->title) === 'Dr.')>Dr.</option>
                                <option value="Mr." @selected(old('title', $employee->title) === 'Mr.')>Mr.</option>
                                <option value="Mrs." @selected(old('title', $employee->title) === 'Mrs.')>Mrs.</option>
                                <option value="Ms." @selected(old('title', $employee->title) === 'Ms.')>Ms.</option>
                                <option value="Sr." @selected(old('title', $employee->title) === 'Sr.')>Sr.</option>
                                <option value="Br." @selected(old('title', $employee->title) === 'Br.')>Br.</option>
                                <option value="Rev." @selected(old('title', $employee->title) === 'Rev.')>Rev.</option>
                            </select>
                        </div>

                        <div>
                            <label
                                for="first_name"
                                class="mb-1 block text-sm font-medium text-gray-700"
                            >
                                First Name
                                <span class="text-red-600">*</span>
                            </label>

                            <input
                                type="text"
                                name="first_name"
                                id="first_name"
                                value="{{ old('first_name', $employee->first_name) }}"
                                required
                                maxlength="100"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                        </div>

                        <div>
                            <label
                                for="middle_name"
                                class="mb-1 block text-sm font-medium text-gray-700"
                            >
                                Middle Name
                            </label>

                            <input
                                type="text"
                                name="middle_name"
                                id="middle_name"
                                value="{{ old('middle_name', $employee->middle_name) }}"
                                maxlength="100"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                        </div>

                        <div>
                            <label
                                for="last_name"
                                class="mb-1 block text-sm font-medium text-gray-700"
                            >
                                Last Name
                            </label>

                            <input
                                type="text"
                                name="last_name"
                                id="last_name"
                                value="{{ old('last_name', $employee->last_name) }}"
                                maxlength="100"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                        </div>

                        <div>
                            <label
                                for="designation"
                                class="mb-1 block text-sm font-medium text-gray-700"
                            >
                                Designation
                            </label>

                            <input
                                type="text"
                                name="designation"
                                id="designation"
                                value="{{ old('designation', $employee->designation) }}"
                                maxlength="150"
                                placeholder="Example: Consultant Nephrologist"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                        </div>

                        <div>
                            <label
                                for="department_id"
                                class="mb-1 block text-sm font-medium text-gray-700"
                            >
                                Department
                            </label>

                            <select
                                name="department_id"
                                id="department_id"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="">Select Department</option>

                                @foreach ($departments as $department)
                                    <option
                                        value="{{ $department->id }}"
                                        @selected(
                                            (string) old('department_id', $employee->department_id)
                                            === (string) $department->id
                                        )
                                    >
                                        {{ $department->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label
                                for="employee_type"
                                class="mb-1 block text-sm font-medium text-gray-700"
                            >
                                Employee Type
                            </label>

                            <input
                                type="text"
                                name="employee_type"
                                id="employee_type"
                                value="{{ old('employee_type', $employee->employee_type) }}"
                                maxlength="100"
                                list="employee-type-options"
                                placeholder="Example: Permanent"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                            <datalist id="employee-type-options">
                                @foreach ($employeeTypes as $employeeType)
                                    <option value="{{ $employeeType }}">
                                @endforeach

                                <option value="Permanent">
                                <option value="Contractual">
                                <option value="Temporary">
                                <option value="Consultant">
                                <option value="Visiting">
                                <option value="Intern">
                                <option value="Trainee">
                            </datalist>
                        </div>

                        <div>
                            <label
                                for="date_of_joining"
                                class="mb-1 block text-sm font-medium text-gray-700"
                            >
                                Date of Joining
                            </label>

                            <input
                                type="date"
                                name="date_of_joining"
                                id="date_of_joining"
                                value="{{ old(
                                    'date_of_joining',
                                    $employee->date_of_joining?->format('Y-m-d')
                                ) }}"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                        </div>

                    </div>
                </div>

                <div class="rounded-xl bg-white p-6 shadow-sm">

                    <div class="mb-5">
                        <h3 class="text-base font-semibold text-gray-900">
                            Professional Details
                        </h3>

                        <p class="mt-1 text-sm text-gray-500">
                            Clinical and professional information where applicable.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                        <div>
                            <label
                                for="professional_registration_no"
                                class="mb-1 block text-sm font-medium text-gray-700"
                            >
                                Professional Registration No.
                            </label>

                            <input
                                type="text"
                                name="professional_registration_no"
                                id="professional_registration_no"
                                value="{{ old(
                                    'professional_registration_no',
                                    $employee->professional_registration_no
                                ) }}"
                                maxlength="100"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                        </div>

                        <div>
                            <label
                                for="qualification"
                                class="mb-1 block text-sm font-medium text-gray-700"
                            >
                                Qualification
                            </label>

                            <input
                                type="text"
                                name="qualification"
                                id="qualification"
                                value="{{ old('qualification', $employee->qualification) }}"
                                maxlength="255"
                                placeholder="Example: MD, DM"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                        </div>

                        <div class="md:col-span-2">
                            <label
                                for="speciality"
                                class="mb-1 block text-sm font-medium text-gray-700"
                            >
                                Speciality
                            </label>

                            <input
                                type="text"
                                name="speciality"
                                id="speciality"
                                value="{{ old('speciality', $employee->speciality) }}"
                                maxlength="255"
                                placeholder="Example: Nephrology"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                        </div>

                    </div>
                </div>

                <div class="rounded-xl bg-white p-6 shadow-sm">

                    <div class="mb-5">
                        <h3 class="text-base font-semibold text-gray-900">
                            Contact Information
                        </h3>
                    </div>

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                        <div>
                            <label
                                for="phone"
                                class="mb-1 block text-sm font-medium text-gray-700"
                            >
                                Phone
                            </label>

                            <input
                                type="text"
                                name="phone"
                                id="phone"
                                value="{{ old('phone', $employee->phone) }}"
                                maxlength="30"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                        </div>

                        <div>
                            <label
                                for="email"
                                class="mb-1 block text-sm font-medium text-gray-700"
                            >
                                Email
                            </label>

                            <input
                                type="email"
                                name="email"
                                id="email"
                                value="{{ old('email', $employee->email) }}"
                                maxlength="255"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                        </div>

                    </div>
                </div>

                <div class="rounded-xl bg-white p-6 shadow-sm">

                    <div class="mb-5">
                        <h3 class="text-base font-semibold text-gray-900">
                            System Classification
                        </h3>

                        <p class="mt-1 text-sm text-gray-500">
                            These settings determine whether the employee appears in clinical and operational dropdowns.
                        </p>
                    </div>

                    <div class="space-y-4">

                        <label class="flex items-start gap-3">
                            <input
                                type="checkbox"
                                name="is_doctor"
                                value="1"
                                @checked(old('is_doctor', $employee->is_doctor))
                                class="mt-1 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                            >

                            <span>
                                <span class="block text-sm font-medium text-gray-800">
                                    Doctor
                                </span>

                                <span class="block text-sm text-gray-500">
                                    Enable this for doctors who should appear in consultant and doctor selections.
                                </span>
                            </span>
                        </label>

                        <label class="flex items-start gap-3">
                            <input
                                type="checkbox"
                                name="is_active"
                                value="1"
                                @checked(old('is_active', $employee->is_active))
                                class="mt-1 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                            >

                            <span>
                                <span class="block text-sm font-medium text-gray-800">
                                    Active Employee
                                </span>

                                <span class="block text-sm text-gray-500">
                                    Inactive employees should no longer appear in normal ERP operational selections.
                                </span>
                            </span>
                        </label>

                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">

                    <a
                        href="{{ route('admin.employees.index') }}"
                        class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                    >
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="inline-flex items-center rounded-lg bg-blue-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700"
                    >
                        Update Employee
                    </button>

                </div>

            </form>

        </div>
    </div>
</x-app-layout>