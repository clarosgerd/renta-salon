# Esquema de base de datos — RentSalon Pro

Este paquete contiene las **migraciones de Laravel** y la **capa de aislamiento multi-negocio**
correspondientes al plan de implementación acordado.

## Contenido

```
database/migrations/    ← 16 migraciones, en orden correcto de dependencias
app/Scopes/NegocioScope.php          ← filtra automáticamente por negocio activo
app/Traits/BelongsToNegocio.php      ← úsalo en todos los modelos "propiedad de un negocio"
app/Http/Middleware/IdentificarNegocio.php ← resuelve el negocio por subdominio/dominio
```

## Orden de las migraciones

1. `negocios` — tabla raíz del multi-tenant
2. `negocio_config_pago` — banco QR y credenciales por negocio
3. Se agregan `negocio_id` y `role` a la tabla `users` de Breeze
4. `salones`
5. `usuario_salon` — pivote: qué salones administra un Admin Salón/Cajero
6. `imagenes` — tabla polimórfica reutilizable (galerías de salones y productos)
7. `paquetes`
8. `paquete_salon` — pivote: un paquete puede aplicar a 1, varios o todos los salones
9. `reservaciones`
10. `pagos_abonos`
11. `productos`
12. `inventario`
13. `ventas_pos`
14. `detalle_venta`
15. `movimientos_inventario`
16. `pagos_qr` — detalle de transacciones QR (Unión / BNB / BCP)

## Cómo instalarlo en tu proyecto Laravel

1. Copia el contenido de `database/migrations/` a tu carpeta `database/migrations/` de Laravel
   (o renómbralas con las fechas reales del día en que las vayas a correr, para no chocar con
   las migraciones por defecto de Breeze/Laravel).
2. Copia `app/Scopes`, `app/Traits` y `app/Http/Middleware` a las carpetas equivalentes de tu proyecto.
3. Instala dependencias necesarias:
   ```
   composer require laravel/breeze spatie/laravel-permission barryvdh/laravel-dompdf
   ```
4. Registra el middleware `IdentificarNegocio` en `bootstrap/app.php` (Laravel 11) para el grupo
   de rutas del portal público y del panel `/admin` (NO en las rutas de `/plataforma`, que debe
   ver todos los negocios).
5. En cada modelo "propiedad de un negocio" (`Salon`, `Paquete`, `Reservacion`, `Producto`,
   `Inventario`, `VentaPos`, etc.) agrega `use BelongsToNegocio;` — así heredan el filtro y la
   auto-asignación de `negocio_id` sin escribir código repetido.
6. Corre las migraciones:
   ```
   php artisan migrate
   ```

## Notas de diseño importantes

- **`fecha_evento` + `hora_inicio` + `hora_fin` + `estado`** en `reservaciones` están indexados
  juntos porque es la consulta que se ejecuta en cada validación de disponibilidad (ver
  `ReservacionService::validarDisponibilidad()` descrito en el plan de arquitectura).
- **`paquete_salon`** es una tabla pivote: si un paquete no tiene filas ahí, la aplicación lo
  interpreta como "aplica a todos los salones del negocio" (regla de negocio, no de base de datos).
- **`inventario.salon_id` es nullable**: si el negocio maneja un almacén general compartido entre
  salones, se deja en `null`; si maneja stock independiente por salón, se llena.
- **`pagos_qr`** se relaciona opcionalmente con `pagos_abonos` O con `ventas_pos` (nunca ambos a
  la vez), porque el mismo mecanismo de QR se usa tanto para abonos de reservación como para
  ventas del punto de venta.
- Los montos usan `decimal(10,2)`, nunca `float`, para evitar errores de redondeo en dinero.

## Módulo de Reservaciones (controlador, service, policy, requests)

Además del esquema y los modelos, el paquete incluye la implementación completa del módulo
de Reservaciones, listo para conectar a vistas Blade:

```
app/Services/ReservacionService.php          ← lógica de negocio (crear, actualizar, confirmar,
                                                 rechazar, cancelar, finalizar), con transacción
                                                 + lockForUpdate para evitar doble-booking
app/Exceptions/ReservacionConflictoException.php
app/Http/Requests/StoreReservacionRequest.php
app/Http/Requests/UpdateReservacionRequest.php
app/Policies/ReservacionPolicy.php            ← restringe Admin Salón/Cajero a sus salones asignados
app/Http/Controllers/Admin/ReservacionController.php
routes/reservaciones.php                      ← pegar dentro del grupo admin con middleware
                                                 ['negocio', 'auth']
```

**Pasos para integrarlo:**

1. Copia estas carpetas a las equivalentes de tu proyecto.
2. Registra la Policy en `app/Providers/AppServiceProvider.php` (o `AuthServiceProvider` si tu
   versión de Laravel aún lo trae):
   ```php
   use App\Models\Reservacion;
   use App\Policies\ReservacionPolicy;
   use Illuminate\Support\Facades\Gate;

   Gate::policy(Reservacion::class, ReservacionPolicy::class);
   ```
3. Incluye `routes/reservaciones.php` dentro de tu grupo de rutas admin en `routes/web.php`:
   ```php
   Route::middleware(['negocio', 'auth'])->prefix('admin')->group(function () {
       require __DIR__.'/reservaciones.php';
       // ... resto de módulos
   });
   ```
4. Crea las vistas Blade en `resources/views/admin/reservaciones/` (`index`, `create`, `edit`,
   `show`) — el wireframe de la ficha de reservación ya acordado define el layout de `show.blade.php`.
   **Estas 4 vistas + el layout base + el componente de badge ya vienen incluidos en este paquete**
   (ver sección siguiente).
5. El controlador ya contempla los tres casos de la Policy: un **Admin Negocio** ve/crea en
   cualquier salón de su negocio; un **Admin Salón o Cajero** solo ve/opera los salones que le
   fueron asignados en `usuario_salon` (`salonesPermitidos()` en el modelo `User`).

## Vistas Blade de Reservaciones (incluidas)

```
resources/views/layouts/admin.blade.php              ← layout base con sidebar y flash messages
resources/views/components/badge-estado.blade.php    ← <x-badge-estado :estado="..." /> reutilizable
resources/views/admin/reservaciones/index.blade.php  ← listado con los mismos filtros del controlador
resources/views/admin/reservaciones/_form.blade.php  ← campos compartidos entre crear y editar
resources/views/admin/reservaciones/create.blade.php
resources/views/admin/reservaciones/edit.blade.php
resources/views/admin/reservaciones/show.blade.php   ← ficha completa con pestañas (Alpine.js)
```

**Notas importantes al integrarlas:**

- Usan **Tailwind** (ya viene con Breeze) y **Alpine.js** vía CDN en el layout — si prefieres
  instalarlo por npm en vez de CDN, quita la etiqueta `<script>` del layout y usa
  `import Alpine from 'alpinejs'` en `resources/js/app.js`.
- El layout referencia rutas (`admin.dashboard`, `admin.calendario.index`, `admin.salones.index`,
  etc.) de módulos que **todavía no se han construido** — es intencional, para que el sidebar ya
  quede completo; solo dará error 404 hasta que esos módulos existan. Si quieres probar antes,
  comenta esos `<a>` o crea rutas placeholder.
- `show.blade.php` incluye el **modal de "Registrar abono"** ya conectado a una ruta
  `admin.reservaciones.pagos.store` que corresponde al `PagoAbonoController` de la Fase 4
  (pendiente en el backlog) — el formulario y la UI ya están listos, solo falta ese controlador.
- El botón "Descargar PDF" apunta a `admin.reservaciones.estado-cuenta.pdf`, también pendiente
  (Fase 4, integración con `barryvdh/laravel-dompdf`).
- La pestaña "Historial" deja una nota en el código señalando que la bitácora detallada de
  cambios de estado se resuelve con `spatie/laravel-activitylog` en la Fase 8.

## Módulo de Pagos/Abonos (PagoAbonoController) — incluido

```
app/Contracts/PagoQrGatewayInterface.php         ← contrato común para Unión/BNB/BCP
app/DataTransferObjects/QrResponse.php           ← respuesta uniforme al generar un QR
app/Services/PagoQr/MockQrGateway.php            ← adaptador simulado (ver nota abajo)
app/Services/PagoQr/PagoQrGatewayFactory.php     ← decide qué banco usar según el negocio
app/Services/PagoAbonoService.php                ← registrar, consultar estado QR, confirmar manual
app/Http/Requests/StorePagoAbonoRequest.php
app/Http/Controllers/Admin/PagoAbonoController.php
app/Http/Controllers/Admin/EstadoCuentaController.php   ← PDF del estado de cuenta
resources/views/pdf/estado-cuenta.blade.php
```

**Sobre el `MockQrGateway`:** como todavía no tienen las credenciales de sandbox de Banco
Unión, BNB ni BCP, este adaptador simula el flujo completo — genera un QR de mentira y lo
marca como "pagado" automáticamente 20 segundos después, solo para poder probar en pantalla
el polling y la confirmación sin depender de ningún banco real. **No usar en producción.**
Cuando lleguen las credenciales de un banco, se crea su adaptador real (`BancoUnionQrGateway`,
`BnbQrGateway` o `BcpQrGateway`) implementando `PagoQrGatewayInterface`, y se descomenta la
línea correspondiente en `PagoQrGatewayFactory::paraNegocio()` — nada más del sistema cambia.

**Pasos para integrarlo:**

1. Copia estas carpetas a las equivalentes de tu proyecto.
2. Instala DomPDF si no lo hiciste en el Paso 4 de la guía de configuración:
   ```
   composer require barryvdh/laravel-dompdf
   ```
3. Agrega la relación `negocio()` de `Salon` (ya viene del trait `BelongsToNegocio`) — la vista
   del PDF la usa para mostrar el nombre comercial del negocio en el encabezado.
4. Corre `php artisan route:list --name=admin.pagos` y `--name=admin.reservaciones` para
   confirmar que las rutas de `routes/reservaciones.php` (ya actualizado) quedaron registradas.
5. El modal de abono en `show.blade.php` ya hace polling automático a
   `admin.pagos.estado-qr` cuando el pago es QR — recarga la página sola en cuanto el
   `MockQrGateway` (o el banco real más adelante) marca el pago como confirmado.
6. El botón "Confirmar manualmente" (respaldo si el banco no notifica a tiempo) está resuelto
   en el backend (`PagoAbonoController::confirmarManual`) pero **todavía no tiene botón en la
   UI del modal** — es el siguiente detalle de interfaz a agregar si lo necesitas de inmediato.
