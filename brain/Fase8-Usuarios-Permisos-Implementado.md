# Fase 8 — Usuarios y permisos — Implementado (11/09/2026)

Cubre AN-6 y §3.11, ver `brain/Historias_Usuario_Pantallas_RentSalon_Pro.md`.

## Nota de alcance

El roadmap describe esta fase como "Roles..., asignación de salones, **auditoría**" pero ninguna
historia de usuario pide un log de auditoría general de altas/bajas — a diferencia de SA-5, que
sí lo pide explícito para impersonar. Se interpretó como la frase genérica del roadmap, no un
requisito propio: no se construyó un audit log nuevo acá. `impersonacion_logs` sigue siendo la
única pieza de auditoría real.

## Punto de partida

Igual que en fases anteriores: `users.negocio_id`/`role`/`activo` y la tabla pivote
`usuario_salon` ya existían y **ya estaban en uso** — `User::salonesPermitidos()` ya consumía
`$this->salones()->pluck('salones.id')` para escopar Reservaciones/Calendario. Esta fase solo
construyó la PANTALLA para administrar esas asignaciones.

## Qué se construyó

- `UserPolicy` (nombre de archivo/clase sin traducir a propósito — Laravel resuelve la Policy por
  convención del nombre de clase del MODELO, `App\Models\User`, no por el idioma del resto del
  panel; `UsuarioPolicy` nunca se hubiera auto-registrado). Solo `admin_negocio`/
  `super_admin_plataforma` (AN-6 es la única historia).
- `UsuarioService` — a diferencia de Salon/Paquete/Producto, `User` **no usa el trait
  `BelongsToNegocio`** (a propósito: `super_admin_plataforma` vive fuera de cualquier negocio), así
  que `negocio_id` se asigna a mano al crear, y cada query del controller filtra explícitamente
  (`where('negocio_id', app('negocio_actual')->id)`) + un `abort_unless` manual en
  edit/update/toggleActivo — la protección multi-tenant automática de `NegocioScope` no aplica acá.
- Selector de rol (Admin Negocio / Admin Salón / Cajero — Super Admin Plataforma no se crea desde
  acá) + multi-select de salones (checkboxes, se oculta con JS vainilla si el rol es Admin
  Negocio, que ve todos los salones automáticamente sin pivote). Se exige al menos 1 salón para
  Admin Salón/Cajero (`required_if`) — con cero salones esos roles quedan sin poder hacer nada.
- Cambiar el rol de un usuario de Admin Salón/Cajero a Admin Negocio limpia el pivote viejo
  (`sync([])`), para no dejar filas vestigiales.
- Guard de auto-desactivación: un Admin Negocio no puede desactivar su propia cuenta
  (`UsuarioService::toggleActivo`, lanza excepción capturada en el controller).
- `LoginRequest::authenticate()` ahora exige `activo => true` como parte de las credenciales de
  `Auth::attempt()` — mismo mensaje genérico que una contraseña incorrecta, sin filtrar que la
  cuenta existe pero está inactiva.

## 2 bugs reales encontrados y corregidos de paso

1. **`User::$fillable` no incluía `email_verified_at`** — al crear un usuario nuevo desde este
   flujo con `'email_verified_at' => now()` (necesario para no trabar la cuenta en la pantalla de
   verificación de correo, `/dashboard` exige `['auth','verified']` y no hay infraestructura de
   email en este proyecto), Eloquent lo descartaba en silencio por protección de mass-assignment.
   Encontrado por un test, no manualmente. Agregado a `$fillable`.
2. **Gotcha ya documentado, confirmado real en esta fase**: los usuarios demo de
   `NegocioDemoSeeder` nunca tuvieron `email_verified_at` seteado — nunca se notó porque ninguna
   pasada manual anterior pegó justo a `/dashboard` con esas cuentas. Un usuario nuevo creado
   desde esta pantalla SÍ llega ahí — confirmado en la QA manual que carga bien.

## Verificado

- 11 tests nuevos (`UsuarioControllerTest`): autorización (cajero/admin_salon → 403), alta con
  salones asignados (pivote + `email_verified_at` seteado), alta sin salones para Admin
  Salón/Cajero falla validación, alta de Admin Negocio sin pivote, cambio de rol limpia el
  pivote viejo, actualizar sin tocar password preserva el hash, toggle-activo, auto-desactivación
  bloqueada, usuario desactivado no puede loguearse, aislamiento multi-tenant (404 manual, no
  Global Scope). Suite completa: **110/110 passed**, sin regresión.
- Manual, contra Apache (`:8080`) + MySQL reales, con "Salones La Paz": login como
  `admin@saloneslapaz.test` → creó un Admin Salón de prueba con 1 de sus 3 salones asignado →
  login con la cuenta NUEVA → `/dashboard` cargó bien (confirma el fix del gotcha) →
  `User::salonesPermitidos()` devolvió solo el salón asignado (id real, no los 3 del negocio) →
  desactivado desde la UI → login rechazado con el mismo mensaje genérico de credenciales
  inválidas. Usuario de prueba borrado al terminar.

## Sin repo git

Este proyecto sigue sin `.git` — todo el trabajo es solo-filesystem, sin commit.

## Roadmap — estado

Con esto quedan completas todas las fases del roadmap original salvo la **Fase 6 (Portal
público)**, que sigue pendiente.
