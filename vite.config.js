import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            // calendario.js aparte (11/09/2026, Fase 3) — solo se carga en
            // admin/calendario/index.blade.php, no en layouts.admin (para
            // no meter FullCalendar en todas las pantallas admin).
            // portal-calendario.js (Fase 6) — mismo criterio, solo se
            // carga en portal/salones/show.blade.php.
            input: [
                'resources/css/app.css', 'resources/js/app.js',
                'resources/js/calendario.js', 'resources/js/portal-calendario.js',
            ],
            refresh: true,
        }),
    ],
});
