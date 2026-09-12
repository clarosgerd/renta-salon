import { Calendar } from 'fullcalendar/all';
import classicThemePlugin from 'fullcalendar/themes/classic';
import 'fullcalendar/skeleton.css';
import 'fullcalendar/themes/classic/theme.css';
import 'fullcalendar/themes/classic/palette.css';

/**
 * Calendario público de disponibilidad (Fase 6, 11/09/2026) — versión
 * reducida de calendario.js (Fase 3): sin panel lateral, sin colores por
 * estado (un solo color "ocupado", ver PortalController::disponibilidad —
 * la fuente de eventos ya viene sin datos de cliente). Entry Vite aparte,
 * cargado SOLO en portal/salones/show.blade.php.
 */
document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('calendario-publico');
    if (!el) return;

    const calendar = new Calendar(el, {
        // Mismo bug real que calendario.js (Fase 3) — los temas de
        // FullCalendar v7 son plugins, no solo CSS; sin registrar
        // classicThemePlugin acá el CSS del tema quedaba huérfano.
        plugins: [classicThemePlugin],
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth',
        },
        locale: 'es',
        height: 'auto',
        events: {
            url: el.dataset.disponibilidadUrl,
            method: 'GET',
            failure: () => {
                alert('No se pudo cargar la disponibilidad.');
            },
        },
        dateClick: (info) => {
            // Un día pasado o ya ocupado igual navega al formulario — la
            // validación real (conflicto de horario) la hace el servidor
            // al enviar, esto es solo una ayuda visual/de navegación.
            window.location.href = `${el.dataset.solicitarUrl}?salon=${el.dataset.salonId}&fecha=${info.dateStr}`;
        },
    });

    calendar.render();
});
