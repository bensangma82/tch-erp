<x-app-layout>
<div class="container mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold mb-6">
        MHIS Settlement Import
    </h1>

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 p-3 bg-red-100 text-red-800 rounded">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    @can('finance.mhis-settlement-import.upload')
    <div class="bg-white rounded shadow p-6 mb-6">
        <h2 class="text-lg font-semibold mb-3">
            Upload MHIS Settlement Excel
        </h2>

        <form
            method="POST"
            action="{{ route('finance.mhis-settlement-import.store') }}"
            enctype="multipart/form-data"
        >
            @csrf

            <input
                type="file"
                name="file"
                accept=".xlsx"
                required
                class="block w-full border rounded p-2 mb-4"
            >

            <button
                type="submit"
                class="bg-blue-700 text-white px-5 py-2 rounded"
            >
                Import Excel
            </button>
        </form>

        <p class="text-sm text-gray-500 mt-3">
            Import only. No accounting entries or Tally exports
            will be generated.
        </p>
    </div>
    @endcan

    <div class="bg-white rounded shadow p-6">
        <h2 class="text-lg font-semibold mb-4">
            Previous Imports
        </h2>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b text-left">
                        <th class="p-2">Batch</th>
                        <th class="p-2">Filename</th>
                        <th class="p-2">Rows</th>
                        <th class="p-2">Approved</th>
                        <th class="p-2">TDS</th>
                        <th class="p-2">Net Payment</th>
                        <th class="p-2">Status</th>
                        <th class="p-2">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($batches as $batch)
                        <tr class="border-b">
                            <td class="p-2">{{ $batch->id }}</td>
                            <td class="p-2">{{ $batch->original_filename }}</td>
                            <td class="p-2">{{ $batch->total_rows }}</td>
                            <td class="p-2">{{ number_format($batch->total_approved, 2) }}</td>
                            <td class="p-2">{{ number_format($batch->total_tds, 2) }}</td>
                            <td class="p-2">{{ number_format($batch->total_net, 2) }}</td>
                            <td class="p-2">{{ $batch->status }}</td>
                            <td class="p-2">
                                <a
                                    href="{{ route('finance.mhis-settlement-import.show', $batch) }}"
                                    class="text-blue-700 underline"
                                >
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-4 text-center text-gray-500">
                                No settlement files imported yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $batches->links() }}
        </div>
    </div>
</div>
</x-app-layout>
