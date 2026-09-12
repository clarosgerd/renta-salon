import { Calendar } from 'fullcalendar/all';
import 'fullcalendar/skeleton.css';
import 'fullcalendar/themes/classic/theme.css';
import 'fullcalendar/themes/classic/palette.css';

/**
 * Calendario maestro (11/09/2026, Fase 3) — entry Vite aparte, cargado
 * SOLO en admin/calendario/index.blade.php (no en layouts.admin, para no
 * meter FullCalendar en todas las pantallas admin). Vainilla, sin Alpine
 * — mismo criterio que el resto del panel.
 */
document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('calendario');
    if (!el) return;

    const panel = document.getElementById('panelLateral');
    const filtros = document.querySelectorAll('.filtro-salon');

    function salonesSeleccionados() {
        return Array.from(filtros)
            .filter((cb) => cb.checked)
            .map((cb) => cb.value);
    }

    function mostrarPanel(event) {
        const props = event.extendedProps;
        document.getElementById('panelFolio').textContent = props.folio;
        document.getElementById('panelCliente').textContent = props.cliente;
        document.getElementById('panelSalon').textContent = props.salon;
        document.getElementById('panelPaquete').textContent = props.paquete || 'Sin paquete';
        document.getElementById('panelSaldo').textContent = `Bs ${Number(props.saldo).toFixed(2)}`;
        document.getElementById('panelEstado').textContent = props.estado;
        document.getElementById('panelVerFicha').href = props.showUrl;
        panel.hidden = false;
    }

    document.getElementById('panelCerrar')?.addEventListener('click', () => {
        panel.hidden = true;
    });

    const calendar = new Calendar(el, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek',
        },
        locale: 'es',
        height: 'auto',
        events: {
            url: el.dataset.eventosUrl,
            method: 'GET',
            extraParams: () => ({ salon_id: salonesSeleccionados() }),
            failure: () => {
                alert('No se pudieron cargar las reservaciones.');
            },
        },
        eventClick: (info) => mostrarPanel(info.event),
        dateClick: (info) => {
            window.location.href = `${el.dataset.crearUrl}?fecha=${info.dateStr}`;
        },
    });

    calendar.render();

    filtros.forEach((cb) => cb.addEventListener('change', () => calendar.refetchEvents()));
});
