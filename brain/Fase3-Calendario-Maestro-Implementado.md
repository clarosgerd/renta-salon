# Fase 3 — Calendario maestro (11/09/2026)

Implementado sobre AN-3 y §3.2 de `Historias_Usuario_Pantallas_RentSalon_Pro.md`. `fullcalendar@7.1.0`
ya estaba en `package.json`/`node_modules` pero sin usar — primer feature que lo consume.

## Qué se construyó

- `CalendarioController@index` (vista) + `@eventos` (JSON, fuente de eventos de FullCalendar) —
  reusa `ReservacionPolicy::viewAny` (mismo permiso que el listado de reservaciones, sin Policy
  nueva) y el scoping de salones por rol.
- **Refactor**: `ReservacionController::salonesDelUsuario()` se extrajo a
  `app/Http/Controllers/Concerns/ScopesSalonesPermitidos.php` — lo necesitaban los 2 controllers,
  evita duplicar el mismo método.
- `resources/js/calendario.js` — entry Vite **aparte** (no se suma a `layouts.admin`, solo se
  carga en `admin/calendario/index.blade.php`, para no meter FullCalendar en todas las pantallas
  admin). `import { Calendar } from 'fullcalendar/all'` — un solo import trae Calendar +
  dayGrid/timeGrid/interaction/list, sin armar los plugins a mano.
- Vista con filtro de salones (checkboxes, todos marcados por default), leyenda de colores, panel
  lateral (vainilla, sin Alpine — `hidden` toggleado por JS) que se llena con
  `event.extendedProps` sin navegar, botón "Ver ficha completa" que sí navega.
- Colores por estado: amarillo=pendiente, verde=confirmada, gris=cancelada (de la spec) + azul
  para `finalizada` (no especificado en la historia, agregado y documentado en el código).
- Clic en un día vacío → `/admin/reservaciones/crear?fecha=YYYY-MM-DD` — un solo cambio en
  `_form.blade.php` (fallback a `request('fecha')` en el value del campo), sin ruta/controller
  nuevo.
- El link "Calendario" del sidebar (ya estaba, guardado con `Route::has()` desde la Fase 0/2)
  apareció solo apenas existió la ruta — confirmado en vivo.

## Verificación

- 7 tests nuevos (`tests/Feature/CalendarioControllerTest.php`): acceso ok, 403 para
  `super_admin_plataforma` (el único rol real que `ReservacionPolicy::viewAny()` no incluye),
  filtro por rango de fechas, filtro por salón, color correcto por estado, un `cajero` solo ve
  sus salones asignados, aislamiento multi-tenant.
- Regresión completa: **58/58 tests pasando** (51 previos + 7 nuevos).
- `npm run build` corrido — genera `calendario-*.js`/`calendario-*.css` en `public/build`
  (314 KB / 90 KB gzip — todo FullCalendar, esperado para un calendario completo).
- QA manual real por HTTP (Apache real en el puerto 8080, BD MySQL real): login tenant → sidebar
  muestra "Calendario" → creé una reservación de prueba real vía `POST /admin/reservaciones` →
  `GET /admin/calendario` 200, assets de Vite bien resueltos (`<script type="module">` apuntando
  al build real) → `GET /admin/calendario/eventos?start=...&end=...` devuelve la reservación con
  el shape exacto que espera FullCalendar (`start`/`end` ISO, `color` #10b981 = confirmada,
  `extendedProps` con folio/cliente/salón/saldo/`showUrl`) → confirmado que
  `/admin/reservaciones/crear?fecha=2026-10-05` precarga el campo (`value="2026-10-05"`).
  Reservación de prueba borrada al terminar.

## Pendiente / fuera de alcance

Toggle a vista semanal ya viene incluido (`timeGridWeek` en el header, parte de `fullcalendar/all`)
pero no se probó a fondo manualmente (solo confirmado que la librería la trae). El resto del
roadmap sigue igual: Fase 5 (QR real), 6 (Portal público), 7 (POS/Productos/Inventario), 8
(Usuarios/permisos) — ver `brain/Fase1-Panel-SuperAdmin-Plataforma-Implementado.md` y
`brain/Fase-Salones-Paquetes-Multitenant-Implementado.md` para el resto del estado.
