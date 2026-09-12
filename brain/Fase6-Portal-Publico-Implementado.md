# Fase 6 — Portal público — Implementado (11/09/2026)

Cubre CL-1/CL-2/CL-3 y §3.14, ver `brain/Historias_Usuario_Pantallas_RentSalon_Pro.md`. Última
fase del roadmap original.

## Decisión confirmada con el usuario

Solo las pantallas públicas en esta pasada — AN-8 (autoservicio de logo/color en
`/admin/configuracion`) queda fuera, el Super Admin sigue editando la marca desde Plataforma.

## Hallazgo real: AN-4 ya estaba construido

Explorando antes de planear: `ReservacionController::confirmar()/rechazar()`,
`ReservacionPolicy::cambiarEstado()` y `ReservacionService::crear()` (que ya valida conflicto de
horario en una transacción con lock) ya existían desde que se activó el módulo de Reservaciones,
antes de esta sesión — no era parte de esta fase. El portal público solo necesitaba LLAMAR a
`ReservacionService::crear()` con `origen='portal_publico'`/`estado='pendiente'`, reusando esa
lógica tal cual.

## Qué se construyó

- `PortalController` (home, listado/ficha de salones, listado/ficha de paquetes, disponibilidad
  JSON) + `SolicitudController` (formulario + registro de la solicitud) — fuera del namespace
  `Admin\`, dentro del grupo `middleware('negocio')` pero SIN `auth`.
- `layouts/portal.blade.php` — el único layout de todo el proyecto con branding REALMENTE dinámico
  (`--brand: {{ color_primario }}` como custom property CSS + estilo inline), a diferencia del
  resto del panel (clases Tailwind fijas) — Tailwind JIT no puede generar clases desde un hex
  arbitrario en runtime.
- Calendario público de disponibilidad — reusa FullCalendar (ya era dependencia desde Fase 3),
  entry Vite aparte (`portal-calendario.js`) sin panel lateral ni colores por estado (un solo
  "Ocupado"), fuente de eventos SIN ningún dato de cliente (confirmado con un test explícito).
- Formulario de solicitud: el servidor decide `precio_total` (del `paquete.precio_base` vigente)
  y `hora_fin` (`hora_inicio` + `paquete.duracion_horas`) — nunca del request. Confirmación
  renderizada DIRECTO desde la respuesta del POST (no una URL GET por id) para no dejar enumerar
  reservaciones de otros clientes.

## 2 bugs reales encontrados y corregidos — ninguno introducido por esta fase, ambos preexistentes

1. **Orden de middleware — fuga de aislamiento multi-tenant real**: `IdentificarNegocio` no
   estaba en `$middlewarePriority`, así que su posición relativa a `SubstituteBindings` (el
   middleware que resuelve `{salon}`/`{paquete}` vía route model binding) dependía de qué OTRO
   middleware hubiera en la ruta. En `/admin/*` (con `auth`, que SÍ está priorizado antes que
   `SubstituteBindings`) el orden salía bien "de casualidad". En el portal público (sin `auth`,
   la primera ruta de TODO el proyecto sin ese middleware) `SubstituteBindings` corría ANTES —
   `app('negocio_actual')` todavía no existía, `NegocioScope` no filtraba nada, y un salón de
   OTRO negocio pasaba route model binding sin dar 404. Encontrado por un test de aislamiento
   multi-tenant, no manualmente. Fix: `$middleware->prependToPriorityList(SubstituteBindings::class, IdentificarNegocio::class)`
   en `bootstrap/app.php` — soluciona el orden para TODAS las rutas del proyecto, no un parche
   local al portal.
2. **`Reservacion::existeConflictoDeHorario()` comparaba fecha por texto exacto**: la columna
   `fecha_evento` castea `'date'`, pero Eloquent SIEMPRE serializa ese cast como datetime completo
   (`'Y-m-d 00:00:00'`) al guardar, sin importar si se le asignó una fecha pura — contra MySQL
   (columna `DATE` real) esto "funcionaba" porque el motor trunca la hora en el storage sin que
   nadie lo pida; contra SQLite (toda la suite de tests) el `where('fecha_evento', $fecha)` NUNCA
   matcheaba, así que la regla anti doble-booking corría en un no-op silencioso bajo tests desde
   que se escribió (sin impacto en producción/MySQL, confirmado). Encontrado escribiendo el test
   de "fecha ya ocupada" del portal. Fix: `whereDate('fecha_evento', $fecha)` — agnóstico de
   motor, mismo comportamiento en MySQL, correcto ahora también en SQLite.

## Verificado

- 14 tests nuevos (`PortalControllerTest`, `SolicitudControllerTest`): pantallas públicas sin
  auth, salón/paquete inactivo → 404, disponibilidad sin datos de cliente, aislamiento
  multi-tenant (el que expuso el bug de middleware), precio decidido por el servidor, fecha ya
  ocupada rechaza sin crear nada, paquete que no aplica al salón rechaza, confirmación sin URL
  enumerable. `ExampleTest` actualizado (el placeholder `/` → redirect a login de Fase 3 ya no
  aplica, ahora `/` es el home real). Suite completa: **124/124 passed**, sin regresión.
- Manual, contra Apache (`:8080`) + MySQL reales, con "Salones La Paz": recorrido completo del
  cliente (home → salones → ficha con calendario público → ficha de paquete) → solicitud real vía
  POST con folio devuelto → confirmado en `/admin/reservaciones` que aparece "pendiente" con los
  datos correctos (`precio_total=15000` del paquete, no manipulable; `hora_fin` calculada
  correctamente) → `admin.reservaciones.confirmar` (AN-4, funcionalidad ya existente) la pasó a
  "confirmada" sin problema. Reservación de prueba borrada al terminar.

## Sin repo git

Este proyecto sigue sin `.git` — todo el trabajo es solo-filesystem, sin commit.

## Roadmap — completo

Con esto quedan completas TODAS las fases del roadmap original de RentSalon Pro
(`brain/Plan_Implementacion_RentSalon_Pro_v2.md`): Salones/Paquetes, Panel Super Admin Plataforma
(SA-1 a SA-5), Calendario maestro, POS/Productos/Inventario, Usuarios y permisos, Portal público.
Pendiente explícitamente fuera de alcance: AN-8 (autoservicio de marca), SA-4 con banco real
(sigue en `MockQrGateway` hasta que haya credenciales de Unión/BNB/BCP).
