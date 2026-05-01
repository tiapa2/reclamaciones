<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Registro de Médicos e Instituciones') }}
        </h2>
    </x-slot>

    <div class="py-8" x-data="doctorsPage(@js($insurers), @js($ncfTypes))">
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
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <form method="GET" class="flex-1">
                        <input name="q" value="{{ $q }}"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100
                                   focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Buscar médicos o instituciones..." />
                    </form>

                    <button type="button" @click="openCreate()"
                        class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-white font-medium hover:bg-blue-700">
                        + Nuevo Médico
                    </button>
                </div>

                <div class="mt-6 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-gray-600 dark:text-gray-300">
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="py-3 text-left font-semibold">Nombre</th>
                                <th class="py-3 text-left font-semibold">Institución</th>
                                <th class="py-3 text-left font-semibold">Teléfono</th>
                                <th class="py-3 text-left font-semibold">Email</th>
                                <th class="py-3 text-right font-semibold">Acciones</th>
                            </tr>
                        </thead>

                        <tbody class="text-gray-800 dark:text-gray-100">
                            @forelse ($doctors as $d)
                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                    <td class="py-3 font-medium">
                                        <div class="flex flex-col">
                                            <span>{{ $d->full_name }}</span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                RNC/Cédula: {{ $d->rnc }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="py-3">{{ $d->institution }}</td>
                                    <td class="py-3">{{ $d->phone }}</td>
                                    <td class="py-3">{{ $d->email }}</td>
                                    <td class="py-3 text-right space-x-2">
                                        <button type="button"
                                            class="rounded-lg bg-gray-600 px-4 py-2 text-white font-medium hover:bg-gray-700"
                                            @click="openEdit({
                                                id: {{ $d->id }},
                                                rnc: @js($d->rnc),
                                                full_name: @js($d->full_name),
                                                specialty: @js($d->specialty),
                                                institution: @js($d->institution),
                                                phone: @js($d->phone),
                                                email: @js($d->email),
                                                e_invoicing_enabled: @js((bool) $d->e_invoicing_enabled),
                                                kontab_api_key_id: @js($d->kontab_api_key_id),
                                                kontab_has_secret: @js(filled($d->kontab_api_secret_encrypted)),
                                                insurers_pivot: @js($d->insurers->filter(fn($i) => filled($i->pivot->doctor_code))->mapWithKeys(fn($i) => [(string) $i->id => $i->pivot->doctor_code])),
                                                ncf: @js(
                                                    $d->ncfAuthorizations
                                                        ->keyBy('ncf_type_id')
                                                        ->map(fn($a) => [
                                                            'active' => (bool) $a->active,
                                                            'from_number' => $a->from_number,
                                                            'to_number' => $a->to_number,
                                                            'requested_at' => optional($a->requested_at)->format('Y-m-d'),
                                                            'expires_at' => optional($a->expires_at)->format('Y-m-d'),
                                                        ])
                                                ),
                                            })">
                                            Editar
                                        </button>

                                        <form method="POST" action="{{ route('doctors.destroy', $d) }}" class="inline"
                                            onsubmit="return confirm('¿Eliminar este médico?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="rounded-lg bg-red-600 px-4 py-2 text-white font-medium hover:bg-red-700">
                                                Eliminar
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-10 text-center text-gray-500 dark:text-gray-400">
                                        No hay médicos registrados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $doctors->links() }}
                </div>
            </div>
        </div>

        <!-- MODAL -->
        <div x-show="modalOpen" x-transition.opacity class="fixed inset-0 z-50 bg-black/50 px-4 py-6 overflow-y-auto"
            style="display: none;">
            <div class="min-h-full flex items-center justify-center">

                <div x-transition @click.outside="closeModal()"
                    class="w-full max-w-3xl rounded-xl bg-white dark:bg-gray-800 shadow-lg max-h-[calc(100vh-3rem)] overflow-hidden">

                    <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 p-5">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100"
                            x-text="isEdit ? 'Editar registro de médicos' : 'Nuevo registro de médicos'"></h3>

                        <button class="text-gray-500 hover:text-gray-800 dark:text-gray-300 dark:hover:text-white"
                            @click="closeModal()">✕</button>
                    </div>

                    <!-- FORM PRINCIPAL (crear/editar doctor) -->
                    <form :action="formAction" method="POST" class="p-5 overflow-y-auto"
                        style="max-height: calc(100vh - 14rem);">
                        @csrf
                        <template x-if="isEdit">
                            <input type="hidden" name="_method" value="PUT">
                        </template>

                        <!-- BLOQUE USUARIO (SOLO EDIT) -->
                        <template x-if="isEdit">
                            <div class="mt-6 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="font-semibold text-gray-900 dark:text-gray-100">Usuario del doctor</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Rol: doctor</div>
                                    </div>
                                </div>

                                <div class="mt-3 text-sm text-red-600 dark:text-red-300" x-show="!form.email">
                                    Este doctor no tiene email. Agrega un email para poder crear el usuario.
                                </div>

                                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm text-gray-700 dark:text-gray-200 mb-1">Email (usuario)</label>
                                        <input type="email" :value="form.email" disabled
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 opacity-80">
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Se usará el email del doctor.</p>
                                    </div>

                                    <div>
                                        <label class="block text-sm text-gray-700 dark:text-gray-200 mb-1">Contraseña</label>
                                        <!-- Este input se envía al form createDoctorUserForm (externo) -->
                                        <input form="createDoctorUserForm" name="password" type="password" required minlength="8"
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                            placeholder="Mínimo 8 caracteres">
                                    </div>

                                    <div class="md:col-span-2 flex justify-end gap-2">
                                        <button type="submit"
                                            form="createDoctorUserForm"
                                            class="rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700 disabled:opacity-50"
                                            :disabled="!form.email || !form.id">
                                            Crear usuario
                                        </button>

                                        <button type="submit"
                                            form="resetDoctorPasswordForm"
                                            class="rounded-lg bg-gray-700/40 px-4 py-2 text-gray-100 hover:bg-gray-700/60 disabled:opacity-50"
                                            :disabled="!form.id"
                                            onclick="return confirm('¿Resetear contraseña del doctor?');">
                                            Resetear contraseña
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- DATOS -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">RNC / Cédula</label>
                                <input name="rnc" x-model="form.rnc"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100
                                          focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Ingrese el RNC" required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Nombre</label>
                                <input name="full_name" x-model="form.full_name"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100
                                          focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Nombre completo" required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Especialidad</label>
                                <input name="specialty" x-model="form.specialty"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100
                                          focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Especialidad médica">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Institución</label>
                                <input name="institution" x-model="form.institution"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100
                                          focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Nombre de la Institución">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Teléfono</label>
                                <input name="phone" x-model="form.phone"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100
                                          focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Teléfono de contacto">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Email</label>
                                <input name="email" type="email" x-model="form.email"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100
                                          focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Correo electrónico">
                            </div>
                        </div>

                        <!-- TABS -->
                        <div class="mt-6">
                            <div class="flex rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
                                <button type="button" class="flex-1 px-4 py-2 text-sm font-medium"
                                    :class="tab === 'insurers'
                                        ? 'bg-gray-700 text-white'
                                        : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700/30'"
                                    @click="tab='insurers'">
                                    Aseguradoras
                                </button>

                                <button type="button" class="flex-1 px-4 py-2 text-sm font-medium"
                                    :class="tab === 'ncf'
                                        ? 'bg-gray-700 text-white'
                                        : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700/30'"
                                    @click="tab='ncf'">
                                    Comprobantes Fiscales
                                </button>

                                <button type="button" class="flex-1 px-4 py-2 text-sm font-medium"
                                    :class="tab === 'einvoice'
                                        ? 'bg-gray-700 text-white'
                                        : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700/30'"
                                    @click="tab='einvoice'">
                                    Facturación e-CF
                                </button>
                            </div>
                        </div>

                        <!-- TAB ASEGURADORAS -->
                        <div class="mt-4" x-show="tab==='insurers'">
                            <div class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                Aseguradoras Aceptadas
                            </div>

                            <div class="space-y-3 max-h-64 overflow-auto pr-1">
                                <template x-for="ins in insurers" :key="ins.id">
                                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4"
                                        x-data="{ enabled: false }" x-effect="enabled = isSelected(ins.id)">

                                        <div class="flex items-start gap-3">
                                            <input type="checkbox"
                                                class="mt-1 rounded border-gray-300 dark:border-gray-700"
                                                x-model="enabled" @change="toggleInsurer(ins.id, enabled)">

                                            <div class="flex-1">
                                                <div class="flex items-center justify-between gap-2">
                                                    <div class="font-semibold text-gray-900 dark:text-gray-100" x-text="ins.name"></div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">Factura / NCF</div>
                                                </div>

                                                <div class="mt-3" x-show="enabled" x-transition>
                                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-200 mb-1">
                                                        Código del Médico
                                                    </label>

                                                    <input :name="`insurers[${ins.id}]`"
                                                        x-model="form.insurers[ins.id]"
                                                        class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100
                                                               focus:border-indigo-500 focus:ring-indigo-500"
                                                        placeholder="Ingrese el código del médico">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- TAB NCF -->
                        <div class="mt-4" x-show="tab==='ncf'">
                            <div class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                Comprobantes Fiscales (NCF del médico)
                            </div>

                            <div class="space-y-3">
                                <template x-for="t in ncfTypes" :key="'type-' + t.id">
                                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                                        <div class="flex items-center justify-between gap-3">
                                            <div>
                                                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100" x-text="t.name"></div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400" x-text="'Prefijo: ' + t.prefix"></div>
                                            </div>

                                            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                                                <input type="hidden" :name="`ncf[${t.id}][active]`" value="0">
                                                <input type="checkbox" :name="`ncf[${t.id}][active]`" value="1"
                                                    x-model="form.ncf[t.id].active"
                                                    class="rounded border-gray-300 dark:border-gray-700">
                                                Activo
                                            </label>
                                        </div>

                                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4"
                                            x-show="form.ncf[t.id].active" x-transition>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-200 mb-1">Número Desde</label>
                                                <input class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100
                                                              focus:border-indigo-500 focus:ring-indigo-500"
                                                    :name="`ncf[${t.id}][from_number]`"
                                                    x-model="form.ncf[t.id].from_number"
                                                    placeholder="Ej: B0100000124">
                                            </div>

                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-200 mb-1">Número Hasta</label>
                                                <input class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100
                                                              focus:border-indigo-500 focus:ring-indigo-500"
                                                    :name="`ncf[${t.id}][to_number]`"
                                                    x-model="form.ncf[t.id].to_number"
                                                    placeholder="Ej: B0100000147">
                                            </div>

                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-200 mb-1">Fecha Solicitud</label>
                                                <input type="date"
                                                    class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100
                                                           focus:border-indigo-500 focus:ring-indigo-500"
                                                    :name="`ncf[${t.id}][requested_at]`"
                                                    x-model="form.ncf[t.id].requested_at">
                                            </div>

                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-200 mb-1">Fecha Vencimiento</label>
                                                <input type="date"
                                                    class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100
                                                           focus:border-indigo-500 focus:ring-indigo-500"
                                                    :name="`ncf[${t.id}][expires_at]`"
                                                    x-model="form.ncf[t.id].expires_at">
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- TAB FACTURACIÓN ELECTRÓNICA (kontab-erp) -->
                        <div class="mt-4" x-show="tab==='einvoice'">
                            <div class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-1">
                                Facturación electrónica (kontab-erp)
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                                Si está activo, las facturas a ARS de este médico se enviarán a kontab-erp para emitir e-CF y enviar a DGII automáticamente. Si está inactivo, se asignan los NCF locales como hasta ahora.
                            </p>

                            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-4">
                                <label class="flex items-center gap-3">
                                    <input type="hidden" name="e_invoicing_enabled" value="0">
                                    <input type="checkbox" name="e_invoicing_enabled" value="1"
                                        x-model="form.e_invoicing_enabled"
                                        class="rounded border-gray-300 dark:border-gray-700">
                                    <span class="text-sm font-medium text-gray-800 dark:text-gray-100">Activar facturación electrónica para este médico</span>
                                </label>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4" :class="!form.e_invoicing_enabled ? 'opacity-60' : ''">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-200 mb-1">Kontab API Key ID</label>
                                        <input type="text" name="kontab_api_key_id" x-model="form.kontab_api_key_id"
                                            placeholder="kt_live_..."
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 font-mono text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-200 mb-1">Kontab API Secret</label>
                                        <input type="password" name="kontab_api_secret" autocomplete="new-password"
                                            x-model="form.kontab_api_secret"
                                            :placeholder="form.kontab_has_secret ? '•••••••• (vacío = mantener actual)' : 'sk_...'"
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 font-mono text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <p class="text-xs text-gray-500 mt-1">El secret sólo se muestra una vez al crearlo en kontab.com.do.</p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3 pt-2 border-t border-gray-100 dark:border-gray-700">
                                    <button type="button" @click="testKontabConnection()"
                                        :disabled="!form.id || !form.kontab_api_key_id || !form.kontab_api_secret"
                                        class="rounded-lg border border-indigo-300 px-3 py-2 text-sm text-indigo-700 hover:bg-indigo-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                        Probar conexión
                                    </button>
                                    <span class="text-xs" :class="kontabTestClass" x-text="kontabTestMsg"></span>
                                </div>
                                <p class="text-xs text-gray-500" x-show="!form.id">
                                    Guarda el médico primero para poder probar la conexión.
                                </p>
                            </div>

                            {{-- Datos del webhook para que el admin de kontab-erp lo registre --}}
                            <div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 dark:bg-amber-900/20 p-4">
                                <button type="button" @click="showWebhookHelp = !showWebhookHelp"
                                    class="w-full flex items-center justify-between text-left">
                                    <div>
                                        <div class="text-sm font-semibold text-amber-900 dark:text-amber-200">
                                            ⓘ Datos para configurar en kontab.com.do
                                        </div>
                                        <div class="text-xs text-amber-700 dark:text-amber-300/80">
                                            Pasa esto al admin de kontab para que registre el webhook (necesario para que la factura quede confirmada por DGII).
                                        </div>
                                    </div>
                                    <i class="text-amber-700 dark:text-amber-300" x-text="showWebhookHelp ? '▾' : '▸'"></i>
                                </button>

                                <div x-show="showWebhookHelp" x-transition class="mt-4 space-y-3 text-sm">
                                    <div>
                                        <label class="block text-xs font-medium text-amber-900 dark:text-amber-200 mb-1">URL del webhook</label>
                                        <div class="flex items-center gap-2">
                                            <input type="text" readonly value="{{ $kontabIntegration['webhook_url'] }}"
                                                class="flex-1 rounded-md border-amber-300 bg-white dark:bg-gray-900 dark:text-gray-100 text-xs font-mono px-2 py-1.5">
                                            <button type="button" @click="copyToClipboard('{{ $kontabIntegration['webhook_url'] }}', 'url')"
                                                class="rounded-md bg-amber-600 hover:bg-amber-700 text-white text-xs px-3 py-1.5">
                                                <span x-text="copiedField === 'url' ? '✓ Copiado' : 'Copiar'"></span>
                                            </button>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-amber-900 dark:text-amber-200 mb-1">Secret</label>
                                        <div class="flex items-center gap-2">
                                            <input :type="showSecret ? 'text' : 'password'" readonly
                                                value="{{ $kontabIntegration['webhook_secret'] ?: '(no configurado en server)' }}"
                                                class="flex-1 rounded-md border-amber-300 bg-white dark:bg-gray-900 dark:text-gray-100 text-xs font-mono px-2 py-1.5">
                                            <button type="button" @click="showSecret = !showSecret"
                                                class="rounded-md bg-white border border-amber-300 text-amber-800 text-xs px-3 py-1.5">
                                                <span x-text="showSecret ? 'Ocultar' : 'Ver'"></span>
                                            </button>
                                            <button type="button" @click="copyToClipboard('{{ $kontabIntegration['webhook_secret'] }}', 'secret')"
                                                :disabled="!'{{ $kontabIntegration['webhook_secret'] }}'.length"
                                                class="rounded-md bg-amber-600 hover:bg-amber-700 text-white text-xs px-3 py-1.5 disabled:opacity-50">
                                                <span x-text="copiedField === 'secret' ? '✓ Copiado' : 'Copiar'"></span>
                                            </button>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-amber-900 dark:text-amber-200 mb-1">Eventos</label>
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach($kontabIntegration['events'] as $ev)
                                                <code class="rounded bg-white dark:bg-gray-900 dark:text-gray-100 text-xs font-mono px-2 py-1 border border-amber-200">{{ $ev }}</code>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div class="text-xs text-amber-800 dark:text-amber-300/90 bg-white/50 dark:bg-amber-900/30 rounded p-3 leading-relaxed">
                                        <p class="font-semibold mb-1">📋 Pasos para el admin de kontab:</p>
                                        <ol class="list-decimal list-inside space-y-0.5">
                                            <li>Entrar a kontab.com.do → Configuración → API & Integraciones → Webhooks.</li>
                                            <li>Click "Nuevo webhook", pegar la <strong>URL</strong> y el <strong>Secret</strong> de arriba.</li>
                                            <li>Marcar los 2 eventos listados.</li>
                                            <li>Activar y guardar.</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- FOOTER -->
                        <div class="mt-6 flex items-center justify-end gap-3 border-t border-gray-200 dark:border-gray-700 pt-4">
                            <button type="button"
                                class="rounded-lg border border-gray-300 dark:border-gray-700 px-4 py-2 text-gray-700 dark:text-gray-200
                                       hover:bg-gray-50 dark:hover:bg-gray-700/30"
                                @click="closeModal()">
                                Cancelar
                            </button>

                            <button type="submit"
                                class="rounded-lg bg-blue-600 px-4 py-2 text-white font-medium hover:bg-blue-700">
                                Guardar
                            </button>
                        </div>
                    </form>

                    <!-- ✅ FORMS EXTERNOS (NO anidados) -->
                    <form id="createDoctorUserForm" method="POST" :action="doctorUserStoreAction" class="hidden">
                        @csrf
                    </form>

                    <form id="resetDoctorPasswordForm" method="POST" :action="doctorUserResetAction" class="hidden">
                        @csrf
                        @method('PATCH')
                    </form>

                </div>
            </div>
        </div>

        <!-- ✅ URLS BASE PARA ARMAR ACTION DINÁMICO -->
        <script>
            window.doctorUserStoreUrlBase = @json(route('doctors.user.store', ['doctor' => '__ID__']));
            window.doctorUserResetUrlBase = @json(route('doctors.user.reset-password', ['doctor' => '__ID__']));
        </script>

        <script>
            function doctorsPage(insurers, ncfTypes) {
                return {
                    insurers: insurers || [],
                    ncfTypes: ncfTypes || [],

                    modalOpen: false,
                    isEdit: false,
                    tab: 'insurers',

                    formAction: "{{ route('doctors.store') }}",
                    form: {
                        id: null,
                        rnc: '',
                        full_name: '',
                        specialty: '',
                        institution: '',
                        phone: '',
                        email: '',
                        e_invoicing_enabled: false,
                        kontab_api_key_id: '',
                        kontab_api_secret: '',
                        kontab_has_secret: false,
                        insurers: {},
                        ncf: {}
                    },

                    kontabTestMsg: '',
                    kontabTestClass: 'text-gray-500',
                    showWebhookHelp: false,
                    showSecret: false,
                    copiedField: null,

                    copyToClipboard(text, field) {
                        if (!text) return;
                        navigator.clipboard.writeText(text).then(() => {
                            this.copiedField = field;
                            setTimeout(() => { this.copiedField = null; }, 1500);
                        });
                    },

                    async testKontabConnection() {
                        if (!this.form.id) return;
                        this.kontabTestMsg = 'Probando…';
                        this.kontabTestClass = 'text-gray-500';
                        try {
                            const res = await fetch(`/doctors/${this.form.id}/test-kontab`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify({
                                    kontab_api_key_id: this.form.kontab_api_key_id,
                                    kontab_api_secret: this.form.kontab_api_secret,
                                }),
                            });
                            const data = await res.json();
                            this.kontabTestMsg = (data.ok ? '✓ ' : '✗ ') + data.message;
                            this.kontabTestClass = data.ok ? 'text-green-700' : 'text-red-700';
                        } catch (e) {
                            this.kontabTestMsg = '✗ Error: ' + e.message;
                            this.kontabTestClass = 'text-red-700';
                        }
                    },

                    // ✅ actions para forms externos
                    get doctorUserStoreAction() {
                        return this.form.id
                            ? window.doctorUserStoreUrlBase.replace('__ID__', this.form.id)
                            : '';
                    },

                    get doctorUserResetAction() {
                        return this.form.id
                            ? window.doctorUserResetUrlBase.replace('__ID__', this.form.id)
                            : '';
                    },

                    initNcfStructure() {
                        this.form.ncf = this.form.ncf || {};

                        this.ncfTypes.forEach(t => {
                            const k = String(t.id);
                            if (!this.form.ncf[k]) {
                                // ✅ NOMBRES que coinciden con tu UI
                                this.form.ncf[k] = {
                                    active: false,
                                    from_number: '',
                                    to_number: '',
                                    requested_at: '',
                                    expires_at: ''
                                };
                            }
                        });
                    },

                    openCreate() {
                        this.isEdit = false;
                        this.tab = 'insurers';
                        this.formAction = "{{ route('doctors.store') }}";
                        this.form = {
                            id: null,
                            rnc: '',
                            full_name: '',
                            specialty: '',
                            institution: '',
                            phone: '',
                            email: '',
                            e_invoicing_enabled: false,
                            kontab_api_key_id: '',
                            kontab_api_secret: '',
                            kontab_has_secret: false,
                            insurers: {},
                            ncf: {}
                        };
                        this.kontabTestMsg = '';
                        this.initNcfStructure();
                        this.modalOpen = true;
                    },

                    openEdit(item) {
                        this.isEdit = true;
                        this.tab = 'insurers';
                        this.formAction = `/doctors/${item.id}`;

                        this.form = {
                            id: item.id,
                            rnc: item.rnc ?? '',
                            full_name: item.full_name ?? '',
                            specialty: item.specialty ?? '',
                            institution: item.institution ?? '',
                            phone: item.phone ?? '',
                            email: item.email ?? '',
                            e_invoicing_enabled: item.e_invoicing_enabled ?? false,
                            kontab_api_key_id: item.kontab_api_key_id ?? '',
                            kontab_api_secret: '',
                            kontab_has_secret: item.kontab_has_secret ?? false,
                            insurers: item.insurers_pivot ?? {},
                            ncf: item.ncf ?? {}
                        };
                        this.kontabTestMsg = '';
                        this.initNcfStructure();
                        this.modalOpen = true;
                    },

                    closeModal() {
                        this.modalOpen = false;
                    },

                    isSelected(insurerId) {
                        const key = String(insurerId);
                        return Object.prototype.hasOwnProperty.call(this.form.insurers, key);
                    },

                    toggleInsurer(insurerId, checked) {
                        const key = String(insurerId);

                        if (checked) {
                            if (!this.isSelected(key)) this.form.insurers[key] = '';
                        } else {
                            if (this.isSelected(key)) delete this.form.insurers[key];
                        }
                    }
                }
            }
        </script>

    </div>
</x-app-layout>
