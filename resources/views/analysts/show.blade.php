<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('analysts.index') }}"
                class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 text-sm">
                ← Analistas
            </a>
            <span class="text-gray-300 dark:text-gray-600">/</span>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ $analyst->name }}
            </h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 rounded-lg bg-green-50 dark:bg-green-900/30 p-4 text-green-800 dark:text-green-200">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Info del analista -->
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 mb-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <div class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $analyst->name }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $analyst->email }}</div>
                    </div>
                    <div class="flex gap-4">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $counts['invoices'] }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Facturas</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $counts['payments'] }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Pagos</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $counts['factorings'] }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Factorings</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                <div class="border-b border-gray-200 dark:border-gray-700 px-6">
                    <nav class="flex space-x-6 -mb-px">
                        <a href="{{ route('analysts.show', [$analyst, 'tab' => 'invoices']) }}"
                            class="py-4 text-sm font-medium border-b-2 transition-colors
                                {{ $tab === 'invoices'
                                    ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                                    : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200' }}">
                            Facturas
                            <span class="ml-1 rounded-full bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 text-xs px-2 py-0.5">
                                {{ $counts['invoices'] }}
                            </span>
                        </a>
                        <a href="{{ route('analysts.show', [$analyst, 'tab' => 'payments']) }}"
                            class="py-4 text-sm font-medium border-b-2 transition-colors
                                {{ $tab === 'payments'
                                    ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                                    : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200' }}">
                            Pagos
                            <span class="ml-1 rounded-full bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300 text-xs px-2 py-0.5">
                                {{ $counts['payments'] }}
                            </span>
                        </a>
                        <a href="{{ route('analysts.show', [$analyst, 'tab' => 'factorings']) }}"
                            class="py-4 text-sm font-medium border-b-2 transition-colors
                                {{ $tab === 'factorings'
                                    ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                                    : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200' }}">
                            Factorings
                            <span class="ml-1 rounded-full bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300 text-xs px-2 py-0.5">
                                {{ $counts['factorings'] }}
                            </span>
                        </a>
                    </nav>
                </div>

                <div class="p-6">

                    {{-- TAB: FACTURAS --}}
                    @if ($tab === 'invoices')
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="text-gray-500 dark:text-gray-400">
                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                        <th class="py-3 text-left font-semibold">NCF</th>
                                        <th class="py-3 text-left font-semibold">Fecha</th>
                                        <th class="py-3 text-left font-semibold">Médico</th>
                                        <th class="py-3 text-left font-semibold">ARS</th>
                                        <th class="py-3 text-right font-semibold">Total</th>
                                        <th class="py-3 text-left font-semibold">Estado</th>
                                        <th class="py-3 text-right font-semibold">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-800 dark:text-gray-100">
                                    @forelse ($invoices as $inv)
                                        <tr class="border-b border-gray-200/60 dark:border-gray-700/60">
                                            <td class="py-3 font-medium">{{ $inv->ncf_number }}</td>
                                            <td class="py-3">{{ optional($inv->invoice_date)->format('d/m/Y') }}</td>
                                            <td class="py-3">{{ $inv->doctor?->full_name ?? '-' }}</td>
                                            <td class="py-3">{{ $inv->insurer?->name ?? '-' }}</td>
                                            <td class="py-3 text-right">{{ number_format($inv->total_amount, 2) }}</td>
                                            <td class="py-3">
                                                @if ($inv->status === 'void')
                                                    <span class="rounded-full bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 text-xs px-2 py-0.5">Anulada</span>
                                                @else
                                                    <span class="rounded-full bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300 text-xs px-2 py-0.5">Activa</span>
                                                @endif
                                            </td>
                                            <td class="py-3 text-right">
                                                <a href="{{ route('invoices.view', $inv) }}"
                                                    class="inline-flex items-center rounded-lg bg-gray-600 px-3 py-1.5 text-white text-xs font-medium hover:bg-gray-700">
                                                    Ver
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="py-10 text-center text-gray-500 dark:text-gray-400">
                                                Este analista no ha creado facturas.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4">{{ $invoices->appends(['tab' => 'invoices'])->links() }}</div>
                    @endif

                    {{-- TAB: PAGOS --}}
                    @if ($tab === 'payments')
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="text-gray-500 dark:text-gray-400">
                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                        <th class="py-3 text-left font-semibold">Fecha</th>
                                        <th class="py-3 text-left font-semibold">Factura (NCF)</th>
                                        <th class="py-3 text-left font-semibold">Médico</th>
                                        <th class="py-3 text-left font-semibold">Método</th>
                                        <th class="py-3 text-left font-semibold">Referencia</th>
                                        <th class="py-3 text-right font-semibold">Monto</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-800 dark:text-gray-100">
                                    @forelse ($payments as $pay)
                                        <tr class="border-b border-gray-200/60 dark:border-gray-700/60">
                                            <td class="py-3">{{ optional($pay->payment_date)->format('d/m/Y') }}</td>
                                            <td class="py-3 font-medium">{{ $pay->invoice?->ncf_number ?? ('#'.$pay->invoice_id) }}</td>
                                            <td class="py-3">{{ $pay->invoice?->doctor?->full_name ?? '-' }}</td>
                                            <td class="py-3">{{ $pay->method ?? '-' }}</td>
                                            <td class="py-3">{{ $pay->reference_no ?? '-' }}</td>
                                            <td class="py-3 text-right">{{ number_format($pay->amount, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="py-10 text-center text-gray-500 dark:text-gray-400">
                                                Este analista no ha registrado pagos.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4">{{ $payments->appends(['tab' => 'payments'])->links() }}</div>
                    @endif

                    {{-- TAB: FACTORINGS --}}
                    @if ($tab === 'factorings')
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="text-gray-500 dark:text-gray-400">
                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                        <th class="py-3 text-left font-semibold">Fecha</th>
                                        <th class="py-3 text-left font-semibold">Factura (NCF)</th>
                                        <th class="py-3 text-left font-semibold">Médico</th>
                                        <th class="py-3 text-left font-semibold">ARS</th>
                                        <th class="py-3 text-right font-semibold">Total</th>
                                        <th class="py-3 text-right font-semibold">Neto Doctor</th>
                                        <th class="py-3 text-left font-semibold">Estado</th>
                                        <th class="py-3 text-right font-semibold">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-800 dark:text-gray-100">
                                    @forelse ($factorings as $f)
                                        <tr class="border-b border-gray-200/60 dark:border-gray-700/60">
                                            <td class="py-3">{{ optional($f->purchase_date)->toDateString() }}</td>
                                            <td class="py-3 font-medium">{{ $f->invoice?->ncf_number ?? ('#'.$f->invoice_id) }}</td>
                                            <td class="py-3">{{ $f->doctor?->full_name ?? '-' }}</td>
                                            <td class="py-3">{{ $f->insurer?->name ?? '-' }}</td>
                                            <td class="py-3 text-right">{{ number_format($f->invoice_total, 2) }}</td>
                                            <td class="py-3 text-right">{{ number_format($f->net_to_doctor, 2) }}</td>
                                            <td class="py-3">
                                                @php
                                                    $badge = match($f->status) {
                                                        'collected' => 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300',
                                                        'cancelled' => 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300',
                                                        default     => 'bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-300',
                                                    };
                                                @endphp
                                                <span class="rounded-full text-xs px-2 py-0.5 {{ $badge }}">
                                                    {{ ucfirst($f->status) }}
                                                </span>
                                            </td>
                                            <td class="py-3 text-right">
                                                <a href="{{ route('factorings.show', $f) }}"
                                                    class="inline-flex items-center rounded-lg bg-gray-600 px-3 py-1.5 text-white text-xs font-medium hover:bg-gray-700">
                                                    Ver
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="py-10 text-center text-gray-500 dark:text-gray-400">
                                                Este analista no ha creado factorings.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4">{{ $factorings->appends(['tab' => 'factorings'])->links() }}</div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
