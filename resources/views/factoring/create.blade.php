<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100">Crear Factoring</h2>

            <a href="{{ route('factorings.index') }}"
                class="rounded-lg border border-gray-300 dark:border-gray-700 px-4 py-2 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700/30">
                Volver
            </a>
        </div>
    </x-slot>

    @php
        // Si vienes a /factorings/create?invoice_id=xx y tú pasas $invoice desde el controller, esto precarga.
        $selectedInvoice = null;
        if (!empty($invoice)) {
            $selectedInvoice = [
                'id' => $invoice->id,
                'ncf' => $invoice->ncf_number,
                'doctor' => $invoice->doctor?->full_name,
                'insurer' => $invoice->insurer?->name,
                'date' => optional($invoice->invoice_date)->toDateString(),
                'total' => (float) $invoice->total_amount,
                'has_factoring' => false, // si precargas uno que no debería, ajústalo
            ];
        }
    @endphp
    

    <div class="py-8" x-data="factoringForm(@json($selectedInvoice))">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

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

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">

                <form method="POST" action="{{ route('factorings.store') }}"
                    class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @csrf

                    {{-- FACTURA (Selector) --}}
                    <div class="md:col-span-2"
     x-data="invoiceSelector({ initial: @js($selectedInvoice) })">

  <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Factura</label>

  <input type="hidden" name="invoice_id" :value="selected?.id ?? ''">

  <div class="relative">
    <input type="text"
           x-model="q"
           :readonly="!!selected"
           @input.debounce.300ms="if(!selected) search()"
           @focus="open = !selected; if(!selected && !results.length) search()"
           @keydown.escape="open=false"
           class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
           placeholder="Buscar por NCF, Doctor, ARS o ID...">

    <!-- Dropdown -->
    <div x-show="open" style="display:none;"
         class="absolute z-50 mt-2 w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-lg max-h-72 overflow-auto">

      <template x-if="loading">
        <div class="p-3 text-sm text-gray-500 dark:text-gray-400">Buscando...</div>
      </template>

      <template x-if="!loading && results.length === 0">
        <div class="p-3 text-sm text-gray-500 dark:text-gray-400">
          No se encontraron facturas disponibles (sin factoring y no anuladas).
        </div>
      </template>

      <template x-for="inv in results" :key="inv.id">
        <button type="button"
                class="w-full text-left px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-800"
                @click="select(inv)">
          <div class="flex items-center justify-between">
            <div class="font-semibold text-gray-900 dark:text-gray-100"
                 x-text="inv.ncf || ('Factura #' + inv.id)"></div>
            <div class="text-sm text-gray-600 dark:text-gray-300"
                 x-text="formatMoney(inv.total)"></div>
          </div>
          <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
            <span x-text="inv.doctor"></span>
            <span class="mx-2">•</span>
            <span x-text="inv.insurer"></span>
            <span class="mx-2">•</span>
            <span x-text="inv.date"></span>
          </div>
        </button>
      </template>
    </div>
  </div>

  <!-- Selected summary -->
  <template x-if="selected">
    <div class="mt-3 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
      <div class="flex items-start justify-between gap-3">
        <div>
          <div class="text-sm text-gray-500 dark:text-gray-400">Factura seleccionada</div>
          <div class="text-lg font-semibold text-gray-900 dark:text-gray-100"
               x-text="selected.ncf || ('Factura #' + selected.id)"></div>
          <div class="text-sm text-gray-600 dark:text-gray-300">
            <span x-text="selected.doctor"></span> — <span x-text="selected.insurer"></span>
          </div>
          <div class="text-sm text-gray-600 dark:text-gray-300">
            Fecha: <span x-text="selected.date"></span>
          </div>
        </div>
        <div class="text-right">
          <div class="text-sm text-gray-500 dark:text-gray-400">Total</div>
          <div class="text-xl font-bold text-gray-900 dark:text-gray-100"
               x-text="formatMoney(selected.total)"></div>
        </div>
      </div>

      <div class="mt-3">
        <button type="button"
                class="rounded-lg bg-gray-700/40 px-3 py-2 text-gray-100 hover:bg-gray-700/60"
                @click="clear()">
          Cambiar factura
        </button>
      </div>
    </div>
  </template>

  <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
    Solo aparecen facturas <b>no anuladas</b> y <b>sin factoring</b>.
  </p>
</div>


                    {{-- Compra --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Fecha
                            compra</label>
                        <input type="date" name="purchase_date" x-model="purchase_date"
                            value="{{ old('purchase_date', now()->toDateString()) }}"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Término
                            (días)</label>
                        <input type="number" name="term_days" x-model.number="term_days"
                            value="{{ old('term_days', 30) }}" min="1" max="365"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                    </div>

                    {{-- Tasas --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">%
                            ISR</label>
                        <input type="number" step="0.01" min="0" max="100" name="discount_rate"
                            x-model.number="discount_rate" value="{{ old('discount_rate', 4) }}"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">%
                            Descuento</label>
                        <input type="number" step="0.01" min="0" max="100" name="commission_rate"
                            x-model.number="commission_rate" value="{{ old('commission_rate', 1) }}"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                    </div>

                    {{-- Opcionales --}}
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Referencia</label>
                        <input type="text" name="reference_no" value="{{ old('reference_no') }}"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                            placeholder="Opcional">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Notas</label>
                        <input type="text" name="notes" value="{{ old('notes') }}"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                            placeholder="Opcional">
                    </div>

                    {{-- Submit --}}
                    <div class="md:col-span-2 flex items-center justify-end gap-3">
                        <button class="rounded-lg bg-green-600 px-4 py-2 text-white font-medium hover:bg-green-700"
                            :disabled="!selectedInvoiceId"
                            :class="!selectedInvoiceId ? 'opacity-50 cursor-not-allowed' : ''">
                            Crear factoring
                        </button>
                    </div>
                </form>

                <div class="mt-6 text-xs text-gray-500 dark:text-gray-400">
                    <div>💡 El neto, descuento y comisión se calculan al guardar según el total de la factura.</div>
                </div>

            </div>
        </div>
    </div>

<script>
  function invoiceSelector({ initial = null } = {}) {
    return {
      q: '',
      open: false,
      loading: false,
      results: [],
      selected: initial,

      init() {
        if (this.selected) {
          this.q = this.selected.ncf || ('Factura #' + this.selected.id);
          this.open = false;
        }
      },

      async search() {
        this.open = true;
        this.loading = true;

        try {
          const url = `{{ route('invoices.search') }}?q=${encodeURIComponent(this.q || '')}`;
          const res = await fetch(url, { headers: { 'Accept': 'application/json' } });

          if (!res.ok) {
            console.error('Search HTTP error:', res.status, await res.text());
            this.results = [];
            return;
          }

          const json = await res.json();
          this.results = json.ok ? (json.invoices || []) : [];
        } catch (e) {
          console.error('Search exception:', e);
          this.results = [];
        } finally {
          this.loading = false;
        }
      },

      select(inv) {
        this.selected = inv;
        this.q = inv.ncf || ('Factura #' + inv.id);
        this.open = false;
      },

      clear() {
        this.selected = null;
        this.q = '';
        this.results = [];
        this.open = true;
        this.search();
      },

      formatMoney(n) {
        const v = (parseFloat(n || 0) || 0);
        return v.toLocaleString('es-DO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      }
    }
  }
</script>

</x-app-layout>
