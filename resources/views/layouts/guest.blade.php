<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ 'Yiraida Pays', 'Factoring Médico' }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased
             bg-[#FDFDFC] text-[#1b1b18]
             dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">

    <div class="min-h-screen flex flex-col items-center justify-center px-4">

        {{-- Branding --}}
        <div class="mb-6 text-center">
            <a href="/" class="inline-flex items-center gap-3">
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

                <div class="text-left leading-tight">
                    <div class="text-sm font-semibold">
                        Yiraida Pays
                    </div>
                    <div class="text-xs text-black/60 dark:text-white/60">
                        Portal de doctores
                    </div>
                </div>
            </a>
        </div>

        {{-- Card --}}
        <div class="w-full max-w-md rounded-3xl
                    border border-black/10 dark:border-white/15
                    bg-white dark:bg-[#101010]
                    shadow-xl p-6">

            {{ $slot }}

        </div>

        {{-- Footer helper --}}
        <div class="mt-6 text-xs text-black/50 dark:text-white/50 text-center">
            Acceso seguro • Facturas • Pagos • Conciliaciones
        </div>

    </div>
</body>
</html>