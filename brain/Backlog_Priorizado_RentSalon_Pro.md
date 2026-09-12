# RentSalon Pro — Backlog Priorizado por Fases

Este backlog cruza las **historias de usuario** ya definidas con el **roadmap de fases** del
plan de arquitectura, y marca qué piezas técnicas ya quedaron construidas en este proceso
(esquema, modelos, controlador de Reservaciones) para que el equipo de desarrollo arranque
directamente donde se quedó.

Leyenda de estado: ✅ Hecho · 🔧 Diseñado, falta código · ⬜ Pendiente

---

## Fase 0 — Setup multi-tenant *(1 semana)*

| Tarea | Estado | Nota |
|---|---|---|
| Instalar Laravel, Breeze, Tailwind | ⬜ | Ver guía "Configuración del proyecto" |
| Migraciones: `negocios`, `negocio_config_pago`, campos extra en `users` | ✅ | Entregadas en `rentsalon-schema.zip` |
| Modelo `Negocio`, `NegocioConfigPago` | ✅ | Entregados |
| `NegocioScope` + trait `BelongsToNegocio` | ✅ | Entregados |
| Middleware `IdentificarNegocio` (resolución por subdominio) | ✅ | Entregado |
| Configurar Virtual Host / subdominios en el entorno de desarrollo | ⬜ | Guía específica ya entregada (Herd/Valet y XAMPP) |
| Roles con `spatie/laravel-permission` | 🔧 | Paquete referenciado; falta seeder de roles/permisos concretos |

---

## Fase 1 — Panel Super Admin Plataforma *(3–4 días)*

| Historia | Tarea técnica | Estado |
|---|---|---|
| SA-1 Alta de negocio | `NegocioController` (CRUD) + vista `plataforma.negocios.create` | ⬜ |
| SA-2 Suspender negocio | Acción `suspender()`/`activar()` en `NegocioController` | ⬜ |
| SA-3 Listado con métricas | `index()` con conteos (`withCount('salones')`, reservaciones del mes) | ⬜ |
| SA-4 Configurar banco QR por negocio | `NegocioConfigPagoController` | ⬜ *(modelo ya existe)* |
| SA-5 Impersonar negocio (soporte) | Middleware/acción de impersonación + registro en auditoría | ⬜ *(opcional, no bloqueante)* |

---

## Fase 2 — Núcleo operativo: Salones, Paquetes, Reservaciones *(1–1.5 semanas)*

| Historia | Tarea técnica | Estado |
|---|---|---|
| AN-1 Alta de salones | `SalonController` + vistas `admin.salones.*` | ⬜ *(modelo `Salon` ya existe)* |
| AN-2 Crear paquetes | `PaqueteController` + vistas `admin.paquetes.*` | ⬜ *(modelo `Paquete` ya existe)* |
| AS-2 Reservación manual con validación de disponibilidad | `ReservacionController@store` + `ReservacionService::crear()` | ✅ **Entregado completo** |
| AN-4 Confirmar/rechazar solicitudes | `ReservacionController@confirmar/@rechazar` | ✅ **Entregado completo** |
| — Cancelar / finalizar reservación | `ReservacionController@cancelar/@finalizar` | ✅ **Entregado completo** |
| AS-1 / restricción por salón asignado | `ReservacionPolicy` + `User::salonesPermitidos()` | ✅ **Entregado completo** |
| — Vistas Blade de Reservaciones (index/create/show/edit) | Basadas en el wireframe de ficha de reservación ya validado | ⬜ **Siguiente paso lógico** |

> Esta fase quedó adelantada: todo el backend de Reservaciones (service, policy, form
> requests, controller, rutas) ya está resuelto. Lo único pendiente aquí son Salones,
> Paquetes (CRUD simples) y las vistas Blade.

---

## Fase 3 — Calendario *(3–5 días)*

| Historia | Tarea técnica | Estado |
|---|---|---|
| AN-3 Calendario maestro filtrable por salón | Endpoint JSON de eventos + integración FullCalendar en `admin.calendario.index` | ⬜ |
| CL-2 Calendario público de disponibilidad (solo ocupado/libre) | Endpoint público simplificado (sin datos de cliente) | ⬜ |

---

## Fase 4 — Finanzas: efectivo *(1 semana)*

| Historia | Tarea técnica | Estado |
|---|---|---|
| AS-3 Registrar abonos | `PagoAbonoController@store` (efectivo) | ⬜ *(modelo `PagoAbono` ya existe, con `total_abonado`/`saldo_pendiente` calculados)* |
| AN-5 / AS-4 Estado de cuenta + PDF | `barryvdh/laravel-dompdf` + vista `pdf.estado-cuenta` | ⬜ |
| — Historial de pagos por reservación | Ya resuelto por la relación `Reservacion::pagosAbonos()` cargada en `show()` | ✅ *(consulta lista, falta vista)* |

---

## Fase 5 — Finanzas: QR (Unión / BNB / BCP) *(1–1.5 semanas, depende del banco)*

| Historia | Tarea técnica | Estado |
|---|---|---|
| CJ-3 Cobrar QR de monto fijo | `PagoQrGatewayInterface` + adaptadores `BancoUnionQrGateway`, `BnbQrGateway`, `BcpQrGateway` | 🔧 *(interfaz diseñada en el plan; falta implementación real por banco — depende de credenciales de cada banco)* |
| — Consulta de estado / confirmación | Cron o Job `ConsultarEstadoPagoQr` + actualización de `PagoQr.estado` | ⬜ |
| — Confirmación manual de respaldo | Acción en `PagoAbonoController@confirmarManual` | ⬜ |
| SA-4 Configurar credenciales por negocio | Tabla y modelo `NegocioConfigPago` ya existen; falta UI y cifrado de credenciales en formulario | 🔧 |

> **Bloqueante externo:** esta fase no puede avanzar en integración real hasta tener
> credenciales de sandbox de Banco Unión, BNB y/o BCP (ver plan de arquitectura, sección de
> pagos QR). Se puede avanzar en paralelo con un adaptador "mock" para no detener el resto
> del desarrollo.

---

## Fase 6 — Portal público por negocio *(4–6 días)*

| Historia | Tarea técnica | Estado |
|---|---|---|
| CL-1 Explorar salones | `PortalPublicoController@salones` + vista pública | ⬜ |
| CL-2 Consultar disponibilidad | Reutiliza endpoint de calendario público (Fase 3) | ⬜ |
| CL-3 Formulario de solicitud | `SolicitudReservaController@store`, reutilizando `ReservacionService::crear()` con `estado: pendiente`, `origen: portal_publico` | 🔧 *(el service ya soporta esto sin cambios; falta el controller público y la vista)* |
| AN-8 Branding dinámico (logo/color) | View Composer que inyecta datos de `Negocio` en el layout público | ⬜ |

---

## Fase 7 — POS + Productos + Inventario *(1–1.5 semanas)*

| Historia | Tarea técnica | Estado |
|---|---|---|
| — CRUD de Productos | `ProductoController` | ⬜ *(modelo `Producto` ya existe)* |
| — Existencias e inventario mínimo | `InventarioController` (vista de existencias + alerta de stock bajo) | ⬜ *(modelo `Inventario` con scope `stockBajo()` ya existe)* |
| CJ-1 / CJ-2 Pantalla de venta, vincular a reservación | `PosController@index/@store` | ⬜ |
| — Descuento automático de inventario | `DetalleVentaObserver` | ✅ **Entregado completo** |
| CJ-4 Corte de caja | `PosController@corte` (agrupado por cajero/turno/método) | ⬜ |

> El "beneficio estrella" del POS —que el inventario se descuente solo— ya está
> resuelto a nivel de backend. Falta la interfaz de venta y el corte de caja.

---

## Fase 8 — Usuarios y permisos *(3–4 días)*

| Historia | Tarea técnica | Estado |
|---|---|---|
| AN-6 Crear usuarios y asignar salones | `UsuarioController` + tabla pivote `usuario_salon` | ⬜ *(modelo y pivote ya existen)* |
| — Roles (Admin Negocio / Admin Salón / Cajero) | Seeder de roles + permisos con Spatie | ⬜ |
| — Auditoría de acciones | `spatie/laravel-activitylog` en modelos clave | ⬜ *(paquete ya referenciado en la guía de instalación)* |

---

## Fase 9 — Pulido y despliegue *(1 semana)*

| Tarea | Estado |
|---|---|
| Pruebas de aislamiento multi-negocio (que un negocio no vea datos de otro) | ⬜ |
| Hosting, dominio wildcard en producción, certificados SSL wildcard | ⬜ |
| Capacitación a los negocios piloto | ⬜ |

---

## Fase 2 (post-lanzamiento, fuera del alcance actual)

- Notificaciones automáticas por WhatsApp (confirmación de reserva, recordatorio de saldo, confirmación de pago QR).
- Módulo de suscripciones del SaaS, si se decide cobrar mensualidad a cada negocio.
- Reportes comparativos entre salones/negocios para el Super Admin.

---

## Resumen de avance actual

De las ~10 fases del roadmap, lo que ya está resuelto a nivel de código (no solo diseño) es:

- **Toda la Fase 0** de esquema de datos y aislamiento multi-tenant (migraciones, modelos, scope, trait, middleware).
- **La columna vertebral de la Fase 2**: el módulo de Reservaciones completo (service, policy, form requests, controller, rutas) — la pieza más compleja de todo el sistema por la validación de disponibilidad.
- **El punto más delicado de la Fase 7**: el descuento automático de inventario vía Observer.

Lo que sigue de mayor a menor impacto para tener un MVP demostrable cuanto antes:
1. CRUD simples de **Salones** y **Paquetes** (rápidos, el modelo ya existe).
2. **Vistas Blade** de Reservaciones sobre los wireframes ya validados.
3. **Calendario** (Fase 3) — depende de tener Salones y Reservaciones con datos reales.
4. **Pagos en efectivo** (Fase 4) — antes de meterse a la integración QR, que tiene una dependencia externa (credenciales bancarias).
