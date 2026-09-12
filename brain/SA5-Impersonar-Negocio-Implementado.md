# SA-5 — Impersonar un negocio — Implementado (11/09/2026)

Última historia pendiente de Fase 1 (Panel Super Admin Plataforma). Texto exacto en
`brain/Historias_Usuario_Pantallas_RentSalon_Pro.md` §1.1.

## Decisión de diseño confirmada con el usuario

Sin pantalla de selección de usuario — "Impersonar" entra automáticamente como el **primer
usuario con rol `admin_negocio`** del negocio. Si no tiene ninguno, se bloquea con un mensaje
claro en vez de mostrar el botón deshabilitado.

## Restricción real de arquitectura que determinó el diseño

`SESSION_DOMAIN=null` — las cookies de `admin.rentsalon-pro.test` y `{subdominio}.rentsalon-pro.test`
NO se comparten, y el tenant se resuelve por Host header (`IdentificarNegocio`). Por eso
"impersonar" no podía ser un simple cambio de usuario en la sesión actual: hay que llevar el
navegador al dominio del negocio con un link firmado de un solo uso
(`URL::temporarySignedRoute` + middleware `signed` estándar de Laravel, generado con
`URL::forceRootUrl()` para construir la URL contra el host del negocio en vez del de
`admin.rentsalon-pro.test`). Se restaura el root URL original inmediatamente después — no toca
`config/session.php` ni la config global.

## Qué se construyó

- `impersonacion_logs` (migración nueva) — el log de auditoría que pide SA-5:
  `super_admin_user_id`/`usuario_impersonado_id` (nullOnDelete, el log sobrevive si el usuario se
  borra), `negocio_id` (cascadeOnDelete), `ip_origen`, `iniciada_en`, `finalizada_en`.
  `app/Models/ImpersonacionLog.php` (sin timestamps de Eloquent, usa sus propios campos).
- `Plataforma\ImpersonacionController::iniciar()` — crea el log, redirige (302) al link firmado
  del dominio del negocio. `::historial()` — pantalla de solo lectura
  (`/plataforma/impersonaciones`), primera pieza de lo que Fase 8 generalizará.
- `ImpersonacionEntradaController` (lado tenant, fuera del namespace Plataforma) — `entrar()`
  (ruta `GET /impersonar/entrar/{log}`, protegida por middleware `signed`, doble chequeo extra:
  log no finalizado + `negocio_id` coincide con `app('negocio_actual')`) y `salir()` (marca
  `finalizada_en`, cierra sesión, redirige a login).
- Banner ámbar en `layouts/admin.blade.php` (`session('impersonacion_log_id')`) con botón
  "Salir de impersonar", visible en TODA pantalla tenant mientras dura la sesión de soporte.
- Botón "Impersonar" + link "Log de impersonación" en `plataforma/negocios/index.blade.php`.

## Nota técnica: por qué el chequeo "negocio correcto" es defensa en profundidad, no alcanzable por HTTP

`URL::temporarySignedRoute` firma la URL absoluta completa (host incluido). Si alguien reenvía el
link firmado contra un host distinto al que se usó para generarlo, `hasValidSignature()` ya lo
rechaza (403) ANTES de llegar al chequeo manual de `negocio_id` — no existe combinación válida de
firma+host equivocado. El chequeo extra queda como backstop ante una futura regresión (ej. cambio
de `SESSION_DOMAIN` o de cómo se registran las rutas), documentado así en el test
(`ImpersonacionEntradaControllerTest`, no incluye un caso "log de otro negocio" por esta razón).

## Verificado

- 9 tests nuevos (4 `Plataforma/ImpersonacionControllerTest` + 5 `ImpersonacionEntradaControllerTest`):
  autorización, negocio sin admin_negocio bloquea sin crear log, log creado con los 3 campos
  correctos, historial visible, URL sin firma rechazada, firma válida autentica correctamente,
  log ya finalizado → 404 (protege contra reuso), **negocio suspendido bloquea incluso con firma
  válida** (heredado de `IdentificarNegocio`, sin lógica propia), `salir` finaliza el log y cierra
  sesión. Suite completa: **78/78 passed**, sin regresión.
- Manual, contra Apache (`:8080`) + MySQL reales, con "Salones La Paz" (negocio demo) y su
  `admin@saloneslapaz.test`: login real como Super Admin → POST impersonar → redirect 302 al host
  correcto CON el puerto `:8080` preservado → siguiendo el link firmado, autenticado como "Admin
  Demo" → dashboard muestra el banner ámbar → "Salir de impersonar" → confirmado en BD
  (`super_admin`/`negocio`/`usuario_impersonado`/`iniciada_en`/`finalizada_en` todos correctos) →
  reusar la misma URL firmada una segunda vez da **404** (bloqueado, no reproducible) → pantalla
  `/plataforma/impersonaciones` muestra el log completo. Negocio y tabla de logs limpiados al
  terminar la QA manual.

## Sin repo git

Este proyecto sigue sin `.git` — todo el trabajo es solo-filesystem, sin commit.

## Fase 1 (SA-1 a SA-5) — completa

Con esto se cierran las 5 historias de Super Admin Plataforma de la Fase 1.
