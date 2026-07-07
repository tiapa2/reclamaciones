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
            @hasanyrole('admin|analista')
            {{-- FORM --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('invoices.store') }}">
                    @csrf

                    <input type="hidden" name="doctor_id" :value="form.doctor_id">
                    <input type="hidden" name="ncf_type_id" :value="form.ncf_type_id">
                    <input type="hidden" name="invoice_type" :value="form.invoice_type">

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
                                    <option :value="t.id"
                                        x-text="t.remaining !== undefined ? `${t.name} (${t.prefix}) · ${t.remaining} disp.` : `${t.name} (${t.prefix})`"></option>
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

                    {{-- TIPO DE FACTURA --}}
                    <div class="mt-5 flex items-center gap-6">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Tipo de factura:</span>
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="radio" x-model="form.invoice_type" value="ars" class="accent-indigo-600">
                            <span class="text-sm text-gray-800 dark:text-gray-200">ARS (Seguro)</span>
                        </label>
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="radio" x-model="form.invoice_type" value="clinica" class="accent-indigo-600">
                            <span class="text-sm text-gray-800 dark:text-gray-200">Clínica</span>
                        </label>
                    </div>

                    {{-- ITEMS --}}
                    <div class="mt-4">
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
                                        <th class="py-2 text-left" x-show="form.invoice_type === 'ars'">Paciente</th>
                                        <th class="py-2 text-left" x-show="form.invoice_type === 'ars'">Afiliado</th>
                                        <th class="py-2 text-left" x-show="form.invoice_type === 'ars'">Autorización</th>
                                        <th class="py-2 text-left" x-show="form.invoice_type === 'clinica'">Descripción</th>
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

                                            {{-- ARS fields --}}
                                            <td class="py-2" x-show="form.invoice_type === 'ars'">
                                                <input type="text" :name="`items[${idx}][patient_name]`"
                                                    x-model="row.patient_name"
                                                    class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                                    placeholder="Nombre del paciente">
                                            </td>

                                            <td class="py-2" x-show="form.invoice_type === 'ars'">
                                                <input type="text" :name="`items[${idx}][affiliate_no]`"
                                                    x-model="row.affiliate_no"
                                                    class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                                    placeholder="No. Afiliado">
                                            </td>

                                            <td class="py-2" x-show="form.invoice_type === 'ars'">
                                                <input type="text" :name="`items[${idx}][authorization_no]`"
                                                    x-model="row.authorization_no"
                                                    class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                                    placeholder="No. Autorización">
                                            </td>

                                            {{-- Clínica field --}}
                                            <td class="py-2" x-show="form.invoice_type === 'clinica'">
                                                <input type="text" :name="`items[${idx}][description]`"
                                                    x-model="row.description"
                                                    class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                                    placeholder="Descripción del servicio">
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
                            <div class="w-full max-w-xs space-y-2">
                                <div class="flex items-center justify-between gap-4">
                                    <span class="text-sm text-gray-600 dark:text-gray-300">Total servicios</span>
                                    <span class="font-semibold text-gray-900 dark:text-gray-100"
                                        x-text="formatMoney(total())"></span>
                                </div>
                                <div class="flex items-center justify-between gap-4">
                                    <label class="text-sm text-gray-600 dark:text-gray-300">Retención ISR (%)</label>
                                    <input type="number" step="0.01" min="0" max="100" name="isr_retention_pct"
                                        x-model="form.isr_retention_pct" placeholder="0"
                                        class="w-24 rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 text-right">
                                </div>
                                <div class="flex items-center justify-between gap-4" x-show="isrAmount() > 0">
                                    <span class="text-sm text-amber-600 dark:text-amber-400">Retención ISR</span>
                                    <span class="text-amber-600 dark:text-amber-400 font-medium"
                                        x-text="'- ' + formatMoney(isrAmount())"></span>
                                </div>
                                <div class="flex items-center justify-between gap-4 border-t border-gray-200 dark:border-gray-700 pt-2">
                                    <span class="text-base font-semibold text-gray-900 dark:text-gray-100">Neto a recibir</span>
                                    <span class="text-lg font-bold text-gray-900 dark:text-gray-100"
                                        x-text="formatMoney(netTotal())"></span>
                                </div>
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
            @endhasanyrole
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
                                    <td class="py-2 font-medium">
                                        {{ $inv->kontab_ncf ?? $inv->ncf_number }}
                                        @if($inv->kontab_invoice_id)
                                            @php
                                                $dgii = $inv->kontab_dgii_status;
                                                $dgiiClass = match($dgii) {
                                                    'accepted' => 'bg-emerald-100 text-emerald-800',
                                                    'rejected' => 'bg-red-100 text-red-800',
                                                    'pending' => 'bg-amber-100 text-amber-800',
                                                    default => 'bg-slate-100 text-slate-700',
                                                };
                                                $dgiiLabel = match($dgii) {
                                                    'accepted' => 'DGII ✓',
                                                    'rejected' => 'DGII ✗',
                                                    'pending' => 'DGII…',
                                                    default => 'DGII —',
                                                };
                                            @endphp
                                            <span class="ml-1 inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold bg-blue-100 text-blue-800" title="Factura electrónica vía Kontab">e-CF</span>
                                            <span class="ml-1 inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $dgiiClass }}">{{ $dgiiLabel }}</span>
                                        @endif
                                    </td>
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

                                        @hasanyrole('admin|analista')
                                        @if ($inv->status !== 'void')
                                            @if($inv->isLocked())
                                                <button type="button" disabled
                                                    title="Factura ya en kontab-erp / DGII — anular requiere nota de crédito"
                                                    class="rounded-lg bg-gray-300 px-4 py-2 text-gray-500 font-medium cursor-not-allowed">
                                                    Anular
                                                </button>
                                            @else
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
                                        @endif
                                        @endhasanyrole
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
                    invoice_type: 'ars',
                    isr_retention_pct: '',
                    items: [{
                        service_date: '',
                        description: '',
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

                    // Médico electrónico: el número lo asigna kontab-erp al emitir.
                    // Mostramos el próximo e-NCF proyectado (next_ncf) que vino del selector,
                    // sin consultar la autorización local (que no aplica).
                    const t = this.ncfTypes.find(x => String(x.id) === String(this.form.ncf_type_id));
                    if (t && t.next_ncf) {
                        this.ncfPreview = t.next_ncf + ' (lo asigna Kontab)';
                        return;
                    }

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
                        description: '',
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

                isrAmount() {
                    const pct = parseFloat(this.form.isr_retention_pct || 0) || 0;
                    return Math.round(this.total() * pct) / 100;
                },

                netTotal() {
                    return this.total() - this.isrAmount();
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
