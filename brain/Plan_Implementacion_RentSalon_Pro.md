# Plan de Implementación — RentSalon Pro
### Sistema de Gestión de Salones de Eventos (Laravel + Blade)

---

## 1. Resumen ejecutivo

**RentSalon Pro** es un sistema web que centraliza la operación de uno o varios salones de eventos: disponibilidad, paquetes, reservaciones, cobros y venta de productos adicionales. Se construye en **Laravel** (backend + lógica de negocio) con **Blade** (vistas del panel administrativo y del portal público), bajo marca propia del cliente (sin depender de plataformas de terceros como Calendly, WhatsApp Business o hojas de cálculo).

**Objetivo del sistema:** eliminar el doble-booking, dar autoservicio de consulta de disponibilidad al cliente final, estandarizar cotizaciones con paquetes predefinidos, y llevar control financiero exacto de abonos/saldos por evento, incluyendo venta de productos con descuento automático de inventario.

---

## 2. Arquitectura general

### 2.1 Stack tecnológico propuesto

| Capa | Tecnología |
|---|---|
| Backend | Laravel 11.x (PHP 8.3) |
| Vistas | Blade + Alpine.js (interactividad ligera sin SPA) |
| Estilos | Tailwind CSS |
| Base de datos | MySQL 8 / MariaDB |
| Calendario | FullCalendar.js (integrado vía Blade + API JSON interna) |
| Autenticación | Laravel Breeze (Blade stack) |
| Roles y permisos | spatie/laravel-permission |
| Generación de PDF (recibos, estados de cuenta) | barryvdh/laravel-dompdf |
| Notificaciones (correo/WhatsApp opcional) | Laravel Notifications + Mailables; WhatsApp vía API (Twilio o Meta Cloud API) como fase opcional |
| Subida de imágenes de salones/productos | Spatie Media Library o disco `public` con Intervention Image |
| Multi-salón / multi-sede | Un solo dominio, filtrado por `salon_id` en cada módulo (no requiere multi-tenant complejo) |

### 2.2 Estructura de la aplicación

- **Portal público** (`/`, `/salones`, `/paquetes`, `/disponibilidad`): sin login, solo lectura + formulario de solicitud de reserva.
- **Panel administrativo** (`/admin/*`): protegido por autenticación y roles, donde se gestionan salones, paquetes, reservaciones, calendario, pagos, POS, productos, inventario y usuarios.

---

## 3. Modelo de datos (entidades principales)

```
usuarios
├── id, name, email, password, role (super_admin, admin_salon, cajero), activo

salones
├── id, nombre, descripcion, capacidad_min, capacidad_max, ubicacion,
│   fotos (relación), activo

paquetes
├── id, salon_id (nullable si aplica a todos), nombre, tipo_evento (boda, xv, corporativo, otro),
│   descripcion, precio_base, servicios_incluidos (JSON o tabla pivote),
│   duracion_horas, activo

reservaciones
├── id, folio, salon_id, paquete_id, cliente_nombre, cliente_telefono, cliente_email,
│   fecha_evento, hora_inicio, hora_fin, num_invitados,
│   precio_total, estado (pendiente, confirmada, cancelada, finalizada),
│   creado_por (usuario_id), notas

pagos_abonos
├── id, reservacion_id, monto, fecha_pago, metodo_pago (efectivo, transferencia, tarjeta),
│   referencia, registrado_por (usuario_id), notas

productos
├── id, nombre, categoria (mobiliario, decoracion, bebidas, alimentos, otro),
│   precio_venta, costo, unidad_medida, activo

inventario
├── id, producto_id, salon_id (o almacén general), stock_actual, stock_minimo,
│   ultima_actualizacion

movimientos_inventario
├── id, producto_id, tipo (entrada, salida_venta, ajuste), cantidad,
│   referencia_venta_id (nullable), fecha, usuario_id

ventas_pos
├── id, folio, reservacion_id (nullable, puede ser venta independiente),
│   fecha, total, metodo_pago, cajero_id, estado (pagada, cancelada)

detalle_venta
├── id, venta_id, producto_id, cantidad, precio_unitario, subtotal
```

### 3.1 Relaciones clave

- `Salon` 1—N `Paquete`, 1—N `Reservacion`
- `Paquete` 1—N `Reservacion`
- `Reservacion` 1—N `PagoAbono`, 1—N `VentaPos` (opcional)
- `Producto` 1—1 `Inventario` (por salón o almacén general), 1—N `MovimientoInventario`
- `VentaPos` 1—N `DetalleVenta` → cada detalle descuenta `Inventario` vía evento/observer

### 3.2 Regla crítica de disponibilidad (anti doble-booking)

Antes de confirmar una reservación, validar en `ReservacionService`:

```php
Reservacion::where('salon_id', $salonId)
    ->where('estado', '!=', 'cancelada')
    ->where('fecha_evento', $fecha)
    ->where(function ($q) use ($horaInicio, $horaFin) {
        $q->whereBetween('hora_inicio', [$horaInicio, $horaFin])
          ->orWhereBetween('hora_fin', [$horaInicio, $horaFin])
          ->orWhere(function ($q2) use ($horaInicio, $horaFin) {
              $q2->where('hora_inicio', '<=', $horaInicio)
                 ->where('hora_fin', '>=', $horaFin);
          });
    })
    ->exists();
```

Esta validación se ejecuta tanto en la solicitud pública como en el alta manual del admin, y se refuerza con un **lock a nivel de base de datos** (transacción + `lockForUpdate`) para evitar condiciones de carrera si dos solicitudes llegan simultáneamente.

---

## 4. Módulos del sistema (detalle funcional)

### 4.1 Salones
- CRUD de espacios: nombre, capacidad, fotos, descripción, servicios base.
- Activar/desactivar salón (oculta del portal público sin borrar historial).
- Vista de "ficha pública" por salón con galería y paquetes asociados.

### 4.2 Paquetes
- CRUD de paquetes por tipo de evento (boda, XV años, corporativo, otro).
- Asociar paquete a uno o varios salones, o dejarlo genérico.
- Lista de servicios incluidos (mobiliario, mantelería, coordinador, horas de renta, etc.) como ítems configurables.
- Precio base + posibilidad de cargos extra al momento de cotizar.

### 4.3 Reservaciones
- Alta manual desde el panel (con validación de disponibilidad).
- Solicitud pública: formulario en portal → llega como reservación en estado **pendiente** para que el admin confirme (evita que el cliente se autoconfirme sin validación humana, pero sí "bloquea" la fecha temporalmente).
- Cambios de estado: pendiente → confirmada → finalizada / cancelada.
- Ficha de reservación con: datos del cliente, paquete, salón, fecha/hora, total, abonos, saldo pendiente, historial de pagos, ventas POS asociadas.
- Generación de folio único y contrato/cotización en PDF.

### 4.4 Calendario
- Vista mensual/semanal con FullCalendar, coloreado por estado (pendiente = amarillo, confirmada = verde, cancelada = gris).
- Filtro por salón (si hay varios espacios).
- Click en evento → abre ficha de la reservación.
- Vista pública de "disponibilidad" simplificada (solo muestra ocupado/libre, sin datos del cliente).

### 4.5 Pagos y abonos
- Registrar abonos parciales o pago total contra una reservación.
- Cálculo automático de saldo: `precio_total - SUM(pagos_abonos.monto)`.
- Métodos de pago configurables (efectivo, transferencia, tarjeta).
- Recibo de pago en PDF descargable/imprimible por cada abono.

### 4.6 Estado de cuenta por renta
- Vista consolidada por reservación: total, abonado, pendiente, próxima fecha límite de pago (si se define política de anticipos).
- Exportable a PDF para entregar al cliente.

### 4.7 Historial de pagos
- Bitácora cronológica de todos los movimientos de una reservación (quién registró, cuándo, cuánto, método).
- Filtro global de pagos por rango de fechas / salón / usuario, útil para corte de caja.

### 4.8 Punto de venta (POS)
- Interfaz rápida tipo "carrito" para agregar productos a una venta.
- Vinculación opcional a una reservación activa (para cobrar extras el día del evento) o venta independiente (mostrador).
- Descuento automático de inventario al confirmar la venta (vía Observer/Event en `DetalleVenta`).
- Corte de caja por turno/usuario/día.

### 4.9 Productos
- Catálogo de artículos vendibles (bebidas, decoración extra, alimentos, etc.), independiente del paquete de renta.
- Precio de venta, costo, categoría, imagen.

### 4.10 Inventario
- Stock actual por producto (y por salón si el negocio maneja almacenes separados).
- Alertas de stock mínimo.
- Movimientos de entrada (compras/reabasto) y salida (ventas, mermas, ajustes).
- Reporte de existencias en tiempo real.

### 4.11 Usuarios
- Roles: **Super Admin** (control total, multi-salón), **Admin de Salón** (gestiona su(s) salón(es) asignados), **Cajero** (solo POS y registro de pagos).
- Permisos granulares vía `spatie/laravel-permission` (ej. `reservaciones.crear`, `pagos.registrar`, `inventario.ajustar`).
- Log de auditoría básico (quién hizo qué y cuándo) usando `spatie/laravel-activitylog` (opcional, recomendado).

---

## 5. Flujo de uso end-to-end

**Cliente (portal público):**
1. Entra al sitio, explora salones y paquetes.
2. Consulta el calendario de disponibilidad de un salón.
3. Llena formulario de solicitud (fecha, tipo de evento, datos de contacto) → se crea reservación en estado *pendiente*, la fecha queda bloqueada temporalmente.

**Administrador (panel):**
4. Recibe notificación (correo/panel) de nueva solicitud.
5. Contacta al cliente, ajusta detalles/precio si aplica, y **confirma** la reservación.
6. Registra abonos conforme el cliente va pagando.
7. El día del evento, usa el POS para cobrar extras (bebidas, servicios adicionales); el inventario se descuenta solo.
8. Al finalizar, marca la reservación como *finalizada*; el sistema conserva historial completo de pagos y ventas.

---

## 6. Estructura de carpetas Laravel (resumen)

```
app/
 ├── Models/ (Salon, Paquete, Reservacion, PagoAbono, Producto, Inventario, VentaPos, DetalleVenta, User)
 ├── Http/Controllers/
 │    ├── Public/ (SalonPublicController, DisponibilidadController, SolicitudReservaController)
 │    └── Admin/  (SalonController, PaqueteController, ReservacionController, CalendarioController,
 │                  PagoController, PosController, ProductoController, InventarioController, UsuarioController)
 ├── Services/ (DisponibilidadService, ReservacionService, PagoService, VentaService)
 ├── Observers/ (DetalleVentaObserver → descuenta inventario)
 ├── Policies/ (por cada modelo, ligado a roles)
 └── Notifications/ (NuevaSolicitudReserva, AbonoRegistrado)

resources/views/
 ├── public/ (layout propio de marca, salones, paquetes, disponibilidad, formulario)
 └── admin/  (layout con sidebar, uno por módulo)

routes/
 ├── web.php        (portal público)
 └── admin.php      (panel, prefijo /admin, middleware auth + role)
```

---

## 7. Fases de desarrollo sugeridas

| Fase | Contenido | Estimado |
|---|---|---|
| **0. Setup** | Instalación Laravel, Breeze, Tailwind, roles con Spatie, estructura base de BD (migraciones) | 3–4 días |
| **1. Núcleo operativo** | Módulos Salones, Paquetes, Reservaciones (CRUD + validación de disponibilidad) | 1–1.5 semanas |
| **2. Calendario** | Integración FullCalendar + vista pública de disponibilidad | 3–5 días |
| **3. Finanzas** | Pagos/abonos, estado de cuenta, historial, PDFs de recibo | 1 semana |
| **4. Portal público** | Landing de marca, ficha de salones/paquetes, formulario de solicitud | 4–6 días |
| **5. POS + Productos + Inventario** | Catálogo, ventas, descuento automático de stock, corte de caja | 1–1.5 semanas |
| **6. Usuarios y permisos** | Roles, asignación de salones por usuario, auditoría | 3–4 días |
| **7. Pulido y despliegue** | Notificaciones por correo, pruebas, hosting, dominio propio, capacitación | 4–6 días |

**Duración total estimada:** ~6 a 8 semanas con un desarrollador full-time (ajustable según alcance de WhatsApp/notificaciones y número de salones a soportar desde el día uno).

---

## 8. Consideraciones adicionales a decidir contigo

- ¿El sistema debe soportar **múltiples negocios/marcas** en la misma instalación (multi-tenant real) o es **una sola marca con varios salones**? (el plan anterior asume la segunda opción, más simple y económica).
- ¿Se requiere pasarela de pagos en línea (Stripe/Conekta) para que el cliente abone su anticipo desde el portal, o los pagos siempre se registran manualmente por el admin?
- ¿Notificaciones por WhatsApp son indispensables desde el lanzamiento, o pueden ir en una segunda fase?
- ¿Cuántos salones/espacios maneja el negocio actualmente, para dimensionar la complejidad del calendario y el inventario compartido vs. por salón?

Estas respuestas afectan directamente el alcance de las Fases 5 y 7, así que conviene definirlas antes de iniciar el desarrollo.
