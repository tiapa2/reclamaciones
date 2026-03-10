<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Facturación') }}
        </h2>
    </x-slot>

    <div class="py-8" x-data="invoicePage()">
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
            @role('admin')
            {{-- FORM --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('invoices.store') }}">
                    @csrf

                    <input type="hidden" name="doctor_id" :value="form.doctor_id">
                    <input type="hidden" name="ncf_type_id" :value="form.ncf_type_id">

                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Médico</label>
                            <select x-model="form.doctor_id" @change="onDoctorChange()"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                                <option value="">Seleccionar médico</option>
                                @foreach ($doctors as $d)
                                    <option value="{{ $d->id }}">{{ $d->full_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">ARS</label>
                            <select name="insurer_id" x-model="form.insurer_id"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                                <option value="">Seleccione ARS</option>
                                <template x-for="i in insurers" :key="i.id">
                                    <option :value="i.id" x-text="i.name"></option>
                                </template>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Tipo
                                NCF</label>
                            <select x-model="form.ncf_type_id" @change="previewNcf()"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                                <option value="">Seleccione tipo</option>
                                <template x-for="t in ncfTypes" :key="t.id">
                                    <option :value="t.id" x-text="`${t.name} (${t.prefix})`"></option>
                                </template>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Fecha</label>
                            <input type="date" x-model="form.invoice_date" name="invoice_date"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">NCF
                                (preview)</label>
                            <input type="text" x-model="ncfPreview" readonly
                                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 opacity-80">
                            <p class="text-xs mt-1" :class="previewError ? 'text-red-400' : 'text-gray-400'"
                                x-text="previewError"></p>
                        </div>
                    </div>

                    {{-- ITEMS --}}
                    <div class="mt-6">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">Servicios</div>
                            <button type="button" @click="addRow()"
                                class="rounded-lg bg-blue-600 px-3 py-2 text-white text-sm hover:bg-blue-700">
                                + Agregar
                            </button>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="text-gray-600 dark:text-gray-300">
                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                        <th class="py-2 text-left">Fecha</th>
                                        <th class="py-2 text-left">Paciente</th>
                                        <th class="py-2 text-left">Afiliado</th>
                                        <th class="py-2 text-left">Autorización</th>
                                        <th class="py-2 text-left">Monto</th>
                                        <th class="py-2 text-right">Acción</th>
                                    </tr>
                                </thead>

                                <tbody class="text-gray-800 dark:text-gray-100">
                                    <template x-for="(row, idx) in form.items" :key="idx">
                                        <tr class="border-b border-gray-200 dark:border-gray-700 align-top">
                                            <td class="py-2">
                                                <input type="date" :name="`items[${idx}][service_date]`"
                                                    x-model="row.service_date"
                                                    class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                                            </td>

                                            <td class="py-2">
                                                <input type="text" :name="`items[${idx}][patient_name]`"
                                                    x-model="row.patient_name"
                                                    class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                                    placeholder="Nombre del paciente">
                                            </td>

                                            <td class="py-2">
                                                <input type="text" :name="`items[${idx}][affiliate_no]`"
                                                    x-model="row.affiliate_no"
                                                    class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                                    placeholder="No. Afiliado">
                                            </td>

                                            <td class="py-2">
                                                <input type="text" :name="`items[${idx}][authorization_no]`"
                                                    x-model="row.authorization_no"
                                                    class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                                    placeholder="No. Autorización">
                                            </td>

                                            <td class="py-2">
                                                <input type="number" step="0.01" min="0.01"
                                                    :name="`items[${idx}][amount]`" x-model.number="row.amount"
                                                    class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                                    placeholder="Monto">
                                            </td>

                                            <td class="py-2 text-right">
                                                <button type="button" @click="removeRow(idx)"
                                                    class="rounded-lg bg-red-600 px-4 py-2 text-white font-medium hover:bg-red-700"
                                                    title="Eliminar fila">
                                                    Eliminar
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4 flex justify-end">
                            <div class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                Total: <span x-text="formatMoney(total())"></span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button class="rounded-lg bg-green-600 px-4 py-2 text-white font-medium hover:bg-green-700">
                            Generar factura
                        </button>
                    </div>
                </form>
            </div>
            @endrole
            {{-- LISTADO --}}
            <div class="mt-6 bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center justify-between gap-3">
                    <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">Últimas facturas</div>

                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        Tip: “Anular” NO devuelve el NCF (queda consumido).
                    </div>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-gray-600 dark:text-gray-300">
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="py-2 text-left">#</th>
                                <th class="py-2 text-left">NCF</th>
                                <th class="py-2 text-left">Médico</th>
                                <th class="py-2 text-left">ARS</th>
                                <th class="py-2 text-left">Tipo</th>
                                <th class="py-2 text-left">Total</th>
                                <th class="py-2 text-left">Estado</th>
                                <th class="py-2 text-left">Acciones</th>
                            </tr>
                        </thead>

                        <tbody class="text-gray-800 dark:text-gray-100">
                            @foreach ($invoices as $inv)
                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                    <td class="py-2">{{ $inv->id }}</td>
                                    <td class="py-2 font-medium">{{ $inv->ncf_number }}</td>
                                    <td class="py-2">{{ $inv->doctor?->full_name }}</td>
                                    <td class="py-2">{{ $inv->insurer?->name }}</td>
                                    <td class="py-2">{{ $inv->ncfType?->name }}</td>
                                    <td class="py-2">{{ number_format((float) $inv->total_amount, 2) }}</td>
                                    <td class="py-2">
                                        <span
                                            class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold
                                            {{ $inv->status === 'issued' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-200' : '' }}
                                            {{ $inv->status === 'draft' ? 'bg-gray-100 text-gray-800 dark:bg-gray-700/40 dark:text-gray-100' : '' }}
                                            {{ $inv->status === 'void' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-200' : '' }}">
                                            {{ $inv->status }}
                                        </span>
                                    </td>
                                    <td class="py-2 space-x-2">
                                        <a href="{{ route('invoices.view', $inv) }}"
                                            class="rounded-lg bg-gray-600 px-4 py-2 text-white font-medium hover:bg-gray-700">
                                            Ver
                                        </a>

                                        @role('admin')
                                        @if ($inv->status !== 'void')
                                            <form method="POST" action="{{ route('invoices.void', $inv) }}"
                                                class="inline" onsubmit="return confirm('¿Anular esta factura?');">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit"
                                                    class="rounded-lg bg-red-600 px-4 py-2 text-white font-medium hover:bg-red-700">
                                                    Anular
                                                </button>
                                            </form>
                                        @endif
                                        @endrole
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">{{ $invoices->links() }}</div>
            </div>

        </div>

    </div>

    <script>
        function invoicePage() {
            return {
                insurers: [],
                ncfTypes: [],
                ncfPreview: '',
                previewError: '',

                invoiceModal: false,
                invoiceDetail: null,

                form: {
                    doctor_id: '',
                    insurer_id: '',
                    ncf_type_id: '',
                    invoice_date: new Date().toISOString().slice(0, 10),
                    items: [{
                        service_date: '',
                        patient_name: '',
                        affiliate_no: '',
                        authorization_no: '',
                        amount: 0
                    }]
                },

                async onDoctorChange() {
                    this.insurers = [];
                    this.ncfTypes = [];
                    this.form.insurer_id = '';
                    this.form.ncf_type_id = '';
                    this.ncfPreview = '';
                    this.previewError = '';

                    if (!this.form.doctor_id) return;

                    const res = await fetch(`{{ route('invoices.doctor-data') }}?doctor_id=${this.form.doctor_id}`);
                    const json = await res.json();

                    if (!json.ok) {
                        this.previewError = json.message || 'No se pudo cargar data del doctor.';
                        return;
                    }

                    this.insurers = json.insurers || [];
                    this.ncfTypes = json.ncf_types || [];
                },

                async previewNcf() {
                    this.ncfPreview = '';
                    this.previewError = '';

                    if (!this.form.doctor_id || !this.form.ncf_type_id) return;

                    const url =
                        `{{ route('invoices.preview-ncf') }}?doctor_id=${this.form.doctor_id}&ncf_type_id=${this.form.ncf_type_id}`;
                    const res = await fetch(url);
                    const json = await res.json();

                    if (!json.ok) {
                        this.previewError = json.message || 'No se pudo previsualizar el NCF.';
                        return;
                    }

                    this.ncfPreview = json.ncf_number;
                },

                addRow() {
                    this.form.items.push({
                        service_date: '',
                        patient_name: '',
                        affiliate_no: '',
                        authorization_no: '',
                        amount: 0
                    });
                },

                removeRow(idx) {
                    if (this.form.items.length === 1) return;
                    this.form.items.splice(idx, 1);
                },

                total() {
                    return this.form.items.reduce((sum, r) => sum + (parseFloat(r.amount || 0) || 0), 0);
                },

                formatMoney(n) {
                    const v = (parseFloat(n || 0) || 0);
                    return v.toLocaleString('es-DO', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            }
        }
    </script>
</x-app-layout>
