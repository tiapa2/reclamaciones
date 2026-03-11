<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Analistas') }}
        </h2>
    </x-slot>

    <div class="py-8" x-data="{ modalOpen: false }">
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
                <div class="flex items-center justify-between mb-6">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $analysts->total() }} analista(s) registrado(s)
                    </p>
                    <button type="button" @click="modalOpen = true"
                        class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-white font-medium hover:bg-blue-700">
                        + Nuevo Analista
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-gray-500 dark:text-gray-400">
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="py-3 text-left font-semibold">Nombre</th>
                                <th class="py-3 text-left font-semibold">Email</th>
                                <th class="py-3 text-center font-semibold">Facturas</th>
                                <th class="py-3 text-center font-semibold">Pagos</th>
                                <th class="py-3 text-center font-semibold">Factorings</th>
                                <th class="py-3 text-right font-semibold">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-800 dark:text-gray-100">
                            @forelse ($analysts as $a)
                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                    <td class="py-3 font-medium">{{ $a->name }}</td>
                                    <td class="py-3 text-gray-600 dark:text-gray-300">{{ $a->email }}</td>
                                    <td class="py-3 text-center">
                                        <span class="inline-flex items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-200 text-xs font-semibold px-2.5 py-0.5 min-w-[2rem]">
                                            {{ $a->created_invoices_count }}
                                        </span>
                                    </td>
                                    <td class="py-3 text-center">
                                        <span class="inline-flex items-center justify-center rounded-full bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-200 text-xs font-semibold px-2.5 py-0.5 min-w-[2rem]">
                                            {{ $a->created_payments_count }}
                                        </span>
                                    </td>
                                    <td class="py-3 text-center">
                                        <span class="inline-flex items-center justify-center rounded-full bg-purple-100 dark:bg-purple-900/40 text-purple-800 dark:text-purple-200 text-xs font-semibold px-2.5 py-0.5 min-w-[2rem]">
                                            {{ $a->created_factorings_count }}
                                        </span>
                                    </td>
                                    <td class="py-3 text-right space-x-2">
                                        <a href="{{ route('analysts.show', $a) }}"
                                            class="inline-flex items-center rounded-lg bg-gray-600 px-3 py-1.5 text-white text-xs font-medium hover:bg-gray-700">
                                            Ver documentos
                                        </a>

                                        <button type="button"
                                            x-data
                                            @click="
                                                if (confirm('¿Resetear la contraseña de {{ addslashes($a->name) }}?')) {
                                                    $el.closest('tr').querySelector('form.reset-form').submit()
                                                }
                                            "
                                            class="inline-flex items-center rounded-lg bg-yellow-500 px-3 py-1.5 text-white text-xs font-medium hover:bg-yellow-600">
                                            Resetear clave
                                        </button>
                                        <form method="POST" action="{{ route('analysts.reset-password', $a) }}" class="reset-form hidden">
                                            @csrf
                                            @method('PATCH')
                                        </form>

                                        <form method="POST" action="{{ route('analysts.destroy', $a) }}" class="inline"
                                            onsubmit="return confirm('¿Eliminar al analista {{ addslashes($a->name) }}? Sus documentos creados quedarán en el sistema.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="inline-flex items-center rounded-lg bg-red-600 px-3 py-1.5 text-white text-xs font-medium hover:bg-red-700">
                                                Eliminar
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-10 text-center text-gray-500 dark:text-gray-400">
                                        No hay analistas registrados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $analysts->links() }}
                </div>
            </div>
        </div>

        <!-- MODAL CREAR ANALISTA -->
        <div x-show="modalOpen" x-transition.opacity
            class="fixed inset-0 z-50 bg-black/50 px-4 py-6 overflow-y-auto"
            style="display: none;">
            <div class="min-h-full flex items-center justify-center">
                <div x-transition @click.outside="modalOpen = false"
                    class="w-full max-w-md rounded-xl bg-white dark:bg-gray-800 shadow-lg">

                    <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 p-5">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Nuevo Analista</h3>
                        <button class="text-gray-500 hover:text-gray-800 dark:text-gray-300 dark:hover:text-white"
                            @click="modalOpen = false">✕</button>
                    </div>

                    <form method="POST" action="{{ route('analysts.store') }}" class="p-5 space-y-4">
                        @csrf

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Nombre
                            </label>
                            <input type="text" name="name" value="{{ old('name') }}" required
                                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Nombre completo" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Email
                            </label>
                            <input type="email" name="email" value="{{ old('email') }}" required
                                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="correo@ejemplo.com" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Contraseña
                            </label>
                            <input type="password" name="password" required
                                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Mínimo 8 caracteres" />
                        </div>

                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" @click="modalOpen = false"
                                class="rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                                Cancelar
                            </button>
                            <button type="submit"
                                class="rounded-lg bg-blue-600 px-4 py-2 text-sm text-white font-medium hover:bg-blue-700">
                                Crear Analista
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
