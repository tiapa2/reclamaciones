<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100">Detalle de factura</h2>

            <div class="flex items-center gap-2">
                @php
                    $invoice->loadMissing('factoring');
                @endphp

                @hasanyrole('admin|analista')
                    @if ($invoice->status !== 'void')
                        @if ($invoice->factoring)
                            <a href="{{ route('factorings.show', $invoice->factoring) }}"
                                class="rounded-lg bg-blue-600 px-4 py-2 text-white font-medium hover:bg-blue-700">
                                Ver Factoring
                            </a>
                        @else
                            <a href="{{ route('factorings.create', ['invoice_id' => $invoice->id]) }}"
                                class="rounded-lg bg-blue-600 px-4 py-2 text-white font-medium hover:bg-blue-700">
                                Crear Factoring
                            </a>
                        @endif
                    @endif

                @endhasanyrole

                {{-- Reenviar a kontab-erp: solo médicos electrónicos, factura no anulada y
                     que aún no se creó en kontab (sin kontab_invoice_id). --}}
                @hasanyrole('admin|analista')
                    @if ($invoice->doctor?->e_invoicing_enabled && $invoice->status !== 'void' && ! $invoice->kontab_invoice_id)
                        <form action="{{ route('invoices.resend-kontab', $invoice) }}" method="POST"
                            onsubmit="this.querySelector('button').disabled=true; this.querySelector('button').innerText='Reenviando…';">
                            @csrf
                            <button type="submit"
                                class="rounded-lg bg-amber-600 px-4 py-2 text-white font-medium hover:bg-amber-700"
                                title="Reenviar esta factura a kontab-erp para emitir el e-CF">
                                Reenviar a Kontab
                            </button>
                        </form>
                    @endif
                @endhasanyrole

                <a href="{{ route('invoices.pdf', $invoice) }}"
                target="_blank"
                    class="rounded-lg bg-green-600 px-4 py-2 text-white font-medium hover:bg-green-700">
                    Ver PDF
                </a>

                <a href="{{ route('invoices.index') }}"
                    class="rounded-lg border border-gray-300 dark:border-gray-700 px-4 py-2 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700/30">
                    Volver
                </a>
            </div>
        </div>
    </x-slot>

    @php
        // Normaliza data para Alpine (evita nulls raros, castea fechas, etc.)
        $itemsForJs = $invoice->items
            ->map(
                fn($it) => [
                    'id' => $it->id,
                    'service_date' => optional($it->service_date)->toDateString(),
                    'description' => $it->description,
                    'patient_name' => $it->patient_name,
                    'affiliate_no' => $it->affiliate_no,
                    'authorization_no' => $it->authorization_no,
                    'amount' => (float) $it->amount,
                    '_edit' => false,
                ],
            )
            ->values();

        // Si quieres mostrar "Factura No." como FT00000054 por ejemplo:
        $invoiceNo = 'FT' . str_pad((string) $invoice->id, 8, '0', STR_PAD_LEFT);
    @endphp

    <div class="py-8" x-data="invoiceViewPage({
        invoiceId: {{ $invoice->id }},
        invoiceType: @js($invoice->invoice_type ?? 'ars'),
        items: @js($itemsForJs),
        insurerName: @js($invoice->insurer?->name ?? ''),
        totalAmount: {{ (float) $invoice->total_amount }},
        routes: {
            updateItemBase: @js(url('/invoice-items')),
            storeItem: @js(route('invoice-items.store', $invoice)),
        }
    })">

        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-8">

                {{-- Mensajes --}}
                <template x-if="flash.message">
                    <div class="mb-4 rounded-lg p-4"
                        :class="flash.type === 'success' ?
                            'bg-green-50 text-green-800 dark:bg-green-900/30 dark:text-green-200' :
                            'bg-red-50 text-red-800 dark:bg-red-900/30 dark:text-red-200'">
                        <span x-text="flash.message"></span>
                    </div>
                </template>

                {{-- Encabezado --}}
                <div class="flex items-start justify-between gap-6">
                    <div>
                        <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                            {{ $invoice->doctor?->full_name }}
                        </div>

                        <div class="mt-4 text-sm text-gray-700 dark:text-gray-300 space-y-1">
                            <div>
                                <span class="font-semibold">RNC:</span> {{ $invoice->doctor?->rnc ?? '-' }}
                                <span class="mx-2">|</span>
                                <span class="font-semibold">Tel.:</span> {{ $invoice->doctor?->phone ?? '-' }}
                            </div>
                            <div><span class="font-semibold">Email:</span> {{ $invoice->doctor?->email ?? '-' }}</div>
                        </div>
                    </div>

                    <div class="text-right">
                        <div class="text-xl font-bold text-sky-500">Factura Crédito Fiscal</div>

                        <div class="mt-2 text-sm text-gray-800 dark:text-gray-200 space-y-1">
                            <div><span class="font-semibold">NCF:</span> {{ $invoice->kontab_ncf ?? $invoice->ncf_number ?: '-' }}</div>

                            @if ($invoice->kontab_invoice_id)
                                <div><span class="font-semibold">DGII:</span>
                                    <span class="uppercase">{{ $invoice->kontab_dgii_status ?? 'pendiente' }}</span>
                                </div>
                                @if ($invoice->kontab_security_code)
                                    <div><span class="font-semibold">Código Seguridad:</span> {{ $invoice->kontab_security_code }}</div>
                                @endif
                            @else
                                <div><span class="font-semibold">Válido Hasta:</span> -</div>
                            @endif

                            <div><span class="font-semibold">Factura No.:</span> {{ $invoiceNo }}</div>
                            <div><span class="font-semibold">Fecha:</span>
                                {{ optional($invoice->invoice_date)->toFormattedDateString() }}</div>
                            <div class="font-semibold">Condición 30 Días</div>
                        </div>
                    </div>
                </div>

                {{-- Bloque aseguradora --}}
                <div
                    class="mt-8 rounded-md border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 p-4 flex items-center justify-between">
                    <div class="text-gray-800 dark:text-gray-100">
                        <span class="font-semibold">Aseguradora:</span> {{ $invoice->insurer?->name }}
                    </div>
                    <div class="text-sm text-gray-700 dark:text-gray-300 space-y-1 text-right">
                        <div><span class="font-semibold">RNC:</span> {{ $invoice->insurer?->rnc ?? '-' }}</div>
                        <div><span class="font-semibold">Tel.:</span> {{ $invoice->insurer?->phone ?? '-' }}</div>
                    </div>
                </div>

                {{-- Aviso de bloqueo: factura electrónica ya emitida a kontab/DGII --}}
                @if ($invoice->isLocked())
                    <div class="mt-6 rounded-lg border border-amber-300 bg-amber-50 dark:border-amber-700 dark:bg-amber-900/20 px-4 py-3 text-sm text-amber-800 dark:text-amber-200">
                        🔒 Comprobante electrónico emitido (e-NCF {{ $invoice->kontab_ncf ?? '' }}). No se puede editar ni agregar líneas. Para corregir, anula y emite una nota de crédito.
                    </div>
                @endif

                {{-- Tabla items --}}
                @hasanyrole('admin|analista')
                    @unless ($invoice->isLocked())
                        <div class="mt-6 flex justify-end">
                            <button type="button"
                                class="rounded-lg bg-indigo-600 px-3 py-2 text-white text-sm font-medium hover:bg-indigo-700"
                                @click="showAddForm = !showAddForm">
                                <span x-show="!showAddForm">+ Agregar línea</span>
                                <span x-show="showAddForm">— Cancelar</span>
                            </button>
                        </div>
                    @endunless
                @endhasanyrole

                <div class="mt-2 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="bg-green-200 text-gray-800">
                                <th class="px-4 py-3 text-left" x-show="invoiceType === 'ars'">AUTORIZACIÓN</th>
                                <th class="px-4 py-3 text-left" x-show="invoiceType === 'ars'">PACIENTE</th>
                                <th class="px-4 py-3 text-left">FECHA</th>
                                <th class="px-4 py-3 text-left" x-show="invoiceType === 'ars'">AFILIADO</th>
                                <th class="px-4 py-3 text-left" x-show="invoiceType === 'clinica'">DESCRIPCIÓN</th>
                                <th class="px-4 py-3 text-right">COBERTURA</th>
                                @hasanyrole('admin|analista')
                                    @unless ($invoice->isLocked())
                                        <th class="px-4 py-3 text-right">ACCIONES</th>
                                    @endunless
                                @endhasanyrole
                            </tr>
                        </thead>

                        <tbody class="text-gray-800 dark:text-gray-100">
                            <template x-if="items.length === 0">
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                        Esta factura no tiene detalles (items).
                                    </td>
                                </tr>
                            </template>

                            <template x-for="(it, idx) in items" :key="it.id">
                                <tr class="border-b border-gray-200 dark:border-gray-700 align-top">

                                    {{-- Autorización (solo ARS) --}}
                                    <td class="px-4 py-3" x-show="invoiceType === 'ars'">
                                        <template x-if="!it._edit">
                                            <span x-text="it.authorization_no || '-'"></span>
                                        </template>
                                        <template x-if="it._edit">
                                            <input x-model="it.authorization_no"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                                placeholder="Autorización">
                                        </template>
                                    </td>

                                    {{-- Paciente (solo ARS) --}}
                                    <td class="px-4 py-3" x-show="invoiceType === 'ars'">
                                        <template x-if="!it._edit">
                                            <span x-text="it.patient_name || '-'"></span>
                                        </template>
                                        <template x-if="it._edit">
                                            <input x-model="it.patient_name"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                                placeholder="Paciente">
                                        </template>
                                    </td>

                                    {{-- Fecha --}}
                                    <td class="px-4 py-3">
                                        <template x-if="!it._edit">
                                            <span x-text="it.service_date || '-'"></span>
                                        </template>
                                        <template x-if="it._edit">
                                            <input type="date" x-model="it.service_date"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                                        </template>
                                    </td>

                                    {{-- Afiliado (solo ARS) --}}
                                    <td class="px-4 py-3" x-show="invoiceType === 'ars'">
                                        <template x-if="!it._edit">
                                            <span x-text="it.affiliate_no || '-'"></span>
                                        </template>
                                        <template x-if="it._edit">
                                            <input x-model="it.affiliate_no"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                                placeholder="Afiliado">
                                        </template>
                                    </td>

                                    {{-- Descripción (solo clínica) --}}
                                    <td class="px-4 py-3" x-show="invoiceType === 'clinica'">
                                        <template x-if="!it._edit">
                                            <span x-text="it.description || '-'"></span>
                                        </template>
                                        <template x-if="it._edit">
                                            <input x-model="it.description"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                                placeholder="Descripción del servicio">
                                        </template>
                                    </td>

                                    {{-- Monto --}}
                                    <td class="px-4 py-3 text-right">
                                        <template x-if="!it._edit">
                                            <span x-text="formatMoney(it.amount)"></span>
                                        </template>
                                        <template x-if="it._edit">
                                            <input type="number" step="0.01" min="0.01"
                                                x-model.number="it.amount"
                                                class="w-32 text-right rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                                        </template>
                                    </td>

                                    {{-- Acciones (ocultas si la factura está bloqueada por e-CF) --}}
                                    @hasanyrole('admin|analista')
                                        @unless ($invoice->isLocked())
                                        <td class="px-4 py-3 text-right space-x-2">
                                            <template x-if="!it._edit">
                                                <span class="inline-flex gap-2">
                                                    <button type="button"
                                                        class="rounded-lg bg-gray-600 px-4 py-2 text-white font-medium hover:bg-gray-700"
                                                        @click="startEdit(it)">
                                                        Editar
                                                    </button>
                                                    <button type="button"
                                                        class="rounded-lg bg-red-600 px-4 py-2 text-white font-medium hover:bg-red-700"
                                                        @click="deleteItem(it)">
                                                        Eliminar
                                                    </button>
                                                </span>
                                            </template>

                                            <template x-if="it._edit">
                                                <button type="button"
                                                    class="rounded-lg bg-green-600/20 px-3 py-2 text-green-700 dark:text-green-200 hover:bg-green-600/30"
                                                    :disabled="savingId === it.id" @click="saveItem(it)">
                                                    <span x-show="savingId !== it.id">Guardar</span>
                                                    <span x-show="savingId === it.id">Guardando...</span>
                                                </button>
                                            </template>

                                            <template x-if="it._edit">
                                                <button type="button"
                                                    class="rounded-lg bg-gray-600/20 px-3 py-2 text-gray-700 dark:text-gray-200 hover:bg-gray-600/30"
                                                    :disabled="savingId === it.id" @click="cancelEdit(it)">
                                                    Cancelar
                                                </button>
                                            </template>
                                        </td>
                                        @endunless
                                    @endhasanyrole
                                </tr>
                            </template>

                            {{-- Fila nueva línea --}}
                            @hasanyrole('admin|analista')
                            <template x-if="showAddForm">
                                <tr class="border-t-2 border-indigo-400 bg-indigo-50/30 dark:bg-indigo-900/10 align-top">
                                    {{-- ARS: autorización --}}
                                    <td class="px-4 py-3" x-show="invoiceType === 'ars'">
                                        <input x-model="newItem.authorization_no"
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 text-sm"
                                            placeholder="Autorización">
                                    </td>
                                    {{-- ARS: paciente --}}
                                    <td class="px-4 py-3" x-show="invoiceType === 'ars'">
                                        <input x-model="newItem.patient_name"
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 text-sm"
                                            placeholder="Paciente *">
                                    </td>
                                    {{-- Fecha (siempre) --}}
                                    <td class="px-4 py-3">
                                        <input type="date" x-model="newItem.service_date"
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 text-sm">
                                    </td>
                                    {{-- ARS: afiliado --}}
                                    <td class="px-4 py-3" x-show="invoiceType === 'ars'">
                                        <input x-model="newItem.affiliate_no"
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 text-sm"
                                            placeholder="Afiliado">
                                    </td>
                                    {{-- Clínica: descripción --}}
                                    <td class="px-4 py-3" x-show="invoiceType === 'clinica'">
                                        <input x-model="newItem.description"
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 text-sm"
                                            placeholder="Descripción *">
                                    </td>
                                    {{-- Monto (siempre) --}}
                                    <td class="px-4 py-3 text-right">
                                        <input type="number" step="0.01" min="0.01"
                                            x-model.number="newItem.amount"
                                            class="w-32 text-right rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 text-sm"
                                            placeholder="0.00">
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <button type="button"
                                            class="rounded-lg bg-indigo-600 px-4 py-2 text-white text-sm font-medium hover:bg-indigo-700"
                                            :disabled="addingItem" @click="addItem()">
                                            <span x-show="!addingItem">Guardar</span>
                                            <span x-show="addingItem">Guardando...</span>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                            @endhasanyrole
                        </tbody>
                    </table>
                </div>

                {{-- Totales + firmas --}}
                <div class="mt-6 flex items-start justify-between">
                    <div class="text-sm text-gray-800 dark:text-gray-200 font-semibold">
                        <span x-text="items.length"></span> Servicios
                        <div class="mt-2 text-2xl font-bold text-gray-900 dark:text-gray-100"
                            x-text="`$${formatMoneyRaw(total())}`"></div>
                    </div>

                    <div class="text-right text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Total Facturado por <span x-text="insurerName"></span>:
                        <span class="ml-3 text-3xl font-bold" x-text="`$${formatMoneyRaw(total())}`"></span>
                    </div>
                </div>

                <div class="mt-16 grid grid-cols-3 gap-10 text-center text-sm text-gray-700 dark:text-gray-300">
                    <div>
                        <div class="border-t border-gray-400/60 pt-2">ENTREGADO POR</div>
                    </div>
                    <div>
                        <div class="border-t border-gray-400/60 pt-2">RECIBIDO POR</div>
                    </div>
                    <div>
                        <div class="border-t border-gray-400/60 pt-2">FIRMA Y SELLO</div>
                    </div>
                </div>

            </div>

            {{-- =========================
     TARJETA PAGOS (APARTE)
========================= --}}
            @php
                $paid = (float) ($invoice->paid_amount ?? 0);
                $total = (float) ($invoice->total_amount ?? 0);
                $pending = max(0, $total - $paid);

                $status = $pending <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid');
            @endphp

            <div class="mt-8 bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <div class="text-lg font-semibold text-gray-900 dark:text-gray-100">Pagos</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            Historial y registro de pagos asociados a esta factura.
                        </div>
                    </div>

                    <div class="text-sm text-gray-700 dark:text-gray-200">
                        <span class="mr-3">
                            Pagado: <span class="font-semibold">${{ number_format($paid, 2) }}</span>
                        </span>
                        <span class="mr-3">
                            Pendiente: <span class="font-semibold">${{ number_format($pending, 2) }}</span>
                        </span>

                        <span
                            class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold
        {{ $status === 'paid' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-200' : '' }}
        {{ $status === 'partial' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-200' : '' }}
        {{ $status === 'unpaid' ? 'bg-gray-100 text-gray-800 dark:bg-gray-700/40 dark:text-gray-100' : '' }}
      ">
                            {{ $status }}
                        </span>
                    </div>
                </div>
                @hasanyrole('admin|analista')
                    {{-- Form registrar pago --}}
                    <form class="mt-4 grid grid-cols-1 md:grid-cols-5 gap-3"
                        action="{{ route('invoices.payments.store', $invoice) }}" method="POST">
                        @csrf

                        <div>
                            <label class="block text-xs text-gray-600 dark:text-gray-300 mb-1">Fecha pago</label>
                            <input type="date" name="payment_date" required value="{{ now()->toDateString() }}"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                        </div>

                        <div>
                            <label class="block text-xs text-gray-600 dark:text-gray-300 mb-1">Método</label>
                            <select name="method"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                                <option value="transfer">Transferencia</option>
                                <option value="cash">Efectivo</option>
                                <option value="card">Tarjeta</option>
                                <option value="check">Cheque</option>
                                <option value="other">Otro</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs text-gray-600 dark:text-gray-300 mb-1">Referencia</label>
                            <input type="text" name="reference_no"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                placeholder="Opcional">
                        </div>

                        <div>
                            <label class="block text-xs text-gray-600 dark:text-gray-300 mb-1">Monto</label>
                            <input type="number" step="0.01" min="0.01" name="amount" required
                                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                        </div>

                        <div class="flex items-end">
                            <button
                                class="w-full rounded-lg bg-green-600 px-4 py-2 text-white font-medium hover:bg-green-700">
                                Registrar pago
                            </button>
                        </div>
                    </form>
                @endhasanyrole
                {{-- Tabla pagos --}}
                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-gray-600 dark:text-gray-300">
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="py-2 text-left">Fecha</th>
                                <th class="py-2 text-left">Método</th>
                                <th class="py-2 text-left">Referencia</th>
                                <th class="py-2 text-right">Monto</th>
                                @hasanyrole('admin|analista')
                                    <th class="py-2 text-right">Acción</th>
                                @endhasanyrole
                            </tr>
                        </thead>
                        <tbody class="text-gray-800 dark:text-gray-100">
                            @forelse($invoice->payments as $p)
                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                    <td class="py-2">{{ optional($p->payment_date)->toDateString() }}</td>
                                    <td class="py-2">{{ $p->method }}</td>
                                    <td class="py-2 text-gray-500 dark:text-gray-300">{{ $p->reference_no ?: '-' }}
                                    </td>
                                    <td class="py-2 text-right">${{ number_format((float) $p->amount, 2) }}</td>
                                    @hasanyrole('admin|analista')
                                        <td class="py-2 text-right">
                                            <form action="{{ route('invoices.payments.destroy', [$invoice, $p]) }}"
                                                method="POST" onsubmit="return confirm('¿Eliminar este pago?');"
                                                class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    class="rounded-lg bg-red-600 px-4 py-2 text-white font-medium hover:bg-red-700">
                                                    Eliminar
                                                </button>
                                            </form>
                                        </td>
                                    @endhasanyrole
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-6 text-center text-gray-500 dark:text-gray-400">
                                        No hay pagos registrados para esta factura.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @php
                $openPayload = $openRec
                    ? [
                        'id' => $openRec->id,
                        'status' => $openRec->status,
                        'started_at' => optional($openRec->started_at)->toDateString(),
                        'closed_at' => optional($openRec->closed_at)->toDateString(),
                        'reference_no' => $openRec->reference_no,
                        'notes' => $openRec->notes,
                        'items' => $openRec->items
                            ->map(
                                fn($it) => [
                                    'id' => $it->id,
                                    'authorization_no' => $it->authorization_no,
                                    'patient_name' => $it->patient_name,
                                    'affiliate_no' => $it->affiliate_no,
                                    'service_date' => optional($it->service_date)->toDateString(),
                                    'amount_billed' => (string) $it->amount_billed,
                                    'amount_paid' => (string) $it->amount_paid,
                                    'adjustment_amount' => (string) $it->adjustment_amount,
                                    'balance' => (string) $it->balance,
                                    'status' => $it->status,
                                    'paid_at' => optional($it->paid_at)->toDateString(),
                                    'claim_no' => $it->claim_no,
                                    'reason_code' => $it->reason_code,
                                    'notes' => $it->notes,
                                ],
                            )
                            ->values(),
                    ]
                    : null;

                $historyPayload = $history
                    ->map(
                        fn($r) => [
                            'id' => $r->id,
                            'status' => $r->status,
                            'started_at' => optional($r->started_at)->toDateString(),
                            'closed_at' => optional($r->closed_at)->toDateString(),
                            'reference_no' => $r->reference_no,
                            'notes' => $r->notes,
                            'items' => $r->items
                                ->map(
                                    fn($it) => [
                                        'id' => $it->id,
                                        'authorization_no' => $it->authorization_no,
                                        'patient_name' => $it->patient_name,
                                        'affiliate_no' => $it->affiliate_no,
                                        'service_date' => optional($it->service_date)->toDateString(),
                                        'amount_billed' => (string) $it->amount_billed,
                                        'amount_paid' => (string) $it->amount_paid,
                                        'adjustment_amount' => (string) $it->adjustment_amount,
                                        'balance' => (string) $it->balance,
                                        'status' => $it->status,
                                        'paid_at' => optional($it->paid_at)->toDateString(),
                                        'claim_no' => $it->claim_no,
                                        'reason_code' => $it->reason_code,
                                        'notes' => $it->notes,
                                    ],
                                )
                                ->values(),
                        ],
                    )
                    ->values();
            @endphp

            <div class="mt-8 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6"
                x-data="reconciliationUI({
  open: @js($openPayload),
  history: @js($historyPayload),
  invoiceId: {{ $invoice->id }},
  autoModal: false
})"
>


                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Conciliaciones</div>
                        <div class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            <template x-if="activeRec">
                                <span>
                                    Conciliación #<span x-text="activeRec.id"></span>
                                    <span class="ml-2 inline-flex rounded-full px-2 py-1 text-xs font-semibold"
                                        :class="badgeRec(activeRec.status)">
                                        <span x-text="activeRec.status"></span>
                                    </span>
                                </span>
                            </template>
                            <template x-if="!activeRec">
                                <span>No hay conciliación</span>
                            </template>
                        </div>

                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-show="activeRec">
                            Inicio: <span x-text="activeRec?.started_at || '-'"></span>
                            <template x-if="activeRec?.closed_at">
                                <span> | Cierre: <span x-text="activeRec.closed_at"></span></span>
                            </template>
                            <template x-if="activeRec?.reference_no">
                                <span> | Ref: <span x-text="activeRec.reference_no"></span></span>
                            </template>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">

                        <!-- Selector historial -->
                        <div class="min-w-[260px]" x-show="historyList.length">
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Historial</label>
                            <select
                                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                @change="pickHistory($event.target.value)">
                                <option value="">— Ver conciliación cerrada —</option>
                                <template x-for="r in historyList" :key="r.id">
                                    <option :value="r.id"
                                        x-text="`#${r.id} • ${r.status} • ${r.closed_at || r.started_at || ''}`">
                                    </option>
                                </template>
                            </select>
                        </div>

                        <!-- Acciones -->
                        @hasanyrole('admin|analista')
                            @hasanyrole('admin|analista')
<template x-if="!openRec">
  <div class="flex items-center gap-2">
    <form method="POST" action="{{ route('invoices.reconciliations.store', $invoice) }}">
      @csrf
      <button class="rounded-lg bg-indigo-600 px-4 py-2 text-white font-medium hover:bg-indigo-700">
        Iniciar conciliación
      </button>
    </form>

    <button type="button"
      class="rounded-lg bg-indigo-600/90 px-4 py-2 text-white font-medium hover:bg-indigo-700"
      @click="autoModal = true">
      Conciliación automática (PDF)
    </button>
  </div>
</template>

<template x-if="activeRec && activeRec.status === 'review'">
  <form method="POST" :action="`/reconciliations/${activeRec.id}/confirm`"
        onsubmit="return confirm('¿Confirmar esta conciliación prellenada?');">
    @csrf
    @method('PATCH')
    <button class="rounded-lg bg-green-600 px-4 py-2 text-white font-medium hover:bg-green-700">
      Confirmar conciliación
    </button>
  </form>
</template>
@endhasanyrole

                            <template x-if="openRec">
                                <form method="POST" :action="`/reconciliations/${openRec.id}/close`"
                                    onsubmit="return confirm('¿Cerrar conciliación?');">
                                    @csrf
                                    @method('PATCH')
                                    <button class="rounded-lg bg-gray-700/40 px-4 py-2 text-gray-100 hover:bg-gray-700/60">
                                        Cerrar conciliación
                                    </button>
                                </form>
                            </template>

                            <template x-if="activeRec && !openRec && activeRec.status !== 'open'">
                                <button type="button"
                                    class="rounded-lg border border-gray-300 dark:border-gray-700 px-4 py-2 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700/30"
                                    @click="showOpen()">
                                    Volver a actual
                                </button>
                            </template>
                        @endhasanyrole
                    </div>
                </div>

                <!-- Resumen -->
                <template x-if="activeRec">
                    <div class="mt-5">
                        <div class="mb-3 grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                            <div class="rounded-lg bg-gray-50 dark:bg-gray-900/40 p-3">
                                <div class="text-xs text-gray-500 dark:text-gray-400">Total facturado</div>
                                <div class="font-semibold" x-text="formatMoney(totalBilled())"></div>
                            </div>
                            <div class="rounded-lg bg-gray-50 dark:bg-gray-900/40 p-3">
                                <div class="text-xs text-gray-500 dark:text-gray-400">Total pagado</div>
                                <div class="font-semibold" x-text="formatMoney(totalPaid())"></div>
                            </div>
                            <div class="rounded-lg bg-gray-50 dark:bg-gray-900/40 p-3">
                                <div class="text-xs text-gray-500 dark:text-gray-400">Balance</div>
                                <div class="font-semibold" x-text="formatMoney(totalBalance())"></div>
                            </div>
                        </div>

                        <!-- Tabla -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="text-gray-600 dark:text-gray-300">
                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                        <th class="py-2 text-left">Autorización</th>
                                        <th class="py-2 text-left">Paciente</th>
                                        <th class="py-2 text-right">Facturado</th>
                                        <th class="py-2 text-right">Pagado</th>
                                        <th class="py-2 text-right">Ajuste</th>
                                        <th class="py-2 text-right">Balance</th>
                                        <th class="py-2 text-left">Estado</th>
                                        @hasanyrole('admin|analista')
                                            <th class="py-2 text-right">Acción</th>
                                        @endhasanyrole
                                    </tr>
                                </thead>

                                <tbody class="text-gray-800 dark:text-gray-100">
                                    <template x-for="it in activeRec.items" :key="it.id">
                                        <tr class="border-b border-gray-200 dark:border-gray-700 align-top">
                                            <td class="py-2">
                                                <div class="font-semibold" x-text="it.authorization_no || '-'"></div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400"
                                                    x-text="it.claim_no ? ('Reclamo: ' + it.claim_no) : ''"></div>
                                            </td>

                                            <td class="py-2">
                                                <div x-text="it.patient_name || '-'"></div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400"
                                                    x-text="it.affiliate_no || ''"></div>
                                            </td>

                                            <td class="py-2 text-right" x-text="formatMoney(it.amount_billed)"></td>

                                            <td class="py-2 text-right">
                                                <template x-if="!isEditable(it)"><span
                                                        x-text="formatMoney(it.amount_paid)"></span></template>
                                                <template x-if="isEditable(it)">
                                                    <input type="number" step="0.01" min="0"
                                                        x-model.number="it.amount_paid"
                                                        class="w-28 rounded border-gray-600 bg-gray-900 text-gray-100 text-right">
                                                </template>
                                            </td>

                                            <td class="py-2 text-right">
                                                <template x-if="!isEditable(it)"><span
                                                        x-text="formatMoney(it.adjustment_amount)"></span></template>
                                                <template x-if="isEditable(it)">
                                                    <input type="number" step="0.01"
                                                        x-model.number="it.adjustment_amount"
                                                        class="w-28 rounded border-gray-600 bg-gray-900 text-gray-100 text-right">
                                                </template>
                                            </td>

                                            <td class="py-2 text-right">
                                                <span x-text="formatMoney(calcBalance(it))"></span>
                                            </td>

                                            <td class="py-2">
                                                <template x-if="!isEditable(it)">
                                                    <span
                                                        class="inline-flex rounded-full px-2 py-1 text-xs font-semibold"
                                                        :class="badge(it.status)">
                                                        <span x-text="it.status"></span>
                                                    </span>
                                                </template>

                                                <template x-if="isEditable(it)">
                                                    <select x-model="it.status"
                                                        class="rounded border-gray-600 bg-gray-900 text-gray-100">
                                                        <option value="pending">pending</option>
                                                        <option value="partial">partial</option>
                                                        <option value="paid">paid</option>
                                                        <option value="rejected">rejected</option>
                                                        <option value="adjusted">adjusted</option>
                                                    </select>
                                                </template>
                                            </td>
                                            @hasanyrole('admin|analista')
                                                <td class="py-2 text-right space-x-2">
                                                    <template x-if="!isEditable(it)">
                                                        <span class="text-xs text-gray-500 dark:text-gray-400">—</span>
                                                    </template>

                                                    <template x-if="isEditable(it) && !it._edit">
                                                        <button type="button"
                                                            class="rounded bg-yellow-500/20 px-3 py-1 text-yellow-200"
                                                            @click="startEdit(it)">
                                                            ✏️
                                                        </button>
                                                    </template>

                                                    <template x-if="isEditable(it) && it._edit">
                                                        <button type="button"
                                                            class="rounded bg-green-600/20 px-3 py-1 text-green-200"
                                                            @click="saveItem(it)">
                                                            Guardar
                                                        </button>
                                                    </template>

                                                    <template x-if="isEditable(it) && it._edit">
                                                        <button type="button"
                                                            class="rounded bg-gray-600/20 px-3 py-1 text-gray-200"
                                                            @click="cancelEdit(it)">
                                                            Cancelar
                                                        </button>
                                                    </template>
                                                </td>
                                            @endhasanyrole
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3 text-xs text-gray-500 dark:text-gray-400" x-show="msg" x-text="msg">
                        </div>
                    </div>
                </template>

                                <div x-show="autoModal" style="display:none;" class="fixed inset-0 z-50 bg-black/50 p-4">
                    <div class="mx-auto mt-16 max-w-xl rounded-xl bg-white dark:bg-gray-800 p-6"
                        @click.outside="autoModal=false">

                        <div class="flex items-start justify-between">
                            <div>
                                <div class="text-lg font-semibold text-gray-900 dark:text-gray-100">Subir PDF para
                                    conciliación automática</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    Se validará y se prellenarán los items. Luego confirmas.
                                </div>
                            </div>
                            <button class="text-gray-400 hover:text-gray-200" @click="autoModal=false">✕</button>
                        </div>

                        <form class="mt-4 space-y-3" method="POST"
                            action="{{ route('invoices.reconciliations.auto-upload', $invoice) }}"
                            enctype="multipart/form-data">
                            @csrf

                            <div>
                                <label class="block text-sm text-gray-300 mb-1">PDF</label>
                                <input type="file" name="pdf" accept="application/pdf" required
                                    class="w-full rounded-lg border-gray-600 bg-gray-900 text-gray-100">
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm text-gray-300 mb-1">Fecha inicio</label>
                                    <input type="date" name="started_at"
                                        class="w-full rounded-lg border-gray-600 bg-gray-900 text-gray-100">
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-300 mb-1">Referencia</label>
                                    <input type="text" name="reference_no"
                                        class="w-full rounded-lg border-gray-600 bg-gray-900 text-gray-100"
                                        placeholder="Opcional">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm text-gray-300 mb-1">Notas</label>
                                <input type="text" name="notes"
                                    class="w-full rounded-lg border-gray-600 bg-gray-900 text-gray-100"
                                    placeholder="Opcional">
                            </div>

                            <div class="flex justify-end gap-2 pt-2">
                                <button type="button"
                                    class="rounded-lg border border-gray-600 px-4 py-2 text-gray-200 hover:bg-gray-700/30"
                                    @click="autoModal=false">
                                    Cancelar
                                </button>
                                <button
                                    class="rounded-lg bg-indigo-600 px-4 py-2 text-white font-medium hover:bg-indigo-700">
                                    Subir y prellenar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <script>
  function reconciliationUI({
    open = null,
    history = [],
    invoiceId = null,
    autoModal = false,
  } = {}) {
    return {
      invoiceId,
      openRec: open,
      historyList: history || [],
      activeRec: open ?? (history?.[0] ?? null),
      msg: '',
      autoModal, // ✅ YA EXISTE EN EL SCOPE

      pickHistory(id) {
        if (!id) return;
        const found = this.historyList.find(x => String(x.id) === String(id));
        if (found) this.activeRec = JSON.parse(JSON.stringify(found));
      },

      showOpen() {
        this.activeRec = this.openRec ?? null;
      },

      isEditable(it) {
        return this.activeRec && this.activeRec.status === 'open';
      },

      startEdit(it) {
        it._backup = JSON.parse(JSON.stringify(it));
        it._edit = true;
      },
      cancelEdit(it) {
        Object.assign(it, it._backup);
        it._edit = false;
      },

      calcBalance(it) {
        const billed = parseFloat(it.amount_billed || 0) || 0;
        const paid = parseFloat(it.amount_paid || 0) || 0;
        const adj = parseFloat(it.adjustment_amount || 0) || 0;
        const bal = billed - paid - adj;
        return Math.max(0, Math.round((bal + Number.EPSILON) * 100) / 100);
      },

      badge(status) {
        if (status === 'paid') return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-200';
        if (status === 'partial') return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-200';
        if (status === 'rejected') return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-200';
        if (status === 'adjusted') return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-200';
        return 'bg-gray-100 text-gray-800 dark:bg-gray-700/40 dark:text-gray-100';
      },

      badgeRec(status) {
        if (status === 'open') return 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-200';
        if (status === 'review') return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-200';
        if (status === 'closed') return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-200';
        if (status === 'canceled') return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-200';
        return 'bg-gray-100 text-gray-800 dark:bg-gray-700/40 dark:text-gray-100';
      },

      totalBilled() {
        if (!this.activeRec) return 0;
        return this.activeRec.items.reduce((s, it) => s + (parseFloat(it.amount_billed || 0) || 0), 0);
      },
      totalPaid() {
        if (!this.activeRec) return 0;
        return this.activeRec.items.reduce((s, it) => s + (parseFloat(it.amount_paid || 0) || 0), 0);
      },
      totalBalance() {
        if (!this.activeRec) return 0;
        return this.activeRec.items.reduce((s, it) => s + this.calcBalance(it), 0);
      },

      async saveItem(it) {
        this.msg = '';
        try {
          const payload = {
            amount_paid: it.amount_paid ?? 0,
            adjustment_amount: it.adjustment_amount ?? 0,
            status: it.status ?? 'pending',
            paid_at: it.paid_at ?? null,
            claim_no: it.claim_no ?? null,
            reason_code: it.reason_code ?? null,
            notes: it.notes ?? null,
          };

          const res = await fetch(`/reconciliation-items/${it.id}`, {
            method: 'PATCH',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: JSON.stringify(payload),
          });

          const json = await res.json();
          if (!res.ok || !json.ok) {
            this.msg = json.message || 'No se pudo guardar.';
            return;
          }

          it.balance = json.item.balance;
          it._edit = false;
          this.msg = 'Guardado ✅';
        } catch (e) {
          console.error(e);
          this.msg = 'Error guardando.';
        }
      },

      formatMoney(n) {
        const v = (parseFloat(n || 0) || 0);
        return v.toLocaleString('es-DO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      },
    }
  }
</script>


        </div>
    </div>


    <script>
        function invoiceViewPage(payload) {
            return {
                invoiceId: payload.invoiceId,
                invoiceType: payload.invoiceType || 'ars',
                items: payload.items || [],
                insurerName: payload.insurerName || '',
                routes: payload.routes || {},
                savingId: null,
                flash: {
                    type: '',
                    message: ''
                },

                showAddForm: false,
                addingItem: false,
                newItem: { service_date: '', description: '', patient_name: '', affiliate_no: '', authorization_no: '', amount: '' },

                resetNewItem() {
                    this.newItem = { service_date: '', description: '', patient_name: '', affiliate_no: '', authorization_no: '', amount: '' };
                },

                csrf() {
                    const el = document.querySelector('meta[name="csrf-token"]');
                    return el ? el.getAttribute('content') : '';
                },

                startEdit(it) {
                    it._backup = JSON.parse(JSON.stringify(it));
                    it._edit = true;
                },

                cancelEdit(it) {
                    Object.assign(it, it._backup || {});
                    it._edit = false;
                    it._backup = null;
                },

                total() {
                    return this.items.reduce((sum, r) => sum + (parseFloat(r.amount || 0) || 0), 0);
                },

                formatMoney(n) {
                    const v = (parseFloat(n || 0) || 0);
                    return v.toLocaleString('es-DO', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                },

                // Para usarlo dentro de template string "$${...}" sin duplicar el signo
                formatMoneyRaw(n) {
                    const v = (parseFloat(n || 0) || 0);
                    return v.toLocaleString('es-DO', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                },

                async addItem() {
                    this.flash = { type: '', message: '' };

                    const isArs = this.invoiceType === 'ars';
                    const missingArs = isArs && (!this.newItem.patient_name || !this.newItem.service_date);
                    const missingClinica = !isArs && (!this.newItem.description || !this.newItem.service_date);

                    if (missingArs || missingClinica || !(parseFloat(this.newItem.amount) > 0)) {
                        this.flash = {
                            type: 'error',
                            message: isArs
                                ? 'Completa paciente, fecha y monto válido.'
                                : 'Completa descripción, fecha y monto válido.',
                        };
                        return;
                    }

                    this.addingItem = true;
                    try {
                        const res = await fetch(this.routes.storeItem, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.csrf(),
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify(this.newItem),
                        });

                        const json = await res.json();
                        if (!res.ok || !json.ok) {
                            this.flash = { type: 'error', message: json.message || 'No se pudo agregar la línea.' };
                            return;
                        }

                        this.items.push(json.item);
                        this.resetNewItem();
                        this.showAddForm = false;
                        this.flash = { type: 'success', message: 'Línea agregada correctamente.' };
                    } catch (e) {
                        this.flash = { type: 'error', message: 'Error de red agregando la línea.' };
                    } finally {
                        this.addingItem = false;
                    }
                },

                async deleteItem(it) {
                    if (!confirm('¿Eliminar esta línea? Esta acción no se puede deshacer.')) return;

                    this.flash = { type: '', message: '' };
                    try {
                        const res = await fetch(`${this.routes.updateItemBase}/${it.id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': this.csrf(),
                                'Accept': 'application/json',
                            },
                        });

                        const json = await res.json();
                        if (!res.ok || !json.ok) {
                            this.flash = { type: 'error', message: json.message || 'No se pudo eliminar la línea.' };
                            return;
                        }

                        this.items = this.items.filter(i => i.id !== it.id);
                        this.flash = { type: 'success', message: 'Línea eliminada correctamente.' };
                    } catch (e) {
                        this.flash = { type: 'error', message: 'Error de red eliminando la línea.' };
                    }
                },

                async saveItem(it) {
                    this.flash = {
                        type: '',
                        message: ''
                    };

                    // validación ligera cliente
                    const isArs = this.invoiceType === 'ars';
                    const invalid = !it.service_date || !(parseFloat(it.amount) > 0) ||
                        (isArs && !it.patient_name) || (!isArs && !it.description);
                    if (invalid) {
                        this.flash = {
                            type: 'error',
                            message: isArs
                                ? 'Completa paciente, fecha y monto válido.'
                                : 'Completa descripción, fecha y monto válido.',
                        };
                        return;
                    }

                    this.savingId = it.id;

                    try {
                        const url = `${this.routes.updateItemBase}/${it.id}`;

                        const res = await fetch(url, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.csrf(),
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                service_date: it.service_date,
                                description: it.description,
                                patient_name: it.patient_name,
                                affiliate_no: it.affiliate_no,
                                authorization_no: it.authorization_no,
                                amount: it.amount,
                            })
                        });

                        // Si el backend devuelve 419 o 500, esto te ayuda a ver
                        if (!res.ok) {
                            let msg = 'No se pudo guardar el item.';
                            try {
                                const err = await res.json();
                                msg = err.message || msg;
                            } catch (_) {}
                            this.flash = {
                                type: 'error',
                                message: msg
                            };
                            return;
                        }

                        const json = await res.json();
                        if (!json.ok) {
                            this.flash = {
                                type: 'error',
                                message: json.message || 'No se pudo guardar el item.'
                            };
                            return;
                        }

                        it._edit = false;
                        it._backup = null;

                        this.flash = {
                            type: 'success',
                            message: 'Item actualizado.'
                        };
                    } catch (e) {
                        this.flash = {
                            type: 'error',
                            message: 'Error de red guardando el item.'
                        };
                    } finally {
                        this.savingId = null;
                    }
                }
            }
        }
    </script>
</x-app-layout>
