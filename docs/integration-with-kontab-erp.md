# Integración Kontab Reclamaciones ↔ Kontab ERP

Este documento describe cómo cada **company admin de kontab.com.do** (ERP) habilita
facturación electrónica para sus médicos en **reclamaciones.vldigitalagency.com**.

## Flujo end-to-end

```
1. Admin Kontab ERP genera API credential (key + secret) para su company
   → Configuración → API & Integraciones → Generar credencial.
   Scopes recomendados: invoices.sales:read/write, contacts:read/write,
   dgii:send, dgii:read, lookups:read.

2. Admin Reclamaciones edita el médico → tab "Facturación e-CF":
   - Activa el toggle.
   - Pega la API Key ID + Secret.
   - Botón "Probar conexión".
   - Guarda.

3. Médico crea factura en Reclamaciones como siempre.
   → Reclamaciones llama a Kontab ERP, que crea contacto (ARS), postea
     factura, asigna NCF y envía a DGII. Reclamaciones queda con
     kontab_invoice_id, kontab_ncf, kontab_dgii_status='pending'.

4. DGII responde (asíncrono, minutos/horas).
   → Kontab ERP dispara webhook a Reclamaciones (ver abajo).
   → Reclamaciones actualiza kontab_dgii_status a 'accepted' o 'rejected'.
```

## Webhook que Kontab ERP debe enviar a Reclamaciones

Cada company admin que active facturación electrónica para sus médicos
**debe registrar este webhook en su company de Kontab ERP**:

- **URL**: `https://reclamaciones.vldigitalagency.com/webhooks/kontab`
- **Eventos**:
  - `invoice.dgii.accepted`
  - `invoice.dgii.rejected`
- **Activo**: sí
- **Secret**: ⚠️ **debe coincidir con `KONTAB_WEBHOOK_SECRET`** del .env del
  servidor de Reclamaciones. Coordina con el admin de Reclamaciones para
  obtener el valor (compartido entre todas las companies que integran).
  Sin coincidencia, Reclamaciones devuelve 401 y la factura queda en
  `dgii_status=pending` indefinidamente.

### Headers que envía Kontab ERP (referencia)

```
POST /webhooks/kontab
X-Kontab-Event: invoice.dgii.accepted
X-Kontab-Timestamp: <unix>
X-Kontab-Signature: sha256=<hex(HMAC-SHA256(secret, ts + '.' + rawBody))>
Content-Type: application/json
```

Body:

```json
{
  "event": "invoice.dgii.accepted",
  "data": {
    "invoice_id": 123,
    "ncf": "E310000000123",
    "track_id": "...",
    "security_code": "...",
    "dgii_status": "Aceptado"
  }
}
```

## Variables de entorno necesarias en Reclamaciones (server)

```env
APP_URL=https://reclamaciones.vldigitalagency.com
KONTAB_API_BASE_URL=https://kontab.com.do/api/integration/v1
KONTAB_WEBHOOK_SECRET=<el shared secret coordinado>
```

`KONTAB_WEBHOOK_SECRET` es **único para toda la instalación de Reclamaciones**
en este momento (v1). Si en el futuro queremos secretos por-company, hay
que mover el campo a `doctors.kontab_webhook_secret_encrypted` y el
controller debe resolverlo por `kontab_invoice_id` antes de validar.

## Lo que no hace Reclamaciones

- ❌ No genera el NCF cuando el toggle está activo (Kontab ERP lo asigna).
- ❌ No envía a DGII (Kontab ERP lo hace).
- ❌ No edita facturas ya enviadas a Kontab ERP. Para corregir → anular
  + emitir nota de crédito.

## Lo que sí mantiene Reclamaciones

- PDF se genera localmente (formato ARS-friendly que ya conocen las
  aseguradoras), usando el `kontab_ncf` como número de comprobante.
- Cobros, factoring, conciliaciones — todo igual que antes.
- Médicos sin el toggle siguen con NCF locales (flujo anterior intacto).
