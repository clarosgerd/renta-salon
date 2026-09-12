# Plan de Implementación — RentSalon Pro (v2)
### Sistema Multi-Negocio de Gestión de Salones de Eventos (Laravel + Blade)

> Versión actualizada con: (1) arquitectura multi-tenant para varios negocios/marcas, (2) pagos por QR y efectivo, (3) WhatsApp movido a Fase 2, (4) diseño escalable más allá de 5 salones.

---

## 1. Resumen ejecutivo

RentSalon Pro pasa de ser un sistema "para un negocio" a una **plataforma SaaS multi-negocio**: cada cliente (negocio de salones de eventos) tiene su propio espacio aislado — sus salones, paquetes, reservaciones, pagos, inventario y usuarios — bajo su propia marca (logo, colores, nombre comercial, y opcionalmente su propio subdominio o dominio). Un **Super Admin de plataforma** ve y administra todos los negocios; cada negocio ve únicamente su propia información.

---

## 2. Decisión de arquitectura: Multi-tenancy

### 2.1 Opciones evaluadas

| Enfoque | Descripción | Pros | Contras |
|---|---|---|---|
| **A. Base de datos por negocio** (multi-tenant "duro", ej. paquete `stancl/tenancy` en modo multi-DB) | Cada negocio tiene su propia base de datos física | Aislamiento total de datos, backups independientes | Más caro de hospedar, migraciones más lentas al escalar a muchos negocios |
| **B. Base de datos única + `negocio_id`** (multi-tenant "compartido", recomendado) | Una sola base de datos; cada tabla relevante tiene `negocio_id`; un Global Scope de Laravel filtra automáticamente por el negocio activo | Más económico, fácil de mantener, migraciones únicas para todos, fácil de escalar a más salones/negocios | Requiere disciplina en el código para nunca olvidar el filtro (se mitiga con Global Scopes) |

**Recomendación: Opción B** — base de datos única con `negocio_id` + Global Scopes de Eloquent. Es el estándar para SaaS de este tamaño (costo/beneficio óptimo) y permite crecer de 5 a 50+ salones sin rediseñar nada, solo agregando registros.

### 2.2 Identificación del negocio (tenant resolution)

Cada negocio se identifica por **subdominio** (ej. `saloneslapaz.rentsalonpro.com`) o por **dominio propio** (ej. `www.saloneselegantes.com` apuntando via CNAME), a elección del cliente:

```
Middleware: IdentificarNegocio
1. Lee el host de la petición (request()->getHost())
2. Busca en tabla `negocios` por subdominio o dominio_personalizado
3. Si existe → guarda el negocio activo en el contenedor de servicios (app()->instance('negocio_actual', $negocio))
4. Si no existe → 404 o redirige al landing de la plataforma (rentsalonpro.com, página de ventas del SaaS)
```

Todos los modelos relevantes (`Salon`, `Paquete`, `Reservacion`, `Producto`, `Inventario`, `Usuario`, etc.) usan un **Global Scope** (`NegocioScope`) que agrega automáticamente `WHERE negocio_id = ?` a cada consulta, y un **Trait** (`BelongsToNegocio`) que autoasigna el `negocio_id` al crear registros. Así, el 95% del código de módulos (Salones, Paquetes, Reservaciones, POS, etc.) se escribe exactamente igual que en el plan original — el aislamiento ocurre de forma transparente.

### 2.3 Portal público por negocio

Cada negocio tiene su propio portal público bajo su subdominio/dominio, con su logo, colores y textos (tabla `negocios` incluye campos de "marca": `logo`, `color_primario`, `nombre_comercial`, `telefono_contacto`, etc., inyectados a la vista vía un `View Composer` global).

### 2.4 Plataforma central (Super Admin)

Un panel aparte (`admin.rentsalonpro.com` o `/plataforma`) donde el **dueño del SaaS**:
- Da de alta nuevos negocios (nombre, subdominio, plan, datos del dueño).
- Activa/suspende negocios (ej. por falta de pago de la suscripción del SaaS, si aplica ese modelo de negocio).
- Ve métricas globales (cuántos negocios activos, reservaciones totales, etc.).

> Nota: si el modelo de negocio del SaaS incluye cobro de suscripción mensual a cada negocio, se puede añadir un módulo de "Planes y Suscripciones" en una fase posterior (no incluido en el alcance actual salvo que lo confirmes).

---

## 3. Modelo de datos actualizado

```
negocios                         ← NUEVA tabla raíz del multi-tenant
├── id, nombre_comercial, subdominio, dominio_personalizado (nullable),
│   logo, color_primario, telefono_contacto, email_contacto,
│   estado (activo, suspendido), plan (opcional), creado_en

usuarios
├── id, negocio_id (nullable si es super_admin de plataforma),
│   name, email, password, role (super_admin_plataforma, admin_negocio, admin_salon, cajero), activo

salones
├── id, negocio_id, nombre, descripcion, capacidad_min, capacidad_max,
│   ubicacion, fotos (relación), activo

paquetes
├── id, negocio_id, salon_id (nullable), nombre, tipo_evento, descripcion,
│   precio_base, servicios_incluidos, duracion_horas, activo

reservaciones
├── id, negocio_id, salon_id, paquete_id, cliente_nombre, cliente_telefono, cliente_email,
│   fecha_evento, hora_inicio, hora_fin, num_invitados, precio_total,
│   estado (pendiente, confirmada, cancelada, finalizada), creado_por, notas

pagos_abonos
├── id, negocio_id, reservacion_id, monto, fecha_pago,
│   metodo_pago (efectivo, qr), estado_pago (pendiente, confirmado, rechazado),
│   referencia_transaccion (nullable, para QR), comprobante_url (nullable),
│   registrado_por, notas

pagos_qr                         ← NUEVA tabla, detalle de transacciones QR
├── id, pago_abono_id, proveedor_qr (banco/pasarela usada), qr_id_externo,
│   monto, moneda, estado (generado, pagado, expirado, fallido),
│   fecha_generacion, fecha_confirmacion, payload_respuesta (JSON)

productos
├── id, negocio_id, nombre, categoria, precio_venta, costo, unidad_medida, activo

inventario
├── id, negocio_id, producto_id, salon_id (nullable, o almacén general del negocio),
│   stock_actual, stock_minimo, ultima_actualizacion

movimientos_inventario
├── id, negocio_id, producto_id, tipo, cantidad, referencia_venta_id (nullable),
│   fecha, usuario_id

ventas_pos
├── id, negocio_id, folio, reservacion_id (nullable), fecha, total,
│   metodo_pago (efectivo, qr), cajero_id, estado (pagada, cancelada)

detalle_venta
├── id, venta_id, producto_id, cantidad, precio_unitario, subtotal
```

Todas las tablas con `negocio_id` (excepto `negocios` misma y `usuarios` para el super admin) quedan protegidas por el `NegocioScope` mencionado en la sección 2.2.

---

## 4. Módulo de pagos: efectivo + QR

### 4.1 Pago en efectivo
Sin cambios respecto al plan original: el admin/cajero registra manualmente el monto recibido contra la reservación o venta POS. Se marca como `confirmado` de inmediato.

### 4.2 Pago por QR

Dado que el pago QR en Bolivia normalmente se procesa a través de la banca local (ej. QR Simple interoperable de ASFI, o QR propio de bancos como Banco Unión, BCP, BNB, o billeteras como Tigo Money), se propone una **capa de abstracción** (`PagoQrGatewayInterface`) para no atar el sistema a un solo proveedor:

```php
interface PagoQrGatewayInterface {
    public function generarQr(float $monto, string $referencia): QrResponse;
    public function consultarEstado(string $qrIdExterno): string; // pagado, pendiente, expirado
}
```

**Flujo operativo:**
1. Admin/cajero selecciona "Pago por QR" en la ficha de reservación o en el POS.
2. Sistema llama a `generarQr()` → obtiene una imagen/código QR con el monto exacto.
3. Se muestra el QR en pantalla (o se imprime) para que el cliente escanee desde su app bancaria.
4. El sistema consulta periódicamente (`consultarEstado()`, vía cron cada 30–60 seg. o webhook si el proveedor lo soporta) hasta confirmar el pago.
5. Al confirmarse, el `pago_abono` pasa a estado `confirmado` automáticamente y se actualiza el saldo de la reservación.
6. Si no hay integración de webhook disponible con el banco elegido, se contempla una **confirmación manual de respaldo**: el cajero verifica el ingreso en su app bancaria y marca el pago como confirmado (con opción de adjuntar captura de comprobante como respaldo).

> **Punto abierto a definir contigo:** ¿qué banco o pasarela de pago QR van a usar? (Banco Unión, BCP, BNB, Tigo Money, u otro con API disponible). Esto determina si el flujo es 100% automático (con webhook/consulta de estado) o semi-manual (QR generado + confirmación manual del cajero). El diseño de arriba soporta ambos casos sin cambiar el resto del sistema.

---

## 5. Roles actualizados

| Rol | Alcance |
|---|---|
| **Super Admin Plataforma** | Ve y administra todos los negocios (alta, suspensión, soporte). No opera reservaciones directamente. |
| **Admin Negocio** | Control total dentro de su negocio: todos los salones, paquetes, usuarios de su negocio, reportes financieros globales del negocio. |
| **Admin Salón** | Gestiona uno o varios salones específicos dentro del negocio (asignados por el Admin Negocio). |
| **Cajero** | Acceso solo a POS y registro de pagos/abonos, sin ver reportes financieros completos. |

Permisos implementados con `spatie/laravel-permission`, y el Global Scope de negocio garantiza que ningún rol (excepto Super Admin Plataforma) pueda ver datos de otro negocio, aunque intente manipular IDs por URL.

---

## 6. Módulos del sistema (actualizados)

Los 9 módulos originales (Salones, Paquetes, Reservaciones, Calendario, Pagos y abonos, POS, Productos, Inventario, Usuarios) se mantienen igual en funcionalidad, ahora todos operando **dentro del contexto del negocio activo**. Se agrega:

### 6.1 Negocios *(nuevo, solo Super Admin Plataforma)*
- Alta/edición de negocios: nombre comercial, subdominio, dominio propio opcional, logo, colores de marca.
- Activar/suspender acceso de un negocio completo.
- Vista de resumen: número de salones, reservaciones activas, último pago registrado (si hay modelo de suscripción del SaaS).

### 6.2 Escalabilidad de salones
El sistema no tiene límite de salones por negocio en el diseño de base de datos ni en el código — los 5 salones actuales son solo datos iniciales. Agregar el salón #6, #20 o #50 no requiere cambios de esquema, solo dar de alta el registro. El calendario y el filtro de disponibilidad ya están diseñados para funcionar igual de bien con 1 o con 50 salones (con paginación/filtro en la interfaz si la lista crece mucho).

---

## 7. Fases de desarrollo actualizadas

| Fase | Contenido | Estimado |
|---|---|---|
| **0. Setup multi-tenant** | Laravel, Breeze, Tailwind, tabla `negocios`, middleware de identificación por subdominio, Global Scope `NegocioScope`, trait `BelongsToNegocio`, roles con Spatie | 1 semana |
| **1. Panel Super Admin Plataforma** | CRUD de negocios, activar/suspender, branding básico | 3–4 días |
| **2. Núcleo operativo por negocio** | Salones, Paquetes, Reservaciones (con validación de disponibilidad ya filtrada por negocio) | 1–1.5 semanas |
| **3. Calendario** | FullCalendar + vista pública de disponibilidad por negocio/salón | 3–5 días |
| **4. Finanzas — efectivo** | Pagos/abonos en efectivo, estado de cuenta, historial, PDFs de recibo | 1 semana |
| **5. Finanzas — QR** | Integración `PagoQrGatewayInterface`, generación de QR, confirmación (automática o manual según proveedor elegido) | 1–1.5 semanas *(depende del proveedor)* |
| **6. Portal público por negocio** | Landing con marca del negocio (logo/colores dinámicos), ficha de salones/paquetes, formulario de solicitud | 4–6 días |
| **7. POS + Productos + Inventario** | Catálogo, ventas con efectivo/QR, descuento automático de stock, corte de caja | 1–1.5 semanas |
| **8. Usuarios y permisos** | Roles (incluye Admin Negocio y Admin Salón), asignación de salones, auditoría | 3–4 días |
| **9. Pulido y despliegue** | Pruebas multi-negocio (verificar aislamiento de datos), hosting, dominios/subdominios, capacitación | 1 semana |

**Duración total estimada:** ~9 a 11 semanas con un desarrollador full-time. (WhatsApp queda fuera de este alcance, para Fase 2 posterior al lanzamiento).

---

## 8. Fase 2 (post-lanzamiento, no incluida en el alcance actual)

- Notificaciones automáticas por WhatsApp (Twilio o Meta Cloud API): confirmación de reservación, recordatorio de saldo pendiente, confirmación de pago QR recibido.
- Módulo de suscripciones del SaaS (si el modelo de negocio cobra mensualidad a cada negocio cliente).
- Reportes avanzados/dashboards comparativos entre salones o entre negocios (solo vista Super Admin Plataforma).

---

## 9. Punto pendiente de confirmar contigo

Para cerrar el diseño técnico del módulo de pagos QR (Fase 5), necesito que definas:

1. **¿Qué banco o pasarela de pago QR van a usar?** (Banco Unión, BCP, BNB, Tigo Money, u otra con API pública). Esto define si la confirmación de pago es automática (webhook/consulta de API) o semi-manual (QR generado + el cajero confirma al ver el depósito).
2. **¿El QR se genera con un monto fijo por transacción**, o necesitan que el cliente pueda pagar montos variables (ej. "abono libre") vía el mismo QR?

Con esas dos respuestas puedo detallar el diseño exacto de la integración de pagos antes de iniciar esa fase del desarrollo.
