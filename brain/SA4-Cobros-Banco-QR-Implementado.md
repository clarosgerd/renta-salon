# SA-4 — Cobros (banco QR y credenciales) por negocio — Implementado (11/09/2026)

Historia diferida durante la Fase 1 (Panel Super Admin Plataforma), retomada a pedido del
usuario. Ver texto exacto de SA-4 en `brain/Historias_Usuario_Pantallas_RentSalon_Pro.md` §1.1.

## Decisión de diseño (inconsistencia real en la documentación)

El árbol de menús (§2) y §3.13 ubican "Configuración → Cobros" dentro del panel del **tenant**
(`/admin`). El criterio de aceptación de SA-4 dice lo contrario: "solo son editables desde este
panel central" (Plataforma). Se resolvió a favor del criterio de aceptación de SA-4 — la pantalla
vive en `/plataforma/negocios/{negocio}/config-pago`, solo Super Admin la ve y edita. El negocio
nunca ve ni edita sus propias credenciales. Si en el futuro se quiere una vista de solo-lectura
para el tenant, es una extensión aparte.

Confirmado con el usuario (AskUserQuestion): sin credenciales reales de ningún banco todavía —
se construyó la pantalla completa enchufada al `MockQrGateway` ya existente. El día que llegue una
integración real, solo se agrega el adapter y se descomenta el `case` correspondiente en
`PagoQrGatewayFactory::paraNegocio()` — esta pantalla no cambia.

## Qué se construyó

- `app/Http/Requests/UpdateNegocioConfigPagoRequest.php` — valida banco (`in:union,bnb,bcp`),
  credenciales solo `required_if:reemplazar_credenciales,1`, y una regla `after()` que exige
  banco+credenciales (existentes o recién enviados) antes de permitir `activo=1`.
- `app/Http/Controllers/Plataforma/NegocioConfigPagoController.php` — `edit`/`update`/
  `probarConexion`. Reutiliza la Policy `NegocioPolicy::update` (misma que ya usa
  `toggleEstado`), sin Policy nueva.
- `resources/views/plataforma/negocios/config-pago.blade.php` — selector de banco, cuenta
  destino, bloque de credenciales enmascarado ("•••• configuradas" + checkbox "Reemplazar" que
  revela los inputs, JS plano igual que el color picker de `_form.blade.php`), checkbox
  "Activar pagos reales", botón "Probar conexión" (deshabilitado si no hay banco).
- Rutas nuevas anidadas bajo `plataforma/negocios/{negocio}/config-pago` (GET/PUT/POST
  probar-conexion) — registradas dentro del grupo `Route::domain('admin.rentsalon-pro.test')`
  ya existente, mismo orden de registro que el resto de Fase 1 (no reintroduce el bug de
  orden de rutas ya documentado en Fase 1).
- Links "Cobros (QR)" agregados en `plataforma/negocios/index.blade.php` (columna de acciones) y
  `plataforma/negocios/edit.blade.php` (header).

## Verificado

- 11 tests nuevos (`tests/Feature/Plataforma/NegocioConfigPagoControllerTest.php`): acceso
  (guest/rol no autorizado), crear config la primera vez, PUT sin "reemplazar" preserva
  credenciales existentes, PUT con "reemplazar" sí las cambia, validación de `activo` sin
  banco/credenciales, `probarConexion` sin config vs. con config, y confirmación explícita de
  que las credenciales cifradas NUNCA aparecen en el HTML de la vista (`assertDontSee`).
- Regresión: 9/9 tests de `NegocioControllerTest` (Fase 1) sin cambios. Suite completa del
  proyecto: **69/69 passed**, sin ninguna regresión.
- Manual, contra Apache (`:8080`) + MySQL reales, con el negocio demo "Salones La Paz" (id=1):
  login real como Super Admin, GET de la pantalla vacía, PUT guardando banco Unión +
  credenciales reales de prueba, confirmado en BD que el valor cifrado no es legible en texto
  plano (`SELECT` directo sobre `negocio_config_pago` no contiene el secreto), confirmado que un
  segundo PUT cambiando solo banco/cuenta SIN marcar "reemplazar" preserva las credenciales
  intactas, confirmado que el HTML de la vista nunca contiene el texto plano de las
  credenciales, y que POST a "Probar conexión" actualiza `ultima_prueba_conexion` (resuelve al
  `MockQrGateway`, éxito determinístico). Fila de prueba borrada al terminar — el negocio demo
  queda sin config-pago, igual que antes.

## Sin migraciones

La tabla `negocio_config_pago` ya existía desde el groundwork original de Fase 5 (sin usar hasta
ahora). No se tocó `PagoQrGatewayFactory` ni `MockQrGateway`.

## Sin repo git

Este proyecto no tiene `.git` (confirmado en sesiones anteriores) — todo el trabajo es
solo-filesystem, sin commit.
