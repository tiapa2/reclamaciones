<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Consulta de Facturas') }}
        </h2>
    </x-slot>

    <div class="py-8" x-data="invoiceQueryPage()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 rounded-lg bg-green-50 dark:bg-green-900/30 p-4 text-green-800 dark:text-green-200">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Médico</label>
                        <select x-model="filters.doctor_id"
                        @change="onDoctorChange"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                            <option value="">Seleccionar médico...</option>
                            @foreach ($doctors as $d)
                                <option value="{{ $d->id }}">{{ $d->full_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">ARS</label>
                        <select x-model="filters.insurer_id"
        class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
    <option value="">Seleccionar ARS...</option>

    <template x-for="i in insurers" :key="i.id">
        <option :value="i.id" x-text="i.name"></option>
    </template>
</select>

                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <button type="button" @click="reset()"
                        class="rounded-lg bg-gray-600 px-4 py-2 text-white font-medium hover:bg-gray-700">
                        Cancelar
                    </button>

                    <button type="button" @click="search()"
                        class="rounded-lg bg-blue-600 px-5 py-2 text-white font-medium hover:bg-blue-700">
                        🔎 Consultar factura
                    </button>
                </div>

                <div class="mt-8 rounded-lg border border-gray-200 dark:border-gray-700 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-gray-900/40">
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="py-3 px-4 text-left font-semibold">Aseguradora</th>
                                <th class="py-3 px-4 text-left font-semibold">Médico</th>
                                <th class="py-3 px-4 text-left font-semibold">No. Factura</th>
                                <th class="py-3 px-4 text-left font-semibold">NCF</th>
                                <th class="py-3 px-4 text-left font-semibold">Fecha</th>
                                <th class="py-3 px-4 text-left font-semibold">Status</th>
                                <th class="py-3 px-4 text-right font-semibold">Monto</th>
                                <th class="py-3 px-4 text-right font-semibold">Acciones</th>
                            </tr>
                        </thead>

                        <tbody class="text-gray-800 dark:text-gray-100">
                            <template x-if="loading">
                                <tr>
                                    <td colspan="8" class="py-10 text-center text-gray-500 dark:text-gray-400">
                                        Cargando...
                                    </td>
                                </tr>
                            </template>

                            <template x-if="!loading && invoices.length === 0">
                                <tr>
                                    <td colspan="8" class="py-10 text-center text-gray-500 dark:text-gray-400">
                                        No hay resultados.
                                    </td>
                                </tr>
                            </template>

                            <template x-for="inv in invoices" :key="inv.id">
                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                    <td class="py-3 px-4" x-text="inv.insurer"></td>
                                    <td class="py-3 px-4" x-text="inv.doctor"></td>
                                    <td class="py-3 px-4" x-text="inv.id"></td>
                                    <td class="py-3 px-4" x-text="inv.ncf"></td>
                                    <td class="py-3 px-4" x-text="inv.date"></td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-1 rounded text-xs"
                                            :class="inv.status === 'void'
                                                ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-200'
                                                : 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-200'"
                                            x-text="inv.status">
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-right" x-text="formatMoney(inv.total)"></td>
                                    <td class="py-3 px-4 text-right space-x-2">
                                        <a :href="`/invoices/${inv.id}/view`"
   class="rounded-lg bg-gray-600 px-4 py-2 text-white font-medium hover:bg-gray-700">
  Ver
</a>



                                        <button type="button" @click="voidInvoice(inv.id)" x-show="inv.status !== 'void'"
                                            class="rounded-lg bg-red-600 px-4 py-2 text-white font-medium hover:bg-red-700">
                                            Anular
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Modal detalle factura (reutilizable) --}}
            <!-- MODAL DETALLE FACTURA -->
<div x-show="invoiceModal" x-transition.opacity
     class="fixed inset-0 z-50 bg-black/60 px-4 py-6 overflow-y-auto"
     style="display:none;">
  <div class="min-h-full flex items-center justify-center">
    <div @click.outside="closeInvoice()"
         class="w-full max-w-5xl rounded-2xl bg-gray-900 text-gray-100 shadow-2xl border border-gray-700 overflow-hidden">

      <!-- Header -->
      <div class="flex items-center justify-between border-b border-gray-700 px-6 py-4">
        <h3 class="text-xl font-semibold">Detalle de factura</h3>
        <button type="button"
                class="text-gray-300 hover:text-white"
                @click="closeInvoice()">
          ✕
        </button>
      </div>

      <!-- Body -->
      <div class="px-6 py-5">
        <!-- Summary -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
          <div class="text-sm">
            <div class="text-gray-400">NCF</div>
            <div class="text-base font-semibold" x-text="invoiceDetail?.ncf_number ?? '-'"></div>
          </div>

          <div class="text-sm">
            <div class="text-gray-400">Fecha</div>
            <div class="text-base font-semibold" x-text="invoiceDetail?.invoice_date ?? '-'"></div>
          </div>

          <div class="text-sm md:text-right">
            <div class="text-gray-400">Total</div>
            <div class="text-base font-semibold" x-text="formatMoney(invoiceDetail?.total ?? 0)"></div>
          </div>
        </div>

        <!-- Items table -->
        <div class="overflow-x-auto rounded-xl border border-gray-700">
          <table class="min-w-full text-sm">
            <thead class="text-gray-300 bg-gray-800/50">
              <tr class="border-b border-gray-700">
                <th class="py-3 px-4 text-left font-semibold">Fecha</th>
                <th class="py-3 px-4 text-left font-semibold">Paciente</th>
                <th class="py-3 px-4 text-left font-semibold">Afiliado</th>
                <th class="py-3 px-4 text-left font-semibold">Autorización</th>
                <th class="py-3 px-4 text-right font-semibold">Monto</th>
              </tr>
            </thead>

            <tbody class="text-gray-100">
              <template x-if="!invoiceDetail?.items?.length">
                <tr>
                  <td colspan="5" class="py-6 px-4 text-center text-gray-400">
                    No hay servicios cargados.
                  </td>
                </tr>
              </template>

              <template x-for="(it, idx) in (invoiceDetail?.items || [])" :key="idx">
                <tr class="border-b border-gray-800 hover:bg-gray-800/40">
                  <td class="py-3 px-4" x-text="it.service_date ?? '-'"></td>
                  <td class="py-3 px-4" x-text="it.patient_name ?? '-'"></td>
                  <td class="py-3 px-4" x-text="it.affiliate_no ?? '-'"></td>
                  <td class="py-3 px-4" x-text="it.authorization_no ?? '-'"></td>
                  <td class="py-3 px-4 text-right" x-text="formatMoney(it.amount ?? 0)"></td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>

        <div class="mt-6 border-t border-gray-200/30 pt-4">
    <div class="flex items-center justify-between">
        <div class="text-sm font-semibold text-gray-100">Pagos</div>
        <div class="text-sm text-gray-300">
            Pagado: <span class="font-semibold" x-text="formatMoney(invoiceDetail?.paid_amount || 0)"></span>
            <span class="mx-2">|</span>
            Pendiente: <span class="font-semibold" x-text="formatMoney((invoiceDetail?.total_amount || 0) - (invoiceDetail?.paid_amount || 0))"></span>
        </div>
    </div>

    <!-- Registrar pago -->
    <form class="mt-3 grid grid-cols-1 md:grid-cols-5 gap-3"
          :action="`/invoices/${invoiceDetail.id}/payments`"
          method="POST">
        @csrf

        <div>
            <label class="block text-xs text-gray-300 mb-1">Fecha pago</label>
            <input type="date" name="payment_date" required
                   class="w-full rounded-lg border-gray-600 bg-gray-900 text-gray-100">
        </div>

        <div>
            <label class="block text-xs text-gray-300 mb-1">Método</label>
            <select name="method" class="w-full rounded-lg border-gray-600 bg-gray-900 text-gray-100">
                <option value="transfer">Transferencia</option>
                <option value="cash">Efectivo</option>
                <option value="card">Tarjeta</option>
                <option value="check">Cheque</option>
                <option value="other">Otro</option>
            </select>
        </div>

        <div>
            <label class="block text-xs text-gray-300 mb-1">Referencia</label>
            <input type="text" name="reference_no"
                   class="w-full rounded-lg border-gray-600 bg-gray-900 text-gray-100"
                   placeholder="Opcional">
        </div>

        <div>
            <label class="block text-xs text-gray-300 mb-1">Monto</label>
            <input type="number" step="0.01" min="0.01" name="amount" required
                   class="w-full rounded-lg border-gray-600 bg-gray-900 text-gray-100">
        </div>

        <div class="flex items-end">
            <button class="w-full rounded-lg bg-green-600 px-4 py-2 text-white font-medium hover:bg-green-700">
                Registrar
            </button>
        </div>
    </form>

    <!-- Tabla de pagos -->
    <div class="mt-4 overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="text-gray-300">
            <tr class="border-b border-gray-700">
                <th class="py-2 text-left">Fecha</th>
                <th class="py-2 text-left">Método</th>
                <th class="py-2 text-left">Referencia</th>
                <th class="py-2 text-right">Monto</th>
                <th class="py-2 text-right">Acción</th>
            </tr>
            </thead>
            <tbody class="text-gray-100">
            <template x-for="p in (invoiceDetail.payments || [])" :key="p.id">
                <tr class="border-b border-gray-800">
                    <td class="py-2" x-text="p.payment_date"></td>
                    <td class="py-2" x-text="p.method"></td>
                    <td class="py-2 text-gray-300" x-text="p.reference_no || '-'"></td>
                    <td class="py-2 text-right" x-text="formatMoney(p.amount)"></td>
                    <td class="py-2 text-right">
                        <form :action="`/invoices/${invoiceDetail.id}/payments/${p.id}`" method="POST"
                              onsubmit="return confirm('¿Eliminar este pago?');" class="inline">
                            @csrf
                            @method('DELETE')
                            <button class="rounded-lg bg-red-600/20 px-3 py-1 text-red-200 hover:bg-red-600/30">
                                🗑️
                            </button>
                        </form>
                    </td>
                </tr>
            </template>
            </tbody>
        </table>
    </div>
</div>


        <!-- Footer -->
        <div class="mt-6 flex justify-end">
          <button type="button"
                  class="rounded-lg border border-gray-700 px-5 py-2 text-gray-200 hover:bg-gray-800"
                  @click="closeInvoice()">
            Cerrar
          </button>
        </div>
      </div>
    </div>
  </div>
</div>


        </div>

        <script>
            function invoiceQueryPage() {
                return {
                    loading: false,
                    invoices: [],

                    doctors: @js($doctors),
    insurers: [],
                    invoiceModal: false,
                    invoiceDetail: null,

                    filters: {
                        doctor_id: '',
                        insurer_id: '',
                    },

                    async search() {
                        this.loading = true;
                        this.invoices = [];

                        const qs = new URLSearchParams();
                        if (this.filters.doctor_id) qs.append('doctor_id', this.filters.doctor_id);
                        if (this.filters.insurer_id) qs.append('insurer_id', this.filters.insurer_id);

                        const res = await fetch(`{{ route('invoice-query.search') }}?${qs.toString()}`);
                        const json = await res.json();

                        this.loading = false;

                        if (!json.ok) return;
                        this.invoices = json.invoices || [];
                    },

                    reset() {
                        this.filters.doctor_id = '';
                        this.filters.insurer_id = '';
                        this.invoices = [];
                    },

                    formatMoney(n) {
                        const v = (parseFloat(n || 0) || 0);
                        return v.toLocaleString('es-DO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    },

                    async openInvoice(id) {
                        const res = await fetch(`/invoices/${id}`);
                        const json = await res.json();
                        if (!json.ok) return;

                        this.invoiceDetail = json.invoice;
                        this.invoiceModal = true;
                    },

                    closeInvoice() {
                        this.invoiceModal = false;
                        this.invoiceDetail = null;
                    },

                    async onDoctorChange() {
    this.filters.insurer_id = '';
    this.insurers = [];
    this.invoices = [];

    if (!this.filters.doctor_id) return;

    const res = await fetch(`/doctors/${this.filters.doctor_id}/insurers`);
    const json = await res.json();

    if (!json.ok) {
        alert('No se pudieron cargar las ARS del médico');
        return;
    }

    this.insurers = json.insurers;
},


                    async voidInvoice(id) {
                        if (!confirm('¿Anular esta factura?')) return;

                        const res = await fetch(`/invoices/${id}/void`, {
                            method: 'PATCH',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Accept': 'application/json',
                            }
                        });
                        const json = await res.json();

                        if (!json.ok) {
                            alert(json.message || 'No se pudo anular.');
                            return;
                        }

                        // Actualiza fila local
                        const row = this.invoices.find(x => x.id === id);
                        if (row) row.status = 'void';
                    }
                }
            }
        </script>
    </div>
</x-app-layout>
