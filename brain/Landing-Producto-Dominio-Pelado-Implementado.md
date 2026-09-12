# Landing del producto en el dominio pelado — Implementado (11/09/2026)

El usuario reportó "no tengo acceso" en `http://rentsalon-pro.test:8080/`. Diagnóstico: era un
404 REAL de Laravel (no de Apache) — `IdentificarNegocio` no encontraba ningún `Negocio` con
`subdominio='rentsalon-pro'` ni `dominio_personalizado='rentsalon-pro.test'`, comportamiento
correcto para un multi-tenant donde ese host nunca fue el subdominio de ningún negocio cliente.

Confirmado con el usuario (AskUserQuestion): construir una landing real para el producto RentSalon
Pro en sí (marketing, no un negocio cliente) en vez de dejar el 404.

## Qué se construyó

- `LandingController` + `resources/views/landing.blade.php` — autocontenida (no extiende ningún
  layout existente, mismo criterio que `layouts/plataforma.blade.php`: no hay `negocio_actual` en
  este dominio). Hero, features, "cómo funciona", CTA de contacto (mailto — sin CRM/captura de
  leads todavía) y link a un negocio de ejemplo (`saloneslapaz.rentsalon-pro.test`).
- Ruta `Route::domain('rentsalon-pro.test')->get('/', ...)` registrada ANTES del grupo
  `middleware('negocio')`, mismo patrón y mismo motivo de orden que
  `Route::domain('admin.rentsalon-pro.test')` de Plataforma (Fase 1) — Laravel matchea por orden
  de registro, no por especificidad.

## Bug real encontrado de paso (entorno de testing, sin impacto en producción)

El host de testing por default (`APP_URL` de `.env`) era literalmente `rentsalon-pro.test` —
elegido en su momento porque coincidía "gratis" con ese valor, sin pensar en que algún día ese
dominio pelado tendría su propia ruta real. Con el landing reclamando exactamente ese host,
`TestCase`'s Negocio sembrado (que también usaba `dominio_personalizado='rentsalon-pro.test'`)
quedó invisible: cualquier test que pegara a `/` sin overridear el host explícitamente terminaba
sirviendo el landing en vez del portal del negocio de prueba. Encontrado por un test
(`PortalControllerTest::test_home_carga_sin_login`), no manualmente.

**Fix**: host de testing dedicado `negocio-test.rentsalon-pro.test` (no colisiona con ningún
dominio real: ni el pelado del landing, ni `admin.rentsalon-pro.test` de Plataforma) —
`phpunit.xml` (`APP_URL`) + `tests/TestCase.php` (Negocio sembrado) actualizados en conjunto.

## Verificado

- 2 tests nuevos (`LandingControllerTest`): carga sin ningún Negocio sembrado (a propósito — es la
  página del producto, no de un tenant), y un test explícito de regresión confirmando que ya no
  choca con el host de testing del resto de la suite. Suite completa: **126/126 passed**.
- Manual, contra Apache (`:8080`) real: `http://rentsalon-pro.test:8080/` ahora sirve el landing
  (200, contenido correcto) y se confirmó que `admin.rentsalon-pro.test:8080` y
  `saloneslapaz.rentsalon-pro.test:8080` siguen funcionando exactamente igual (no se rompió nada).

## Commiteado y pusheado

`3dd6cd8` en `main`, https://github.com/clarosgerd/renta-salon (segundo commit del repo, después
del primer commit inicial del roadmap completo).
