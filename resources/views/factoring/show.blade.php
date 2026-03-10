<x-app-layout>
  <x-slot name="header">
    <div class="flex items-center justify-between">
      <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100">Detalle de Factoring</h2>

      <div class="flex items-center gap-2">
        <a href="{{ route('factorings.index') }}"
           class="rounded-lg border border-gray-300 dark:border-gray-700 px-4 py-2 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700/30">
          Volver
        </a>

        <a href="{{ route('invoices.view', $factoring->invoice_id) }}"
           class="rounded-lg bg-gray-700/40 px-4 py-2 text-gray-100 hover:bg-gray-700/60">
          Ver factura
        </a>
      </div>
    </div>
  </x-slot>

  <div class="py-8">
    <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">

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

      <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

          <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <div class="text-xs text-gray-500 dark:text-gray-400">Factura</div>
            <div class="font-semibold text-gray-900 dark:text-gray-100">#{{ $factoring->invoice_id }}</div>
            <div class="text-sm text-gray-600 dark:text-gray-300">{{ $factoring->invoice?->ncf_number }}</div>
          </div>

          <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <div class="text-xs text-gray-500 dark:text-gray-400">Doctor</div>
            <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $factoring->doctor?->full_name }}</div>
            <div class="text-sm text-gray-600 dark:text-gray-300">ARS: {{ $factoring->insurer?->name }}</div>
          </div>

          <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <div class="text-xs text-gray-500 dark:text-gray-400">Estado</div>
            <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $factoring->status }}</div>
            <div class="text-sm text-gray-600 dark:text-gray-300">
              Compra: {{ optional($factoring->purchase_date)->toDateString() }} |
              Vence: {{ optional($factoring->due_date)->toDateString() }}
            </div>
          </div>

        </div>

        <div class="mt-6 overflow-x-auto">
          <table class="min-w-full text-sm">
            <tbody class="text-gray-800 dark:text-gray-100">
              <tr class="border-b border-gray-200 dark:border-gray-700">
                <td class="py-2 font-semibold">Total factura (snapshot)</td>
                <td class="py-2 text-right">{{ number_format((float)$factoring->invoice_total, 2) }}</td>
              </tr>
              <tr class="border-b border-gray-200 dark:border-gray-700">
                <td class="py-2">% ISR</td>
                <td class="py-2 text-right">{{ number_format((float)$factoring->discount_rate, 2) }}%</td>
              </tr>
              <tr class="border-b border-gray-200 dark:border-gray-700">
                <td class="py-2">Monto ISR</td>
                <td class="py-2 text-right">{{ number_format((float)$factoring->discount_amount, 2) }}</td>
              </tr>
              <tr class="border-b border-gray-200 dark:border-gray-700">
                <td class="py-2">% Descuento</td>
                <td class="py-2 text-right">{{ number_format((float)$factoring->commission_rate, 2) }}%</td>
              </tr>
              <tr class="border-b border-gray-200 dark:border-gray-700">
                <td class="py-2">Monto descuento</td>
                <td class="py-2 text-right">{{ number_format((float)$factoring->commission_amount, 2) }}</td>
              </tr>
              <tr class="border-b border-gray-200 dark:border-gray-700">
                <td class="py-2 font-semibold">Neto entregado al doctor</td>
                <td class="py-2 text-right font-semibold">{{ number_format((float)$factoring->net_to_doctor, 2) }}</td>
              </tr>
              <tr>
                <td class="py-2 font-semibold">Ganancia esperada</td>
                <td class="py-2 text-right font-semibold">{{ number_format((float)$factoring->expected_profit, 2) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="mt-6 flex items-center justify-end gap-2">
          @if($factoring->status === 'active')
            <form action="{{ route('factorings.collect', $factoring) }}" method="POST"
                  onsubmit="return confirm('¿Marcar como cobrado?');">
              @csrf
              @method('PATCH')
              <button class="rounded-lg bg-green-600 px-4 py-2 text-white hover:bg-green-700">
                Marcar como cobrado
              </button>
            </form>

            <form action="{{ route('factorings.cancel', $factoring) }}" method="POST"
                  onsubmit="return confirm('¿Cancelar factoring?');">
              @csrf
              @method('PATCH')
              <button class="rounded-lg bg-red-600 px-4 py-2 text-white hover:bg-red-700">
                Eliminar
              </button>
            </form>
          @endif
        </div>

      </div>
    </div>
  </div>
</x-app-layout>
