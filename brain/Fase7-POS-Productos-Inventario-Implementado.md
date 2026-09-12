# Fase 7 — POS + Productos + Inventario — Implementado (11/09/2026)

Cubre CJ-1 a CJ-4 (Cajero) y la parte de catálogo/stock de AN-7, ver
`brain/Historias_Usuario_Pantallas_RentSalon_Pro.md` §1.4 y §3.8-§3.10.

## Decisión confirmada con el usuario

El corte de caja (CJ-4/§3.9) se construyó como **reporte del día, sin el bloqueo de "cerrar
turno"** que pide el texto literal — eso hubiera requerido una tabla `turnos` nueva fuera del
esquema ya diseñado. Se cumple el criterio real (ver mi corte del día, efectivo vs. QR, total a
entregar) sin esa pieza extra.

## Punto de partida: igual que SA-4, el esquema ya existía sin usar

`productos`, `inventario`, `movimientos_inventario`, `ventas_pos`, `detalle_venta`, `pagos_qr`
(esta última YA la usaba `PagoAbono` para QR de reservaciones — el POS reutiliza la misma
infraestructura, solo `venta_pos_id` en vez de `pago_abono_id`) y los 6 modelos Eloquent
correspondientes ya existían y compilaban. Faltaban policies, requests, servicios, controllers,
vistas y rutas — nada de esquema nuevo.

## Qué se construyó

- **Productos**: CRUD simple (`ProductoController`/`ProductoService`/`ProductoPolicy`, mismo
  esqueleto que `SalonController`). Alta con stock inicial opcional (crea la fila de Inventario
  general de una vez). Solo `admin_negocio`/`super_admin_plataforma` administran el catálogo
  (mismo criterio que `SalonPolicy`: ninguna historia de Admin Salón/Cajero pide crear productos).
- **Inventario**: existencias (tabla producto × salón/almacén general, con indicador de stock
  bajo) + movimientos (listado cronológico + formulario de entrada manual/reabasto).
  `InventarioController` reusa `ProductoPolicy` (viewAny/update), sin Policy propia — mismo
  patrón que `NegocioConfigPagoController` reusando `NegocioPolicy`. El tipo `ajuste` queda en el
  enum de la tabla para el futuro, sin UI todavía (solo se expone "entrada", tal cual pide
  §3.10 literalmente).
- **POS**: pantalla de venta con carrito 100% vainilla JS (buscador cliente-side, cantidad
  editable, sin dependencias nuevas — mismo criterio que el Calendario). `VentaPosService` es el
  análogo exacto de `PagoAbonoService` (registrar → generarQr si aplica → polling `estadoQr` →
  `confirmarManual` de respaldo), reusando el mismo `PagoQrGatewayFactory`/`MockQrGateway`.
  `VentaPosPolicy`: los 4 roles que pueden operar un POS (`cajero`, `admin_salon`,
  `admin_negocio`, `super_admin_plataforma`).

### Detalle real que valía la pena resolver: el QR nunca se persistía en ningún lado

`PagoQr.payload_respuesta` guardaba el payload crudo del banco, pero la IMAGEN del QR
(`QrResponse::imagenBase64`) no quedaba en ninguna columna — el mismo problema existe hoy en el
flujo de abono de Reservaciones (por eso esa pantalla solo dice "el sistema generará el QR..." en
vez de mostrarlo de verdad). Para el POS, donde CJ-3 exige literalmente "el sistema muestra el
código en pantalla", `VentaPosService::generarQrParaVenta()` guarda la imagen DENTRO de
`payload_respuesta` (`+ ['imagen_base64' => ...]`) — sin migración nueva, esa columna ya es JSON
libre. Esto además sobrevive un F5 de la pantalla, cosa que el flujo de abonos todavía no hace.
**Nota para más adelante**: el mismo fix (2 líneas) aplicaría igual de bien a
`PagoAbonoService::generarQrParaAbono()` si se quiere completar esa pantalla — no se tocó en esta
pasada por quedar fuera del alcance de "POS + Productos + Inventario".

### Descuento de stock

Siempre contra el inventario **general** (`salon_id = null`) — el POS (§3.8) no tiene selector de
salón, solo "vincular a reservación" opcional. El precio de cada línea lo decide el servidor
(`producto->precio_venta` vigente), nunca el cliente — mismo criterio de revalidación server-side
que el resto del ecosistema. Todo dentro de una única transacción: si el stock no alcanza para
CUALQUIER ítem del carrito, se revierte la venta completa (nada de detalle/movimiento a medias).

## Verificado

- 21 tests nuevos (`ProductoControllerTest`, `InventarioControllerTest`, `PosControllerTest`) —
  CRUD, aislamiento multi-tenant, rol no autorizado, venta efectivo descuenta stock y crea
  detalle+movimiento, venta QR genera `PagoQr` con `venta_pos_id`, **stock insuficiente rechaza
  sin crear absolutamente nada** (transacción atómica, verificado con 3 asserts de conteo en 0),
  venta vinculada a reservación aparece en su historial, corte del día solo trae lo del cajero
  autenticado, corte separa correctamente efectivo/QR confirmado/QR pendiente.
- Suite completa: **99/99 passed**, sin regresión.
- Manual, contra Apache (`:8080`) + MySQL reales, con "Salones La Paz" y un Cajero de prueba:
  login real → `/admin/pos` renderiza con el producto real → venta en efectivo de 3 unidades
  (stock 20→17, total Bs.37.50 correcto) → confirmado que el Cajero da **403** en
  `/admin/productos` → venta QR de 2 unidades → recarga de `/admin/pos` muestra la imagen del QR
  real en base64 (confirma el fix de persistencia) → polling de `estado-qr` responde "generado" →
  `confirmar-manual` deja el `PagoQr` en "pagado" → corte de caja muestra Efectivo Bs.37.50, QR
  confirmado Bs.25.00, QR pendiente Bs.0.00, Total a entregar Bs.62.50 — todo cuadra. Datos de
  prueba (producto, ventas, usuario Cajero) borrados al terminar.

## Sin repo git

Este proyecto sigue sin `.git` — todo el trabajo es solo-filesystem, sin commit.
