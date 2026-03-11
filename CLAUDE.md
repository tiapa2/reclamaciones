# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# Setup (first time)
composer setup          # install deps, generate key, migrate, build assets

# Development
composer dev            # starts server + queue listener + log watcher concurrently
npm run dev             # Vite hot reload (included in composer dev)

# Testing
composer test           # clears config cache, then runs Pest tests
php artisan test --filter TestName  # run a single test

# Linting
./vendor/bin/pint       # format PHP code with Laravel Pint

# Database
php artisan migrate
php artisan db:seed
```

## Architecture

**Domain:** Medical invoice management and insurance claims system for the Dominican Republic, supporting NCF (Número de Comprobante Fiscal) fiscal documents.

**Stack:** Laravel 12, SQLite (local dev), Tailwind CSS + Alpine.js + Vite, Pest for tests, DomPDF for PDF generation, Spatie Permission for RBAC.

### Key Domain Concepts

- **Doctor** – Medical professional. Has a `user_id` for portal access. Related to Insurers via a `doctor_insurer` pivot with a `doctor_code` per insurer.
- **Insurer** – Insurance company.
- **Invoice** – Core entity. Belongs to Doctor + Insurer + NcfType. Has `status` (active/void) and `payment_status`.
- **InvoiceItem** – Line items on an invoice.
- **InvoicePayment** – Payment records against an invoice.
- **InvoiceReconciliation** – Tracks the reconciliation process between submitted invoices and insurer records. Has AI-assisted auto-upload flow.
- **Factoring** – Invoice financing: doctor sells invoices at a discount. Tracks `discount_rate`, `commission_rate`, `net_to_doctor`, and `status` (pending/collected/cancelled).
- **NcfType** / **DoctorNcfAuthorization** – Fiscal document types and per-doctor authorization records.

### Multi-Tenancy / Scoping

`app/Support/DoctorScope.php` — Doctors can only see their own records. Applied globally to scope queries by authenticated doctor's user.

### Role-Based Access

Spatie Permission (`spatie/laravel-permission`). Roles and permissions defined in the database. Check `config/permission.php` for the model list.

### PDF Generation

`barryvdh/laravel-dompdf` used for invoice PDFs. Template: `resources/views/invoices/pdf.blade.php`.

### Routes Structure

All business routes in `routes/web.php` under `auth` + `verified` middleware. Key resource groups: `/invoices`, `/doctors`, `/insurers`, `/factorings`, `/payments`. Reconciliation routes are nested under invoices.

### Queue / Cache / Sessions

All use the `database` driver (tables: `jobs`, `cache`, `sessions`). Run `php artisan queue:work` (included in `composer dev`).
