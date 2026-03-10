<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Dashboard') }}
        </h2>
        <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Resumen del periodo: {{ $monthStart->format('d M Y') }} - {{ $monthEnd->format('d M Y') }}
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- KPIs --}}
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400">Facturas emitidas (mes)</div>
                    <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $issuedCountMonth }}</div>
                </div>

                <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400">Facturas anuladas (mes)</div>
                    <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $voidCountMonth }}</div>
                </div>

                <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400">Facturado (mes)</div>
                    <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        {{ number_format($totalBilledMonth, 2) }}
                    </div>
                </div>

                <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400">Cobrado (mes)</div>
                    <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        {{ number_format($totalPaidMonth, 2) }}
                    </div>
                </div>

                <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400">Pendiente total</div>
                    <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        {{ number_format($totalOutstanding, 2) }}
                    </div>
                </div>

                <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400">Hoy</div>
                    <div class="mt-2 text-sm text-gray-700 dark:text-gray-200 space-y-1">
                        <div>Emitidas: <span class="font-semibold">{{ $issuedToday }}</span></div>
                        <div>Facturado: <span class="font-semibold">{{ number_format($billedToday, 2) }}</span></div>
                        <div>Cobrado: <span class="font-semibold">{{ number_format($paidToday, 2) }}</span></div>
                    </div>
                </div>
            </div>
                            @role('admin')
            {{-- Top lists --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">Top médicos (mes)</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">por monto facturado</div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="text-gray-500 dark:text-gray-400">
                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                    <th class="py-2 text-left">Médico</th>
                                    <th class="py-2 text-right">Facturas</th>
                                    <th class="py-2 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-800 dark:text-gray-100">
                                @forelse($topDoctors as $row)
                                    <tr class="border-b border-gray-100 dark:border-gray-700/50">
                                        <td class="py-2">{{ $row->doctor?->full_name ?? '—' }}</td>
                                        <td class="py-2 text-right">{{ $row->qty }}</td>
                                        <td class="py-2 text-right">{{ number_format((float)$row->total, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="py-6 text-center text-gray-500 dark:text-gray-400">Sin datos</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">Top ARS (mes)</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">por monto facturado</div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="text-gray-500 dark:text-gray-400">
                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                    <th class="py-2 text-left">ARS</th>
                                    <th class="py-2 text-right">Facturas</th>
                                    <th class="py-2 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-800 dark:text-gray-100">
                                @forelse($topInsurers as $row)
                                    <tr class="border-b border-gray-100 dark:border-gray-700/50">
                                        <td class="py-2">{{ $row->insurer?->name ?? '—' }}</td>
                                        <td class="py-2 text-right">{{ $row->qty }}</td>
                                        <td class="py-2 text-right">{{ number_format((float)$row->total, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="py-6 text-center text-gray-500 dark:text-gray-400">Sin datos</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                
            </div>
@endrole
            {{-- Recientes --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6">
                    <div class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">Últimas facturas</div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="text-gray-500 dark:text-gray-400">
                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                    <th class="py-2 text-left">NCF</th>
                                    <th class="py-2 text-left">Médico</th>
                                    <th class="py-2 text-left">ARS</th>
                                    <th class="py-2 text-right">Total</th>
                                    <th class="py-2 text-left">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-800 dark:text-gray-100">
                                @forelse($recentInvoices as $inv)
                                    <tr class="border-b border-gray-100 dark:border-gray-700/50">
                                        <td class="py-2 font-medium">{{ $inv->ncf_number }}</td>
                                        <td class="py-2">{{ $inv->doctor?->full_name }}</td>
                                        <td class="py-2">{{ $inv->insurer?->name }}</td>
                                        <td class="py-2 text-right">{{ number_format((float)$inv->total_amount, 2) }}</td>
                                        <td class="py-2">
                                            <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold
                                                {{ $inv->status === 'issued' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-200' : '' }}
                                                {{ $inv->status === 'draft' ? 'bg-gray-100 text-gray-800 dark:bg-gray-700/40 dark:text-gray-100' : '' }}
                                                {{ $inv->status === 'void' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-200' : '' }}
                                            ">
                                                {{ $inv->status }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="py-6 text-center text-gray-500 dark:text-gray-400">Sin facturas</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        <a href="{{ route('invoices.index') }}"
                           class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                            Ver módulo de facturas →
                        </a>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6">
                    <div class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">Últimos pagos</div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="text-gray-500 dark:text-gray-400">
                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                    <th class="py-2 text-left">Fecha</th>
                                    <th class="py-2 text-left">Factura</th>
                                    <th class="py-2 text-left">Médico</th>
                                    <th class="py-2 text-left">ARS</th>
                                    <th class="py-2 text-right">Monto</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-800 dark:text-gray-100">
                                @forelse($recentPayments as $p)
                                    <tr class="border-b border-gray-100 dark:border-gray-700/50">
                                        <td class="py-2">{{ optional($p->payment_date)->format('Y-m-d') }}</td>
                                        <td class="py-2 font-medium">{{ $p->invoice?->ncf_number ?? '—' }}</td>
                                        <td class="py-2">{{ $p->invoice?->doctor?->full_name ?? '—' }}</td>
                                        <td class="py-2">{{ $p->invoice?->insurer?->name ?? '—' }}</td>
                                        <td class="py-2 text-right">{{ number_format((float)$p->amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="py-6 text-center text-gray-500 dark:text-gray-400">Sin pagos</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{-- si ya creaste el módulo de pagos aparte --}}
                        @if (Route::has('payments.index'))
                            <a href="{{ route('payments.index') }}"
                               class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                                Ver módulo de pagos →
                            </a>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
