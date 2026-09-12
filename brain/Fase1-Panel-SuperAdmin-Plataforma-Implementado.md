# Fase 1 — Panel Super Admin Plataforma: CRUD de Negocios (11/09/2026)

Implementado sobre SA-1/SA-2/SA-3 de `Historias_Usuario_Pantallas_RentSalon_Pro.md`. **SA-4
(banco QR por negocio) y SA-5 (impersonar) quedan fuera a propósito** — confirmado con el
usuario: SA-4 es más natural en Fase 5 (falta definir banco/pasarela), SA-5 es la más compleja
(swap de sesión + auditoría) y no bloquea nada del resto.

## Qué se construyó

- **Dominio separado** `admin.rentsalon-pro.test`, fuera del middleware `negocio`
  (`IdentificarNegocio`) a propósito — el Super Admin ve todos los negocios, ninguno se resuelve
  como "el negocio actual". Ya estaba en el archivo hosts de Windows.
- **Login reusado**: mismo `AuthenticatedSessionController`/vista `auth.login` que el lado
  tenant (no depende de `app('negocio_actual')`) — solo rutas nuevas (`plataforma.login`/
  `plataforma.logout`) para no chocar con los nombres `login`/`logout` ya usados. Sin registro
  público ni reset de password en este dominio — cuentas Super Admin por seeder/tinker.
- `AuthenticatedSessionController::store()`: el redirect post-login ahora depende del rol
  (`esSuperAdminPlataforma()` → `/plataforma/negocios`; el resto → `/dashboard`).
- `Plataforma\NegocioController`: CRUD completo (sin destroy — "suspender" vía `toggleEstado()`
  cubre la baja, mismo criterio que Salon/Paquete). Listado con métricas por `withCount`
  (# salones, # reservaciones creadas este mes — criterio documentado en el código, la historia
  no especifica si es por fecha de creación o de evento).
- `NegocioPolicy`, `Store/UpdateNegocioRequest` (subdominio validado con regex — evita romper
  `IdentificarNegocio::explode('.', $host)[0]` con un valor inválido — y unique ignorando el
  propio registro en update).
- `layouts.plataforma.blade.php` nuevo — NO reusa `layouts.admin` (ese asume
  `app('negocio_actual')`, que no existe en este dominio).
- `SuperAdminDemoSeeder` (usuario `superadmin@rentsalonpro.test`/`password`), conectado a
  `DatabaseSeeder`.

## Bug real encontrado y corregido: orden de registro de rutas

`Route::domain('admin.rentsalon-pro.test')` tiene que registrarse **ANTES** que el grupo
`Route::middleware('negocio')` de abajo. Laravel matchea rutas en el orden en que se registran,
no por especificidad — el `POST /login` de `auth.php` (dentro del grupo `negocio`, **sin**
restricción de dominio) matchea CUALQUIER host, incluido `admin.rentsalon-pro.test`. Con el grupo
de plataforma registrado después, ese login-tenant "atrapaba" las requests de plataforma antes de
llegar nunca al grupo con dominio — el login de plataforma daba 404 en vez de redirigir.
Encontrado con un test real (`test_login_de_super_admin_redirige_a_plataforma_negocios`), no
manualmente. Mismo tipo de gotcha que "ruta específica antes que la genérica" ya visto en otros
proyectos de esta sesión.

## Verificación

- 9 tests nuevos (`tests/Feature/Plataforma/NegocioControllerTest.php`): listar, 403 para
  `admin_negocio`, login real redirige bien, crear (nace activo), subdominio duplicado
  rechazado, formato de subdominio inválido rechazado, métricas correctas, toggle-estado en
  ambos sentidos, negocio suspendido bloquea su subdominio (`IdentificarNegocio` → 403).
  `tests/TestCase.php` solo siembra un Negocio para el host tenant default — estos tests pasan
  `HTTP_HOST=admin.rentsalon-pro.test` explícito en cada request.
- Regresión completa: **51/51 tests pasando** (42 previos + 9 nuevos).
- QA manual real por HTTP (servidor de desarrollo, BD MySQL real, no solo sqlite de test): login
  como Super Admin → `/plataforma/negocios` → crear "Salones Elegantes" (subdominio
  `saloneselegantes`, ya estaba en el hosts de Windows) → confirmado que el subdominio pasó de
  404 (no existía) a 200 (login del tenant) → suspendido vía toggle-estado → confirmado 403 en
  ese mismo subdominio → reactivado.
- Se agregaron `HasFactory` + `ReservacionFactory` (no existían) para poder testear las
  métricas del listado.

## Pendiente / fuera de alcance

SA-4 (banco QR/credenciales por negocio, `NegocioConfigPago` ya tiene el modelo con cast
`encrypted:array` listo) y SA-5 (impersonar) — ver `Historias_Usuario_Pantallas_RentSalon_Pro.md`.
