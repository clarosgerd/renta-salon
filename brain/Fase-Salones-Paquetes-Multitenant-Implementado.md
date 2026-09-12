# Activación del ruteo multi-tenant + Salones y Paquetes (11/09/2026)

Implementado sobre `Plan_Implementacion_RentSalon_Pro_v2.md`, siguiendo el patrón ya establecido
por el módulo Reservaciones (Controller delgado + Service + Policy + FormRequests).

## Hallazgo: la app nunca se había ejecutado contra una request HTTP real

Al activar el ruteo se encontraron y corrigieron 5 bugs fundacionales, ninguno relacionado a
Salones/Paquetes en sí — existían desde antes, simplemente nunca se habían disparado porque
`routes/web.php` tenía todo comentado:

1. **`routes/web.php`** tenía el bloque completo de rutas multi-tenant/auth/dashboard comentado;
   las rutas de Reservaciones/Pagos estaban activas pero sin prefijo `/admin` ni middleware
   `auth`/`negocio`. El login redirigía a `route('dashboard')`, que no existía.
2. **`app/Models/Salon.php`, `Reservacion.php`, `PagoAbono.php`, `Imagen.php`** no declaraban
   `protected $table` — Eloquent adivinaba mal el nombre (`salons`, `reservacions`,
   `pago_abonos`, `imagens`) contra las tablas reales (`salones`, `reservaciones`,
   `pagos_abonos`, `imagenes`). **Cualquier query a estos 4 modelos fallaba** con "Base table or
   view not found" — el módulo Reservaciones jamás funcionó contra la BD real.
3. **`app/Http/Controllers/Controller.php`** no tenía el trait `AuthorizesRequests` — cualquier
   `$this->authorize(...)` (usado en los 4 controllers: Reservacion, PagoAbono, Salon, Paquete)
   explotaba con "Call to undefined method ::authorize()".
4. **`resources/views/layouts/admin.blade.php`** linkeaba a 7 rutas nombradas, de las cuales solo
   2 existían — cualquier pantalla que usara este layout reventaba al renderizar el sidebar.
5. **`routes/reservaciones.php`** era una copia muerta de las rutas de `web.php`, nunca incluida
   en ningún lado.

Todo corregido: `$table` explícito en los 4 modelos, `AuthorizesRequests` en el Controller base,
`routes/web.php` reescrito con la estructura real (`negocio` → `auth` → `admin` prefix), sidebar
con `@if (Route::has(...))` por link, `routes/reservaciones.php` borrado.

## Salones y Paquetes — implementado

- `SalonPolicy`/`PaquetePolicy`: solo `admin_negocio`/`super_admin_plataforma` (ver AN-1/AN-2 en
  Historias de Usuario — Admin Salón/Cajero no administran el catálogo).
- `SalonService`: galería de fotos vía `Storage::disk('public')` + tabla polimórfica `imagenes`
  ya existente — agregar (`crear`/`actualizar`), mover (botones ▲/▼, sin drag-and-drop), eliminar
  (borra archivo físico + fila).
- `PaqueteService`: `servicios_incluidos` (JSON array, chips en el form) + pivote `paquete_salon`
  vía `sync()` — vacío = aplica a todos los salones (`Paquete::aplicaATodosLosSalones()`, regla
  ya existente en el modelo).
- **Gotcha real evitado**: la validación `exists:salones,id` corre contra la BD cruda, sin pasar
  por `NegocioScope` — sin `Rule::exists('salones','id')->where('negocio_id', ...)` explícito, un
  `salon_id` de OTRO negocio pasaría la validación. Corregido en Store/UpdatePaqueteRequest.
- Sin `show`/`destroy` — `toggle-activo` cubre "ocultar del portal sin borrar historial" (AN-1).

## Infraestructura de testing corregida

`tests/TestCase.php` no seedaba ningún `Negocio` — con el ruteo activado, **todos** los tests
existentes de Breeze (login, registro, perfil, reset de password — 16 tests) empezaron a fallar
con 404 porque `IdentificarNegocio` no encontraba ningún tenant para el host que usan las pruebas
(`rentsalon-pro.test`, de `APP_URL`). Se agregó una siembra automática de `Negocio` en
`TestCase::setUp()` (con guard `Schema::hasTable` para no romper tests sin `RefreshDatabase`,
como el stub `ExampleTest`). Se agregaron `HasFactory` + factories nuevas para `Negocio`, `Salon`,
`Paquete` (no existían).

## Verificación

- **42 tests pasando** (25 preexistentes sin regresiones + 17 nuevos, incluido un test de
  aislamiento multi-tenant por cada módulo: un Salon/Paquete de OTRO negocio nunca aparece en el
  listado ni es editable vía URL directa — confirma que `NegocioScope` filtra solo, sin depender
  de ningún chequeo manual en los controllers).
- **QA manual real contra HTTP** (servidor de desarrollo en `127.0.0.5:8100`, resolviendo el
  subdominio `saloneslapaz.rentsalon-pro.test` vía el archivo hosts ya configurado): login con
  `admin@saloneslapaz.test`/`password` → dashboard → creación de un Salón con foto real (subida,
  movida, eliminada) → creación de un Paquete con servicios incluidos y limitado a un salón →
  toggle activo/inactivo en ambos módulos. Todo confirmado contra la BD real (MySQL
  `rentsalon_pro`), no solo contra sqlite de test.
- `database/seeders/NegocioDemoSeeder.php` ahora es idempotente y siembra 2 salones + 2 paquetes
  de ejemplo; conectado a `DatabaseSeeder::run()`.

## Pendiente / fuera de alcance (según lo acordado)

Panel Super Admin de Plataforma (`NegocioController`, dominio `admin.rentsalon-pro.test`),
Calendario, POS, Productos, Inventario, Usuarios, Portal público (Fase 6). El sidebar ya está
preparado para que estos módulos "aparezcan solos" cuando se construyan (sin tocar el layout de
nuevo).
