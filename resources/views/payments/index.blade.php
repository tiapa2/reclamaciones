<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Pagos') }}
        </h2>
    </x-slot>

    <div class="py-8" x-data="paymentsPage()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 rounded-lg bg-green-50 dark:bg-green-900/30 p-4 text-green-800 dark:text-green-200">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded-lg bg-red-50 dark:bg-red-900/30 p-4 text-red-800 dark:text-red-200">
                    <div class="font-semibold mb-2">Revisa estos campos:</div>
                    <ul class="list-disc ml-5">
                        @foreach ($errors->all() as $error)
                            <li class="text-sm">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- HEADER ACTIONS --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <form method="GET" class="flex-1">
                        <input name="q" value="{{ $q }}"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100
                                      focus:border-indigo-500 focus:ring-indigo-500"
                               placeholder="Buscar por NCF, médico, ARS o referencia..." />
                    </form>
                    @role('admin')
                    <button type="button" @click="openCreate()"
                            class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-white font-medium hover:bg-blue-700">
                        + Registrar pago
                    </button>
                    @endrole
                </div>

                {{-- LISTADO --}}
                <div class="mt-6 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-gray-600 dark:text-gray-300">
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="py-3 text-left font-semibold">Fecha</th>
                                <th class="py-3 text-left font-semibold">Factura (NCF)</th>
                                <th class="py-3 text-left font-semibold">Médico</th>
                                <th class="py-3 text-left font-semibold">ARS</th>
                                <th class="py-3 text-left font-semibold">Método</th>
                                <th class="py-3 text-left font-semibold">Ref.</th>
                                <th class="py-3 text-right font-semibold">Monto</th>
                                @role('admin')
                                <th class="py-3 text-right font-semibold">Acciones</th>
                                @endrole
                            </tr>
                        </thead>

                        <tbody class="text-gray-800 dark:text-gray-100">
                            @forelse ($payments as $p)
                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                    <td class="py-3">{{ optional($p->payment_date)->toDateString() }}</td>
                                    <td class="py-3 font-medium">{{ $p->invoice?->ncf_number ?? ('#'.$p->invoice_id) }}</td>
                                    <td class="py-3">{{ $p->invoice?->doctor?->full_name }}</td>
                                    <td class="py-3">{{ $p->invoice?->insurer?->name }}</td>
                                    <td class="py-3">{{ $p->method }}</td>
                                    <td class="py-3 text-gray-500 dark:text-gray-400">{{ $p->reference_no ?: '-' }}</td>
                                    <td class="py-3 text-right">{{ number_format((float)$p->amount, 2) }}</td>
                                    @role('admin')
                                    <td class="py-3 text-right">
                                        <form method="POST" action="{{ route('payments.destroy', $p) }}" class="inline"
                                              onsubmit="return confirm('¿Eliminar este pago?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="rounded-lg bg-red-600 px-4 py-2 text-white font-medium hover:bg-red-700">
                                                Eliminar
                                            </button>
                                        </form>
                                    </td>
                                    @endrole
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-10 text-center text-gray-500 dark:text-gray-400">
                                        No hay pagos registrados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $payments->links() }}
                </div>
            </div>
        </div>

        {{-- MODAL CREAR --}}
        <div x-show="modalOpen" x-transition.opacity class="fixed inset-0 z-50 bg-black/50 px-4 py-6 overflow-y-auto"
             style="display:none;">
            <div class="min-h-full flex items-center justify-center">
                <div x-transition @click.outside="closeModal()"
                     class="w-full max-w-3xl rounded-xl bg-white dark:bg-gray-800 shadow-lg overflow-hidden
                            max-h-[calc(100vh-3rem)]">
                    <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 p-5">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Registrar pago</h3>
                        <button class="text-gray-500 hover:text-gray-800 dark:text-gray-300 dark:hover:text-white"
                                @click="closeModal()">✕</button>
                    </div>

                    <form method="POST" action="{{ route('payments.store') }}" class="p-5 overflow-y-auto"
                          style="max-height: calc(100vh - 14rem);">
                        @csrf

                        {{-- Buscar factura --}}
                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                                Buscar factura (NCF / Médico / ARS)
                            </label>

                            <div class="flex gap-2">
                                <input type="text" x-model="invoiceQuery" @input.debounce.300ms="searchInvoices()"
                                       class="flex-1 rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                       placeholder="Ej: B0100000124, Dr. Juan, Humano..." />
                                <button type="button" @click="searchInvoices()"
                                        class="rounded-lg bg-gray-700/40 px-4 py-2 text-gray-100 hover:bg-gray-700/60">
                                    Buscar
                                </button>
                            </div>

                            <input type="hidden" name="invoice_id" :value="selectedInvoice?.id || ''">

                            <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3" x-show="invoices.length">
                                <template x-for="inv in invoices" :key="inv.id">
                                    <button type="button" @click="selectInvoice(inv)"
                                            class="text-left rounded-lg border border-gray-200 dark:border-gray-700 p-3
                                                   hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                        <div class="flex items-center justify-between">
                                            <div class="font-semibold text-gray-900 dark:text-gray-100" x-text="inv.ncf_number"></div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400" x-text="`#${inv.id}`"></div>
                                        </div>
                                        <div class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                                            <span x-text="inv.doctor"></span>
                                            <span class="mx-2">•</span>
                                            <span x-text="inv.insurer"></span>
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            Total: <span x-text="formatMoney(inv.total)"></span>
                                            <span class="mx-2">|</span>
                                            Pagado: <span x-text="formatMoney(inv.paid)"></span>
                                            <span class="mx-2">|</span>
                                            Pendiente: <span class="font-semibold" x-text="formatMoney(inv.balance)"></span>
                                        </div>
                                    </button>
                                </template>
                            </div>

                            <div class="mt-3 rounded-lg bg-blue-50 dark:bg-blue-900/20 p-3"
                                 x-show="selectedInvoice">
                                <div class="text-sm text-blue-800 dark:text-blue-200">
                                    Factura seleccionada:
                                    <span class="font-semibold" x-text="selectedInvoice?.ncf_number"></span>
                                    — Pendiente:
                                    <span class="font-semibold" x-text="formatMoney(selectedInvoice?.balance || 0)"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Datos pago --}}
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Fecha</label>
                                <input type="date" name="payment_date" required
                                       class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Método</label>
                                <select name="method" required
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                                    <option value="transfer">Transferencia</option>
                                    <option value="cash">Efectivo</option>
                                    <option value="card">Tarjeta</option>
                                    <option value="check">Cheque</option>
                                    <option value="other">Otro</option>
                                </select>
                            </div>

                            <div class="md:col-span-1">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Referencia</label>
                                <input type="text" name="reference_no"
                                       class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                       placeholder="Opcional">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Monto</label>
                                <input type="number" step="0.01" min="0.01" name="amount" required
                                       class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                            </div>

                            <div class="md:col-span-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Notas</label>
                                <input type="text" name="notes"
                                       class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                       placeholder="Opcional">
                            </div>
                        </div>

                        <div class="mt-6 flex items-center justify-end gap-3 border-t border-gray-200 dark:border-gray-700 pt-4">
                            <button type="button"
                                    class="rounded-lg border border-gray-300 dark:border-gray-700 px-4 py-2 text-gray-700 dark:text-gray-200
                                           hover:bg-gray-50 dark:hover:bg-gray-700/30"
                                    @click="closeModal()">
                                Cancelar
                            </button>

                            <button type="submit"
                                    class="rounded-lg bg-green-600 px-4 py-2 text-white font-medium hover:bg-green-700"
                                    :disabled="!selectedInvoice"
                                    :class="!selectedInvoice ? 'opacity-60 cursor-not-allowed' : ''">
                                Guardar pago
                            </button>
                        </div>

                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400" x-show="!selectedInvoice">
                            Selecciona una factura para poder registrar el pago.
                        </p>
                    </form>
                </div>
            </div>
        </div>

        <script>
            function paymentsPage() {
                return {
                    modalOpen: false,

                    invoiceQuery: '',
                    invoices: [],
                    selectedInvoice: null,

                    openCreate() {
                        this.modalOpen = true;
                        this.invoiceQuery = '';
                        this.invoices = [];
                        this.selectedInvoice = null;
                    },

                    closeModal() {
                        this.modalOpen = false;
                    },

                    async searchInvoices() {
                        const q = (this.invoiceQuery || '').trim();
                        const res = await fetch(`{{ route('payments.invoice-search') }}?q=${encodeURIComponent(q)}`);
                        const json = await res.json();
                        this.invoices = json.ok ? (json.invoices || []) : [];
                    },

                    selectInvoice(inv) {
                        this.selectedInvoice = inv;
                    },

                    formatMoney(n) {
                        const v = (parseFloat(n || 0) || 0);
                        return v.toLocaleString('es-DO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    }
                }
            }
        </script>
    </div>
</x-app-layout>
