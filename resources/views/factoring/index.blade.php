<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Factoring') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 rounded-lg bg-green-50 dark:bg-green-900/30 p-4 text-green-800 dark:text-green-200">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mb-4 rounded-lg bg-red-50 dark:bg-red-900/30 p-4 text-red-800 dark:text-red-200">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">

                <!-- Top bar (buscador + boton) -->
                <div class="flex flex-col md:flex-row md:items-center gap-3">
                    <form method="GET" action="{{ route('factorings.index') }}" class="flex-1">
                        <input
                            type="text"
                            name="q"
                            value="{{ request('q') }}"
                            placeholder="Buscar por NCF, médico, ARS o referencia..."
                            class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                        />
                    </form>

                    <a href="{{ route('factorings.create') }}"
                       class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-white font-medium hover:bg-blue-700">
                        + Nuevo Factoring
                    </a>
                </div>

                <!-- Tabla -->
                <div class="mt-6 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-gray-500 dark:text-gray-400">
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="py-3 px-4 text-left">Fecha</th>
                            <th class="py-3 px-4 text-left">Factura (NCF)</th>
                            <th class="py-3 px-4 text-left">Médico</th>
                            <th class="py-3 px-4 text-left">ARS</th>
                            <th class="py-3 px-4 text-left">Vence</th>
                            <th class="py-3 px-4 text-right">Total</th>
                            <th class="py-3 px-4 text-right">Neto Doctor</th>
                            <th class="py-3 px-4 text-right">Ganancia</th>
                            <th class="py-3 px-4 text-left">Estado</th>
                            <th class="py-3 px-4 text-right">Acciones</th>
                        </tr>
                        </thead>

                        <tbody class="text-gray-800 dark:text-gray-100">
                        @forelse($factorings as $f)
                            <tr class="border-b border-gray-200/60 dark:border-gray-700/60">
                                <td class="py-3 px-4">
                                    {{ optional($f->purchase_date)->toDateString() }}
                                </td>

                                <td class="py-3 px-4 font-medium">
                                    {{ $f->invoice?->ncf_number ?? ('#'.$f->invoice_id) }}
                                </td>

                                <td class="py-3 px-4">
                                    {{ $f->doctor?->full_name ?? '-' }}
                                </td>

                                <td class="py-3 px-4">
                                    {{ $f->insurer?->name ?? '-' }}
                                </td>

                                <td class="py-3 px-4">
                                    {{ optional($f->due_date)->toDateString() }}
                                </td>

                                <td class="py-3 px-4 text-right">
                                    {{ number_format((float)$f->invoice_total, 2) }}
                                </td>

                                <td class="py-3 px-4 text-right">
                                    {{ number_format((float)$f->net_to_doctor, 2) }}
                                </td>

                                <td class="py-3 px-4 text-right">
                                    {{ number_format((float)$f->expected_profit, 2) }}
                                </td>

                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold
                                        {{ $f->status === 'active' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-200' : '' }}
                                        {{ $f->status === 'collected' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-200' : '' }}
                                        {{ $f->status === 'cancelled' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-200' : '' }}
                                        {{ !in_array($f->status, ['active','collected','cancelled']) ? 'bg-gray-100 text-gray-800 dark:bg-gray-700/40 dark:text-gray-100' : '' }}
                                    ">
                                        {{ $f->status }}
                                    </span>
                                </td>

                                <td class="py-3 px-4 text-right whitespace-nowrap">
    <div class="inline-flex items-center gap-2 justify-end">
        <a href="{{ route('factorings.show', $f) }}"
           class="rounded-lg bg-gray-600 px-4 py-2 text-white font-medium hover:bg-gray-700">
            Ver
        </a>
    </div>
</td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="py-10 text-center text-gray-500 dark:text-gray-400">
                                    No hay factorizaciones registradas.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $factorings->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
