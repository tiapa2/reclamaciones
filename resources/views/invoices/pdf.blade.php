<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Factura {{ $invoice->ncf_number }}</title>

  <style>
    /* DomPDF friendly CSS */

    @page { margin: 38px 42px; } /* top right bottom left */
    body {
      font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
      font-size: 10px;
      color: #111;
    }

    .row { width: 100%; }
    .col-left { width: 58%; float: left; }
    .col-right { width: 42%; float: right; text-align: right; }
    .clearfix { clear: both; }

    .title-name {
      font-size: 18px;
      font-weight: 700;
      margin: 0 0 10px 0;
    }

    .muted { color: #333; }
    .label { font-weight: 700; }

    .doc-title {
      font-size: 18px;
      font-weight: 800;
      color: #0ea5e9; /* sky-ish */
      margin-bottom: 6px;
    }

    .section-box {
      border: 1px solid #e5e7eb;
      background: #f7f7f7;
      padding: 6px 4px;
      margin-top: 18px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    .items {
      margin-top: 18px;
      border: 1px solid #e5e7eb;
    }

    .items thead th {
      background: #cdeacb; /* verde suave */
      color: #111;
      font-weight: 700;
      font-size: 10px;
      padding: 6px 4px;
      border-bottom: 1px solid #e5e7eb;
      text-align: left;
    }

    .items tbody td {
      padding: 6px 4px;
      border-bottom: 1px solid #e5e7eb;
      vertical-align: top;
      font-size: 10px;
    }

    .right { text-align: right; }

    .totals {
      margin-top: 18px;
      width: 100%;
    }

    .totals-left {
      width: 50%;
      float: left;
      font-weight: 700;
    }

    .totals-right {
      width: 50%;
      float: right;
      text-align: right;
      font-weight: 700;
    }

    .big-amount {
      font-size: 16px;
      font-weight: 800;
      margin-top: 6px;
    }

    .big-amount-right {
      font-size: 18px;
      font-weight: 900;
    }

    .signature-row {
      margin-top: 40px;
      width: 100%;
    }

    .sig {
      width: 33.33%;
      float: left;
      text-align: center;
      font-size: 10px;
      color: #111;
    }

    .sig-line {
      border-top: 1px solid #111;
      margin: 25px 18px 0 18px;
      padding-top: 6px;
    }

    /* Small helpers */
    .mb-6 { margin-bottom: 6px; }
    .mb-10 { margin-bottom: 10px; }
    .mt-6 { margin-top: 6px; }

  </style>
</head>
<body>

@php
  $invoiceNo = 'FT' . str_pad((string)$invoice->id, 8, '0', STR_PAD_LEFT);
  $validUntil = '-'; // si no tienes expires_at, déjalo así
@endphp

  {{-- Header (2 columnas) --}}
  <div class="row">
    <div class="col-left">
      <div class="title-name">{{ $invoice->doctor?->full_name }}</div>

      <div class="mb-6">
        <span class="label">RNC:</span> {{ $invoice->doctor?->rnc ?? '-' }}
        <span class="muted"> | </span>
        <span class="label">Tel.:</span> {{ $invoice->doctor?->phone ?? '-' }}
      </div>

      <div class="mb-10">
        <span class="label">Email:</span> {{ $invoice->doctor?->email ?? '-' }}
      </div>
    </div>

    <div class="col-right">
      <div class="doc-title">Factura Crédito Fiscal</div>

      <div class="mb-6"><span class="label">NCF:</span> {{ $invoice->kontab_ncf ?? $invoice->ncf_number ?: '-' }}</div>
      <div class="mb-6"><span class="label">Válido Hasta:</span> {{ $invoice->kontab_valid_until ?? $validUntil }}</div>
      <div class="mb-6"><span class="label">Factura No.:</span> {{ $invoiceNo }}</div>
      <div class="mb-6">
  <span class="label">Fecha:</span> 
  {{ optional($invoice->invoice_date)->translatedFormat('d M Y') }}
</div>
      <div class="mb-6"><span class="label">Condición</span> 30 Días</div>
    </div>

    <div class="clearfix"></div>
  </div>

  {{-- Aseguradora --}}
  <div class="section-box">
    <table>
      <tr>
        <td style="width:60%;">
          <span class="label">Aseguradora:</span> {{ $invoice->insurer?->name }}
        </td>
        <td style="width:40%; text-align:right;">
          <div><span class="label">RNC:</span> {{ $invoice->insurer?->rnc ?? '-' }}</div>
          <div><span class="label">Tel.:</span> {{ $invoice->insurer?->phone ?? '-' }}</div>
        </td>
      </tr>
    </table>
  </div>

  {{-- Items --}}
  <table class="items">
    <thead>
      <tr>
        <th style="width:20%;">AUTORIZACIÓN</th>
        <th style="width:30%;">PACIENTE</th>
        <th style="width:16%;">FECHA</th>
        <th style="width:16%;">AFILIADO</th>
        <th style="width:18%; text-align:right;">COBERTURA</th>
      </tr>
    </thead>
    <tbody>
      @forelse($invoice->items as $it)
        <tr>
          <td>{{ $it->authorization_no ?: '-' }}</td>
          <td>{{ $it->patient_name }}</td>
          <td>{{ optional($it->service_date)->toDateString() }}</td>
          <td>{{ $it->affiliate_no ?: '-' }}</td>
          <td class="right">${{ number_format((float)$it->amount, 2) }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="5" style="padding: 14px 8px; text-align:center; color:#555;">
            Esta factura no tiene detalles (items).
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>

  {{-- Totales --}}
  <div class="totals">
    <div class="totals-left">
      {{ $invoice->items->count() }} Servicios
      <div class="big-amount">${{ number_format((float)$invoice->total_amount, 2) }}</div>
    </div>

    <div class="totals-right">
      Total Facturado por {{ $invoice->insurer?->name }}:
      <span class="big-amount-right">${{ number_format((float)$invoice->total_amount, 2) }}</span>
    </div>

    <div class="clearfix"></div>
  </div>

  {{-- Representación fiscal e-CF (obligatoria para comprobantes electrónicos DGII) --}}
  @if ($invoice->kontab_invoice_id && $invoice->kontab_dgii_status === 'accepted')
    <div style="border-top:2px solid #2563eb; margin-top:24px; padding-top:12px;">
      @if ($invoice->kontab_qr_svg)
        <img src="{{ $invoice->kontab_qr_svg }}" style="width:130px; height:130px;" alt="QR e-CF" /><br>
      @endif
      @if ($invoice->kontab_security_code)
        <div style="color:#2563eb; font-size:11px; margin-top:4px;">
          <strong>Código de Seguridad:</strong> {{ $invoice->kontab_security_code }}
        </div>
      @endif
      @if ($invoice->kontab_fecha_firma)
        <div style="color:#2563eb; font-size:11px;">
          <strong>Fecha Firma:</strong> {{ $invoice->kontab_fecha_firma }}
        </div>
      @endif
    </div>
  @endif

  {{-- Firmas --}}
  <div class="signature-row">
    <div class="sig">
      <div class="sig-line">ENTREGADO POR</div>
    </div>
    <div class="sig">
      <div class="sig-line">RECIBIDO POR</div>
    </div>
    <div class="sig">
      <div class="sig-line">FIRMA Y SELLO</div>
    </div>

    <div class="clearfix"></div>
  </div>

</body>
</html>
