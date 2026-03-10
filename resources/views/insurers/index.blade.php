<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Registro de Aseguradoras') }}
        </h2>
    </x-slot>

    <div class="py-8" x-data="insurersPage()">
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

            <!-- CARD -->
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">

                <!-- HEADER -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <form method="GET" class="flex-1">
                        <input
                            name="q"
                            value="{{ $q }}"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-700
                                   dark:bg-gray-900 dark:text-gray-100
                                   focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Buscar aseguradoras..."
                        />
                    </form>

                    <button
                        type="button"
                        @click="openCreate()"
                        class="inline-flex items-center justify-center rounded-lg
                               bg-blue-600 px-4 py-2 text-white font-medium
                               hover:bg-blue-700"
                    >
                        + Nueva Aseguradora
                    </button>
                </div>

                <!-- TABLE -->
                <div class="mt-6 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-gray-600 dark:text-gray-300">
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="py-3 text-left font-semibold">Nombre</th>
                                <th class="py-3 text-left font-semibold">RNC</th>
                                <th class="py-3 text-left font-semibold">Email</th>
                                <th class="py-3 text-left font-semibold">Teléfono</th>
                                <th class="py-3 text-right font-semibold">Acciones</th>
                            </tr>
                        </thead>

                        <tbody class="text-gray-800 dark:text-gray-100">
                            @forelse ($insurers as $item)
                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                    <td class="py-3 font-medium">{{ $item->name }}</td>
                                    <td class="py-3">{{ $item->rnc }}</td>
                                    <td class="py-3">{{ $item->email }}</td>
                                    <td class="py-3">{{ $item->phone }}</td>
                                    <td class="py-3 text-right space-x-2">

                                        <!-- EDIT -->
                                        <button
                                            type="button"
                                            class="rounded-lg bg-gray-600 px-4 py-2 text-white font-medium hover:bg-gray-700"
                                            @click="openEdit({
                                                id: {{ $item->id }},
                                                name: @js($item->name),
                                                rnc: @js($item->rnc),
                                                email: @js($item->email),
                                                phone: @js($item->phone),
                                            })"
                                        >
                                            Editar
                                        </button>

                                        <!-- DELETE -->
                                        <form method="POST"
                                              action="{{ route('insurers.destroy', $item) }}"
                                              class="inline"
                                              onsubmit="return confirm('¿Eliminar esta aseguradora?')">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="rounded-lg bg-red-600 px-4 py-2 text-white font-medium hover:bg-red-700"
                                            >
                                                Eliminar
                                            </button>
                                        </form>

                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5"
                                        class="py-8 text-center text-gray-500 dark:text-gray-400">
                                        No hay aseguradoras registradas.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $insurers->links() }}
                </div>
            </div>
        </div>

        <!-- MODAL -->
        <div
            x-show="modalOpen"
            x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center
                   bg-black/50 px-4"
            style="display: none;"
        >
            <div
                x-transition
                @click.outside="closeModal()"
                class="w-full max-w-2xl rounded-xl
                       bg-white dark:bg-gray-800 shadow-lg"
            >
                <div class="flex items-center justify-between
                            border-b border-gray-200 dark:border-gray-700 p-5">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100"
                        x-text="isEdit ? 'Editar Aseguradora' : 'Nueva Aseguradora'">
                    </h3>

                    <button class="text-gray-500 hover:text-gray-800
                                   dark:text-gray-300 dark:hover:text-white"
                            @click="closeModal()">✕</button>
                </div>

                <form :action="formAction" method="POST" class="p-5">
                    @csrf
                    <template x-if="isEdit">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Nombre</label>
                            <input name="name" x-model="form.name"
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-700
                                          dark:bg-gray-900 dark:text-gray-100
                                          focus:border-indigo-500 focus:ring-indigo-500"
                                   placeholder="Nombre de la aseguradora" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">RNC</label>
                            <input name="rnc" x-model="form.rnc"
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-700
                                          dark:bg-gray-900 dark:text-gray-100
                                          focus:border-indigo-500 focus:ring-indigo-500"
                                   placeholder="Ingrese el RNC" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Teléfono</label>
                            <input name="phone" x-model="form.phone"
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-700
                                          dark:bg-gray-900 dark:text-gray-100
                                          focus:border-indigo-500 focus:ring-indigo-500"
                                   placeholder="Digita tu Teléfono">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Email</label>
                            <input name="email" type="email" x-model="form.email"
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-700
                                          dark:bg-gray-900 dark:text-gray-100
                                          focus:border-indigo-500 focus:ring-indigo-500"
                                   placeholder="Digita tu Email">
                        </div>

                    </div>

                    <div class="mt-6 flex items-center justify-end gap-3
                                border-t border-gray-200 dark:border-gray-700 pt-4">
                        <button type="button"
                                class="rounded-lg border border-gray-300 dark:border-gray-700
                                       px-4 py-2 text-gray-700 dark:text-gray-200
                                       hover:bg-gray-50 dark:hover:bg-gray-700/30"
                                @click="closeModal()">
                            Cancelar
                        </button>

                        <button type="submit"
                                class="rounded-lg bg-blue-600 px-4 py-2
                                       text-white font-medium hover:bg-blue-700">
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function insurersPage() {
                return {
                    modalOpen: false,
                    isEdit: false,
                    formAction: "{{ route('insurers.store') }}",
                    form: { id: null, name: '', rnc: '', email: '', phone: '' },

                    openCreate() {
                        this.isEdit = false;
                        this.formAction = "{{ route('insurers.store') }}";
                        this.form = { id: null, name: '', rnc: '', email: '', phone: '' };
                        this.modalOpen = true;
                    },

                    openEdit(item) {
                        this.isEdit = true;
                        this.formAction = `/insurers/${item.id}`;
                        this.form = { ...item };
                        this.modalOpen = true;
                    },

                    closeModal() {
                        this.modalOpen = false;
                    }
                }
            }
        </script>
    </div>
</x-app-layout>
