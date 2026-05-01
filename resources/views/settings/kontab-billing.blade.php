@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto py-8 px-4">
  <div class="bg-white shadow rounded-lg p-6">
    <h1 class="text-2xl font-bold text-slate-900 mb-1">Facturación electrónica (Kontab)</h1>
    <p class="text-sm text-slate-500 mb-6">
      Conecta tu doctor a tu empresa en <a href="https://kontab.com.do" class="text-blue-600 hover:underline">kontab.com.do</a>
      para que las facturas a ARS se generen, posteen y envíen a la DGII automáticamente.
      Las facturas anteriores no se modifican y, si desactivas, vuelves al flujo de NCF locales.
    </p>

    @if(session('success'))
      <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-800 text-sm rounded">{{ session('success') }}</div>
    @endif
    @if(session('error'))
      <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-800 text-sm rounded">{{ session('error') }}</div>
    @endif

    @if(! $doctor)
      <div class="p-4 bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded">
        Tu usuario no está asociado a un doctor. Si eres admin, accede a esta página con <code>?doctor_id=ID</code>.
      </div>
    @else
      <form method="POST" action="{{ route('settings.kontab-billing.update') }}" class="space-y-5">
        @csrf

        <label class="flex items-center gap-3">
          <input type="checkbox" name="enabled" value="1" id="enabled-toggle"
                 {{ $enabled ? 'checked' : '' }}
                 class="w-5 h-5 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
          <span class="text-sm font-medium text-slate-800">Activar facturación electrónica para este doctor</span>
        </label>

        <div id="creds-block" class="space-y-4 {{ $enabled ? '' : 'opacity-60' }}">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Kontab API Key ID</label>
            <input type="text" name="kontab_api_key_id" value="{{ $apiKeyId }}"
                   placeholder="kt_live_..."
                   class="w-full border-slate-300 rounded-md text-sm font-mono focus:ring-blue-500 focus:border-blue-500">
            <p class="text-xs text-slate-500 mt-1">Lo generas en kontab.com.do → Configuración → API & Integraciones.</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Kontab API Secret</label>
            <input type="password" name="kontab_api_secret" autocomplete="new-password"
                   placeholder="{{ $hasSecret ? '•••••••• (vacío = mantener actual)' : 'sk_...' }}"
                   class="w-full border-slate-300 rounded-md text-sm font-mono focus:ring-blue-500 focus:border-blue-500">
            <p class="text-xs text-slate-500 mt-1">El secret sólo se muestra una vez al crearlo; pégalo aquí.</p>
          </div>
        </div>

        <div class="flex gap-3 items-center">
          <button type="submit"
                  class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-md">
            Guardar
          </button>
          <button type="button" id="btn-test"
                  class="bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm px-4 py-2 rounded-md">
            Probar conexión
          </button>
          <span id="test-result" class="text-sm"></span>
        </div>
      </form>
    @endif
  </div>
</div>

@push('scripts')
<script>
  const toggle = document.getElementById('enabled-toggle');
  const credsBlock = document.getElementById('creds-block');
  const btnTest = document.getElementById('btn-test');
  const result = document.getElementById('test-result');

  toggle?.addEventListener('change', () => {
    credsBlock.classList.toggle('opacity-60', !toggle.checked);
  });

  btnTest?.addEventListener('click', async () => {
    const keyId = document.querySelector('input[name="kontab_api_key_id"]').value.trim();
    const secret = document.querySelector('input[name="kontab_api_secret"]').value;
    if (!keyId || !secret) {
      result.textContent = '⚠ Completa Key ID + Secret primero.';
      result.className = 'text-sm text-amber-600';
      return;
    }
    result.textContent = 'Probando…';
    result.className = 'text-sm text-slate-500';
    try {
      const res = await fetch("{{ route('settings.kontab-billing.test') }}", {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        body: JSON.stringify({ kontab_api_key_id: keyId, kontab_api_secret: secret }),
      });
      const data = await res.json();
      result.textContent = (data.ok ? '✓ ' : '✗ ') + data.message;
      result.className = 'text-sm ' + (data.ok ? 'text-green-700' : 'text-red-700');
    } catch (e) {
      result.textContent = '✗ Error: ' + e.message;
      result.className = 'text-sm text-red-700';
    }
  });
</script>
@endpush
@endsection
