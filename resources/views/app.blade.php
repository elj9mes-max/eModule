<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <script>
            /* Lite mode for slower phones: decided before first paint so heavy effects never render. */
            (function () {
                var mode = null;
                try { mode = localStorage.getItem('emodule_performance_mode'); } catch (e) {}
                if (mode !== 'lite' && mode !== 'full') {
                    var memory = navigator.deviceMemory || 8;
                    var cores = navigator.hardwareConcurrency || 8;
                    var saveData = navigator.connection && navigator.connection.saveData;
                    mode = (memory <= 4 || cores <= 4 || saveData) ? 'lite' : 'full';
                }
                document.documentElement.dataset.perf = mode;
            })();
        </script>

        <!-- Fonts & Scripts -->
        {{ \Illuminate\Support\Facades\Vite::fonts() }}
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
