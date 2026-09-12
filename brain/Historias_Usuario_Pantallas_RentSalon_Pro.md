# RentSalon Pro — Historias de Usuario y Pantallas del Panel Administrativo

---

## 1. Historias de usuario por rol

Formato: **Como** [rol], **quiero** [acción], **para** [beneficio].

### 1.1 Super Admin Plataforma

| # | Historia de usuario | Criterio de aceptación |
|---|---|---|
| SA-1 | Como Super Admin, quiero dar de alta un nuevo negocio con su subdominio y datos de marca, para que empiece a operar de inmediato. | Al guardar, el negocio queda activo y accesible en `subdominio.rentsalonpro.com` sin pasos adicionales. |
| SA-2 | Como Super Admin, quiero suspender el acceso de un negocio, para bloquearlo si incumple el pago de su suscripción. | Al suspender, el portal público y el panel de ese negocio muestran un aviso de "servicio suspendido" en lugar del contenido normal. |
| SA-3 | Como Super Admin, quiero ver un listado de todos los negocios con su estado y métricas básicas (# salones, # reservaciones del mes), para monitorear la salud general de la plataforma. | La tabla es filtrable por estado (activo/suspendido) y ordenable por fecha de alta. |
| SA-4 | Como Super Admin, quiero configurar el banco QR y las credenciales de cobro de cada negocio, para habilitar sus pagos QR sin que ellos vean las credenciales sensibles de otros negocios. | Las credenciales se guardan cifradas y solo son editables desde este panel central. |
| SA-5 | Como Super Admin, quiero entrar como "impersonar" a un negocio específico, para dar soporte técnico sin pedirle sus credenciales. | Acción registrada en el log de auditoría con mi usuario real + el negocio impersonado. |

### 1.2 Admin Negocio

| # | Historia de usuario | Criterio de aceptación |
|---|---|---|
| AN-1 | Como Admin Negocio, quiero dar de alta mis salones con fotos, capacidad y descripción, para que aparezcan en mi portal público. | El salón creado aparece inmediatamente en `/salones` de mi subdominio si está marcado como "activo". |
| AN-2 | Como Admin Negocio, quiero crear paquetes por tipo de evento (boda, XV años, corporativo, otro) con precio y servicios incluidos, para cotizar de forma estandarizada. | Un paquete puede asociarse a uno, varios o todos mis salones. |
| AN-3 | Como Admin Negocio, quiero ver el calendario maestro de todos mis salones en una sola vista, para detectar disponibilidad de un vistazo. | El calendario permite filtrar por salón y por estado de reservación (pendiente/confirmada/cancelada). |
| AN-4 | Como Admin Negocio, quiero confirmar o rechazar solicitudes de reserva que llegan desde el portal público, para controlar qué eventos se agendan realmente. | Al confirmar, la fecha queda bloqueada para cualquier otro salón/paquete que se solape; al rechazar, la fecha se libera. |
| AN-5 | Como Admin Negocio, quiero ver el estado de cuenta de cualquier reservación (total, abonado, saldo), para saber cuánto falta cobrar. | El estado de cuenta se actualiza en tiempo real con cada abono registrado. |
| AN-6 | Como Admin Negocio, quiero crear usuarios (Admin Salón, Cajero) y asignarles salones específicos, para delegar la operación sin dar acceso total. | Un Admin Salón solo ve/edita los salones que le asigné; un Cajero solo ve POS y pagos. |
| AN-7 | Como Admin Negocio, quiero ver reportes de ventas POS e inventario de todos mis salones, para tomar decisiones de reabasto. | El reporte es filtrable por rango de fechas y por salón. |
| AN-8 | Como Admin Negocio, quiero personalizar el logo y color de mi portal público, para que se vea con mi marca y no la de RentSalon Pro. | El cambio se refleja de inmediato en el portal público sin necesidad de soporte técnico. |

### 1.3 Admin Salón

| # | Historia de usuario | Criterio de aceptación |
|---|---|---|
| AS-1 | Como Admin Salón, quiero ver solo el calendario y las reservaciones de los salones que me asignaron, para enfocarme en mi operación sin ver otros salones del negocio. | Al iniciar sesión, el menú y los listados están automáticamente filtrados a mis salones asignados. |
| AS-2 | Como Admin Salón, quiero registrar una reservación manual (ej. cliente que llamó por teléfono), para no depender del portal público. | El sistema valida disponibilidad antes de guardar y avisa si hay conflicto de fecha/hora. |
| AS-3 | Como Admin Salón, quiero registrar abonos de mis clientes, para mantener actualizado su saldo pendiente. | Cada abono queda con fecha, monto, método de pago y quién lo registró. |
| AS-4 | Como Admin Salón, quiero generar el PDF del estado de cuenta de una reservación, para entregárselo al cliente impreso o por correo. | El PDF incluye folio, datos del cliente, paquete, total, abonos y saldo pendiente. |

### 1.4 Cajero

| # | Historia de usuario | Criterio de aceptación |
|---|---|---|
| CJ-1 | Como Cajero, quiero abrir el POS y agregar productos a una venta con pocos clics, para cobrar rápido el día del evento. | La búsqueda de producto es por nombre o categoría, con teclado o lector de código si aplica. |
| CJ-2 | Como Cajero, quiero vincular una venta POS a una reservación activa, para que quede registrada como consumo extra de ese evento. | La venta aparece en el historial de esa reservación específica. |
| CJ-3 | Como Cajero, quiero cobrar en efectivo o generar un QR de monto fijo, para dar ambas opciones de pago al cliente. | Al elegir QR, el sistema muestra el código en pantalla y espera confirmación antes de cerrar la venta. |
| CJ-4 | Como Cajero, quiero ver mi corte de caja del día (ventas y pagos que yo registré), para hacer el cierre de turno. | El corte diferencia efectivo vs. QR y muestra el total exacto a entregar/reportar. |

### 1.5 Cliente final (portal público, sin login)

| # | Historia de usuario | Criterio de aceptación |
|---|---|---|
| CL-1 | Como cliente, quiero explorar los salones disponibles con fotos y capacidad, para elegir el que me conviene. | La ficha del salón muestra galería, capacidad y paquetes asociados. |
| CL-2 | Como cliente, quiero consultar el calendario de disponibilidad de un salón antes de llamar, para no perder tiempo si la fecha ya está ocupada. | El calendario público solo muestra "disponible/ocupado", sin datos de otros clientes. |
| CL-3 | Como cliente, quiero llenar un formulario de solicitud de reserva con mis datos y la fecha deseada, para iniciar el proceso sin necesidad de llamar. | Al enviar, recibo confirmación en pantalla de que mi solicitud quedó registrada como "pendiente de confirmación". |

---

## 2. Mapa de navegación del panel administrativo

```
/admin
 ├── Dashboard                         (resumen: próximos eventos, saldos pendientes, alertas de stock)
 ├── Calendario                        (vista maestra, filtrable por salón)
 ├── Reservaciones
 │    ├── Listado
 │    ├── Nueva reservación
 │    └── Ficha de reservación (detalle + pagos + ventas asociadas)
 ├── Salones
 │    ├── Listado
 │    └── Formulario (crear/editar)
 ├── Paquetes
 │    ├── Listado
 │    └── Formulario (crear/editar)
 ├── Pagos y Abonos
 │    ├── Listado global (filtrable por salón/fecha/método)
 │    └── Registrar pago (modal desde ficha de reservación)
 ├── Punto de Venta (POS)
 │    ├── Pantalla de venta
 │    └── Corte de caja
 ├── Productos
 │    ├── Listado
 │    └── Formulario (crear/editar)
 ├── Inventario
 │    ├── Existencias actuales
 │    └── Movimientos (entradas/salidas/ajustes)
 ├── Usuarios
 │    ├── Listado
 │    └── Formulario (crear/editar + asignación de salones)
 └── Configuración
      ├── Datos de marca (logo, color, subdominio)
      └── Cobros (banco QR, credenciales)

/plataforma   (solo Super Admin Plataforma, fuera del contexto de negocio)
 ├── Negocios
 │    ├── Listado
 │    └── Formulario (alta/edición/suspensión)
 └── Métricas globales
```

---

## 3. Especificación de pantallas clave

### 3.1 Dashboard (`/admin`)
**Objetivo:** que el admin vea en 5 segundos si hay algo urgente.
- Tarjetas superiores: eventos de hoy/esta semana, solicitudes pendientes de confirmar, saldo total por cobrar, productos con stock bajo.
- Lista corta de "Próximos eventos" (5 más cercanos) con acceso directo a su ficha.
- Lista corta de "Solicitudes nuevas" (desde portal público) con botones rápidos Confirmar/Rechazar.
- Gráfico simple de ingresos del mes (abonos confirmados + ventas POS).

### 3.2 Calendario maestro (`/admin/calendario`)
- Vista mensual por defecto (FullCalendar), con toggle a vista semanal.
- Selector de salón(es) a mostrar (multi-select; "todos" por defecto si el rol tiene acceso a varios).
- Colores por estado: amarillo = pendiente, verde = confirmada, gris = cancelada.
- Click en un evento abre un panel lateral (no navega fuera del calendario) con resumen: cliente, paquete, salón, saldo — con botón "Ver ficha completa".
- Botón "+ Nueva reservación" que abre el formulario con la fecha pre-cargada si se hizo clic en un día vacío.

### 3.3 Listado de reservaciones (`/admin/reservaciones`)
- Tabla con columnas: Folio, Cliente, Salón, Fecha evento, Paquete, Total, Saldo pendiente, Estado.
- Filtros: por estado, por salón, por rango de fecha, por búsqueda de nombre/folio.
- Etiqueta visual de "Saldo pendiente" en rojo si el evento es en menos de X días y aún no está liquidado (regla configurable).
- Acción rápida por fila: Ver, Registrar abono, Cambiar estado.

### 3.4 Ficha de reservación (`/admin/reservaciones/{id}`)
Pantalla más importante del sistema — reúne toda la operación de un evento:
- **Encabezado:** folio, estado (con botón de cambio de estado), salón, paquete, fecha/hora.
- **Pestaña Datos del cliente:** nombre, teléfono, email, número de invitados, notas.
- **Pestaña Estado de cuenta:** total del paquete + cargos extra, tabla de abonos (fecha, monto, método, quién registró), saldo restante calculado, botón "Registrar abono" y "Descargar estado de cuenta (PDF)".
- **Pestaña Ventas POS asociadas:** ventas de productos vinculadas a este evento (ej. cobros del día del evento).
- **Pestaña Historial:** bitácora de cambios (creación, confirmaciones, cancelaciones, quién y cuándo).

### 3.5 Registrar abono (modal/pantalla)
- Campos: monto, método de pago (Efectivo / QR), fecha, notas.
- Si elige **QR**: el sistema genera el código con el monto ya fijado (no editable), lo muestra en pantalla grande para que el cliente escanee, y un indicador de estado ("Esperando confirmación...") que se actualiza solo. Botón "Confirmar manualmente" visible como respaldo.
- Si elige **Efectivo**: se guarda y confirma de inmediato.
- Al guardar, la ficha de reservación refleja el nuevo saldo sin recargar la página.

### 3.6 Salones — listado y formulario
- Listado: tarjetas o tabla con foto miniatura, nombre, capacidad, estado (activo/inactivo), botón editar.
- Formulario: nombre, descripción, capacidad mínima/máxima, ubicación, galería de fotos (subida múltiple con reordenamiento), toggle activo/inactivo (oculta del portal público sin borrar historial).

### 3.7 Paquetes — listado y formulario
- Listado: nombre, tipo de evento, precio base, salones asociados, estado.
- Formulario: nombre, tipo de evento (select: boda/XV años/corporativo/otro), descripción, precio base, duración en horas, lista dinámica de "servicios incluidos" (agregar/quitar ítems tipo chip), selector de salones donde aplica (o "todos").

### 3.8 Punto de Venta — POS (`/admin/pos`)
- Panel izquierdo: buscador de productos + grid de categorías para agregar rápido al carrito.
- Panel derecho: carrito con cantidad editable, subtotal, total.
- Selector opcional: "Vincular a reservación" (buscador de folio/cliente) — si se deja vacío, es venta de mostrador.
- Botones de cobro: Efectivo (confirma directo) / QR (muestra el código, espera confirmación).
- Al confirmar la venta, se genera el ticket/recibo y se descuenta el inventario automáticamente (sin pantalla adicional para el cajero).

### 3.9 Corte de caja (`/admin/pos/corte`)
- Filtro por turno/fecha/cajero.
- Totales separados: efectivo recibido, QR confirmado, QR aún pendiente de confirmar (alerta si hay pendientes al cerrar turno).
- Botón "Cerrar turno" que bloquea nuevas ventas bajo ese corte (requiere nuevo turno para seguir vendiendo).

### 3.10 Productos e Inventario
- **Productos:** listado con nombre, categoría, precio, costo, stock actual (columna calculada desde Inventario); formulario simple de alta/edición.
- **Inventario — Existencias:** tabla por producto y salón (si aplica) con stock actual, stock mínimo, indicador visual si está por debajo del mínimo.
- **Inventario — Movimientos:** listado cronológico de entradas/salidas/ajustes, con formulario para registrar entradas manuales (reabasto) — las salidas por venta se generan solas.

### 3.11 Usuarios (`/admin/usuarios`)
- Listado: nombre, email, rol, salones asignados (chips), estado (activo/inactivo).
- Formulario: datos básicos, selector de rol (Admin Negocio / Admin Salón / Cajero — Super Admin Plataforma no se crea desde aquí), y si el rol es Admin Salón o Cajero, un multi-select de "salones asignados".

### 3.12 Configuración — Datos de marca
- Logo (subida de imagen), color primario (selector de color), nombre comercial, subdominio (solo lectura si ya está en uso, editable dominio personalizado), teléfono/email de contacto público.
- Vista previa en vivo de cómo se ve el portal público con esos cambios antes de guardar.

### 3.13 Configuración — Cobros (QR)
- Selector de banco (Unión / BNB / BCP).
- Campos de credenciales según el banco elegido (número de comercio, llaves API) — guardados cifrados, nunca mostrados en texto plano después de guardar (solo opción "Reemplazar").
- Botón "Probar conexión" que intenta generar un QR de prueba de Bs. 1 y confirma si la integración responde.

### 3.14 Portal público — pantallas del cliente
- **Home del negocio:** hero con nombre/marca, botones "Ver salones" y "Ver paquetes".
- **Listado de salones:** tarjetas con foto, nombre, capacidad, botón "Ver disponibilidad".
- **Ficha de salón:** galería, descripción, paquetes asociados, calendario público de disponibilidad (solo ocupado/libre).
- **Ficha de paquete:** descripción, precio, servicios incluidos, botón "Solicitar esta fecha" (abre formulario).
- **Formulario de solicitud:** nombre, teléfono, email, fecha deseada, tipo de evento, número de invitados, notas → botón enviar → pantalla de confirmación ("Tu solicitud fue recibida, te contactaremos para confirmar").

---

## 4. Siguiente paso sugerido

Con las historias de usuario y pantallas definidas, los siguientes pasos naturales son:
1. **Wireframes visuales** de las pantallas más críticas (Dashboard, Ficha de reservación, POS) para validar layout antes de programar.
2. **Esquema SQL/migraciones completas** de Laravel basadas en el modelo de datos ya definido.
3. Definir el **backlog priorizado** (qué historias entran en cada fase del roadmap ya propuesto).

¿Con cuál de estas tres quieres continuar?
