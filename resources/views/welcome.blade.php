<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ 'Yiraida Pays', 'Portal del Doctor' }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    <!-- Styles / Scripts -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <style>
            /* deja tu tailwind inline aquí si no usas vite */
        </style>
    @endif
</head>

<body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] dark:text-[#EDEDEC] min-h-screen">
    {{-- Top bar --}}
    <header class="w-full border-b border-black/5 dark:border-white/10">
        <div class="mx-auto max-w-6xl px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div
                    class="h-9 w-9 rounded-xl  text-white dark:bg-white dark:text-black flex items-center justify-center shadow-sm">
                    {{-- mini icon --}}
                    <svg fill="none" width="30" id="Layer_2" data-name="Layer 2"
                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 62.65 80">
                        <defs>
                            <style>
                                .cls-1 {
                                    fill: #33bfe9;
                                }

                                .cls-2 {
                                    fill: #04586e;
                                }
                            </style>
                        </defs>
                        <g id="Layer_1-2" data-name="Layer 1">
                            <g>
                                <path class="cls-1"
                                    d="M3.15,79.27c16.43-2.47,31.09-12.02,39.52-26.37,6.22-10.59,8.82-23.56,6.95-35.76-.14-.21-.28-.15-.48-.14-1.59.13-3.52.74-5.13,1.01-.16-.01-.1-.3-.09-.44.12-1.15,1.7-4.84,2.2-6.21,1.4-3.81,2.87-7.61,4.37-11.38l12.16,14.69c-.73.06-1.52.4-2.2.51-.98.16-2.48.07-3.46.39-.62.21-.13,1.46-.11,1.98.76,24.11-11.41,46.39-33.4,56.74-5.77,2.71-13.25,5.07-19.63,5.3-.12,0-.23,0-.35,0-.17,0-.65-.04-.7,0-.84-.1-.06-.1.35-.35Z" />
                                <path class="cls-1"
                                    d="M46.78,57.81c-.11.1-.16.54-.4.38,1.05-1.84,2.3-3.56,3.35-5.4,5.78-10.1,8.97-21.79,9.25-33.45l.35-1.31.53,5.85c0,9.21-2.14,18.16-6.87,26.05-1.65,2.75-3.83,5.77-6.21,7.88Z" />
                                <path class="cls-1"
                                    d="M31.68,61.95c.08.33-.19.41-.35.61-3.37,4.08-8.82,7.77-13.59,10.04C12.25,75.21,6.11,76.98,0,77.17c-.03-.23.4-.32.58-.38,9.85-3.3,17.41-5.74,26.25-11.55.51-.33,4.63-3.5,4.84-3.29Z" />
                                <path class="cls-2"
                                    d="M3.15,79.27c-.41.25-1.19.25-.35.35-.02.01.04.24-.03.31-.09.08-2.02.08-2.33.04-.26-.03-.54-.14-.26-.35,1.01.04,1.99-.2,2.97-.35Z" />
                                <path class="cls-2" d="M3.85,79.62l-.21.12-.14-.12c.12,0,.23,0,.35,0Z" />
                            </g>
                        </g>
                    </svg>
                </div>
                <div class="leading-tight">
                    <div class="text-sm font-semibold">Yiraida Pays</div>
                    <div class="text-xs text-black/60 dark:text-white/60">Facturas • Pagos • Conciliaciones</div>
                </div>
            </div>

            @if (Route::has('login'))
                <nav class="flex items-center gap-2">
                    @auth
                        <a href="{{ url('/dashboard') }}"
                            class="px-4 py-2 rounded-lg border border-black/10 dark:border-white/15 hover:border-black/30 dark:hover:border-white/30 text-sm">
                            Ir a mi panel
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                            class="px-4 py-2 rounded-lg border border-transparent hover:border-black/10 dark:hover:border-white/15 text-sm">
                            Iniciar sesión
                        </a>
                    @endauth
                </nav>
            @endif
        </div>
    </header>

    {{-- Hero --}}
    <main class="mx-auto max-w-6xl px-6 py-10 lg:py-16">
        <div class="grid lg:grid-cols-2 gap-10 items-center">
            <div>
                <span
                    class="inline-flex items-center gap-2 text-xs font-medium px-3 py-1.5 rounded-full
                             border border-black/10 dark:border-white/15 bg-white/60 dark:bg-white/5">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    Portal del doctor en tiempo real
                </span>

                <h1 class="mt-4 text-4xl lg:text-5xl font-semibold tracking-tight">
                    Tu facturación,
                    <span class="text-black/60 dark:text-white/60">clara y al día.</span>
                </h1>

                <p class="mt-4 text-base lg:text-lg text-black/70 dark:text-white/70 leading-relaxed">
                    Revisa tus <b>facturas</b>, valida <b>pagos</b> y confirma <b>conciliaciones</b> por período.
                    Todo con estados, historial y trazabilidad para tu cierre mensual.
                </p>

                <div class="mt-6 flex flex-wrap gap-3">
                    @auth
                        <a href="{{ url('/dashboard') }}"
                            class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl bg-black text-white dark:bg-white dark:text-black text-sm font-medium hover:opacity-90">
                            Ver mis facturas
                            <svg class="ml-2" width="16" height="16" viewBox="0 0 24 24" fill="none">
                                <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                            class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl bg-black text-white dark:bg-white dark:text-black text-sm font-medium hover:opacity-90">
                            Entrar al portal
                        </a>
                    @endauth

                    <a href="#como-funciona"
                        class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl border border-black/10 dark:border-white/15 text-sm hover:border-black/30 dark:hover:border-white/30">
                        Ver cómo funciona
                    </a>
                </div>

                {{-- quick stats --}}
                <div class="mt-8 grid grid-cols-3 gap-3 max-w-md">
                    <div class="rounded-2xl border border-black/10 dark:border-white/15 bg-white dark:bg-white/5 p-4">
                        <div class="text-xs text-black/60 dark:text-white/60">Facturas</div>
                        <div class="mt-1 text-sm font-semibold">pendientes · pagadas · vencidas</div>
                    </div>
                    <div class="rounded-2xl border border-black/10 dark:border-white/15 bg-white dark:bg-white/5 p-4">
                        <div class="text-xs text-black/60 dark:text-white/60">Pagos</div>
                        <div class="mt-1 text-sm font-semibold">aplicados + en revisión</div>
                    </div>
                    <div class="rounded-2xl border border-black/10 dark:border-white/15 bg-white dark:bg-white/5 p-4">
                        <div class="text-xs text-black/60 dark:text-white/60">Conciliación</div>
                        <div class="mt-1 text-sm font-semibold">balance + trazabilidad</div>
                    </div>
                </div>
            </div>

            {{-- Right panel --}}
            <div class="relative">
                <div
                    class="absolute -inset-4 blur-3xl opacity-30
                            bg-gradient-to-tr from-rose-400 via-amber-300 to-sky-400">
                </div>

                <div
                    class="relative rounded-3xl border border-black/10 dark:border-white/15 bg-white dark:bg-[#101010] overflow-hidden shadow-xl">
                    <div class="p-5 border-b border-black/5 dark:border-white/10 flex items-center justify-between">
                        <div class="text-sm font-medium">Doctor Billing Overview</div>
                        <span class="text-xs px-2 py-1 rounded-full bg-black/5 dark:bg-white/10">corte #019</span>
                    </div>

                    <div class="p-5 space-y-4">
                        {{-- fake timeline --}}
                        <div class="rounded-2xl border border-black/10 dark:border-white/15 p-4">
                            <div class="flex items-center justify-between">
                                <div class="text-sm font-semibold">Período seleccionado</div>
                                <span class="text-xs text-black/60 dark:text-white/60">Feb 2026</span>
                            </div>
                            <div class="mt-3 h-2 rounded-full bg-black/5 dark:bg-white/10 overflow-hidden">
                                <div class="h-2 w-2/3 rounded-full bg-emerald-500"></div>
                            </div>
                            <div class="mt-2 text-xs text-black/60 dark:text-white/60">
                                Conciliación en curso: revisa pagos aplicados…
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="rounded-2xl border border-black/10 dark:border-white/15 p-4">
                                <div class="text-xs text-black/60 dark:text-white/60">Facturas</div>
                                <div class="mt-1 text-sm font-semibold">12</div>
                            </div>
                            <div class="rounded-2xl border border-black/10 dark:border-white/15 p-4">
                                <div class="text-xs text-black/60 dark:text-white/60">Pagos</div>
                                <div class="mt-1 text-sm font-semibold">8 aplicados</div>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-black/10 dark:border-white/15 p-4">
                            <div class="flex items-center justify-between">
                                <div class="text-sm font-semibold">Estado</div>
                                <span
                                    class="text-xs px-2 py-1 rounded-full bg-emerald-500/15 text-emerald-700 dark:text-emerald-300">
                                    listo para validar
                                </span>
                            </div>
                            <div class="mt-2 text-xs text-black/60 dark:text-white/60">
                                Confirma conciliación para cerrar el período sin diferencias.
                            </div>
                        </div>

                        <div class="text-xs text-black/50 dark:text-white/50">
                            Historial y trazabilidad: movimientos, cambios, validaciones y auditoría por usuario.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- How it works --}}
        <section id="como-funciona" class="mt-14 lg:mt-20">
            <div class="flex items-end justify-between gap-6">
                <div>
                    <h2 class="text-2xl font-semibold">Cómo funciona</h2>
                    <p class="mt-2 text-sm text-black/70 dark:text-white/70">
                        Un flujo simple para que el doctor tenga control total sin complicaciones.
                    </p>
                </div>
            </div>

            <div class="mt-6 grid md:grid-cols-3 gap-4">
                <div class="rounded-3xl border border-black/10 dark:border-white/15 bg-white dark:bg-white/5 p-6">
                    <div class="text-xs font-medium text-black/60 dark:text-white/60">Paso 1</div>
                    <div class="mt-2 text-lg font-semibold">Entra a tu portal</div>
                    <p class="mt-2 text-sm text-black/70 dark:text-white/70">
                        Accede a tus períodos y estados de facturación con seguridad.
                    </p>
                </div>

                <div class="rounded-3xl border border-black/10 dark:border-white/15 bg-white dark:bg-white/5 p-6">
                    <div class="text-xs font-medium text-black/60 dark:text-white/60">Paso 2</div>
                    <div class="mt-2 text-lg font-semibold">Revisa facturas y pagos</div>
                    <p class="mt-2 text-sm text-black/70 dark:text-white/70">
                        Filtra por aseguradora, fecha, estado y descarga soportes cuando lo necesites.
                    </p>
                </div>

                <div class="rounded-3xl border border-black/10 dark:border-white/15 bg-white dark:bg-white/5 p-6">
                    <div class="text-xs font-medium text-black/60 dark:text-white/60">Paso 3</div>
                    <div class="mt-2 text-lg font-semibold">Conciliación y cierre</div>
                    <p class="mt-2 text-sm text-black/70 dark:text-white/70">
                        Confirma la conciliación del período y deja todo listo para tu cierre mensual.
                    </p>
                </div>
            </div>
        </section>

        {{-- Footer CTA --}}
        <section class="mt-14 lg:mt-20">
            <div
                class="rounded-3xl border border-black/10 dark:border-white/15 bg-black text-white dark:bg-white dark:text-black p-8 lg:p-10 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                <div>
                    <div class="text-sm font-semibold">Tu panel médico, listo para usar</div>
                    <div class="mt-1 text-sm opacity-80">
                        Menos dudas, más control: facturas, pagos y conciliaciones en un solo lugar.
                    </div>
                </div>

                <div class="flex gap-3">
                    @auth
                        <a href="{{ url('/dashboard') }}"
                            class="px-5 py-2.5 rounded-xl bg-white text-black dark:bg-black dark:text-white text-sm font-medium hover:opacity-90">
                            Ir a mi panel
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                            class="px-5 py-2.5 rounded-xl bg-white text-black dark:bg-black dark:text-white text-sm font-medium hover:opacity-90">
                            Entrar al portal
                        </a>
                    @endauth
                </div>
            </div>
        </section>
    </main>

    <footer class="border-t border-black/5 dark:border-white/10">
        <div
            class="mx-auto max-w-6xl px-6 py-6 text-xs text-black/60 dark:text-white/60 flex items-center justify-between">
            <span>© {{ date('Y') }} Yiraida Pays</span>
            <span class="hidden sm:inline">Facturas • Pagos • Conciliación • Auditoría</span>
        </div>
    </footer>
</body>

</html>
