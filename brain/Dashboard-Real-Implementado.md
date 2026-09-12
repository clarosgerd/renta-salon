# Dashboard real (11/09/2026) — Implementado

Reemplaza el stub mínimo de la activación multi-tenant (10/09/2026, que solo mostraba 3 conteos
genéricos sin ningún diseño). El usuario compartió un mockup de referencia con 4 tarjetas +
"Próximos eventos"/"Solicitudes nuevas" y pidió que el dashboard se viera así. Corresponde a §3.1
de `brain/Historias_Usuario_Pantallas_RentSalon_Pro.md`, salvo el gráfico de ingresos del mes (no
estaba en el mockup, no se construyó en esta pasada).

## Qué se construyó

- `DashboardController` reescrito — 4 métricas (eventos esta semana, solicitudes pendientes,
  saldo total por cobrar, productos con stock bajo) + 2 listados (próximos eventos, solicitudes
  nuevas con botones rápidos Confirmar/Rechazar). Mismo criterio de scoping por rol que
  Reservaciones/Calendario: Admin Negocio/Super Admin ven todo el negocio, Admin Salón/Cajero
  solo sus salones asignados (`salonesPermitidos()`).
- Los botones Confirmar/Rechazar del dashboard **reusan las rutas ya existentes**
  (`admin.reservaciones.confirmar`/`rechazar`, AN-4) — no duplica lógica, solo agrega otro punto
  de entrada a la misma acción, protegido por `@can('cambiarEstado', ...)`.
- `saldo_pendiente` es un accessor (no columna) — se suma sobre la colección ya cargada, no en
  SQL; volumen de reservaciones activas de un negocio no justifica una query agregada más
  compleja.
- "Productos con stock bajo" cuenta productos DISTINTOS (no filas de inventario) — un producto
  con 2 almacenes bajos no debe contar doble.

## Datos demo (confirmado con el usuario)

Se encontró basura de pruebas anteriores en el negocio demo "Salones La Paz" (un salón con
nombre Faker "Salón eaque eaque" + su reservación dependiente, un paquete "XV de Prueba") —
confirmado con el usuario antes de borrar. Se cargaron 6 reservaciones demo realistas (próximos
eventos confirmados + solicitudes pendientes de origen portal_publico) + 2 productos con stock
bajo, para que el dashboard se vea poblado al abrirlo, no vacío.

## Verificado

- 4 tests nuevos (`DashboardControllerTest`): conteos correctos de las 4 tarjetas, próximos
  eventos excluye canceladas y pasadas (solo las 5 más cercanas), confirmar una solicitud desde
  el dashboard funciona de punta a punta (reusa la ruta real), Admin Salón solo ve sus propios
  salones en el resumen. Suite completa: **130/130 passed**.
- Manual, contra Apache (`:8080`) + MySQL reales: comparado visualmente contra el mockup del
  usuario — 4 tarjetas con los colores correctos (ámbar en pendientes, rojo en stock bajo),
  nombres reales de clientes en ambos listados, botones Confirmar/Rechazar funcionando.

## Commiteado y pusheado

A `main` en https://github.com/clarosgerd/renta-salon.
