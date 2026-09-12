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
