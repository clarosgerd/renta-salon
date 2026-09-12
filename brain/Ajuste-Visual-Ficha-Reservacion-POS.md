# Ajuste visual — Ficha de reservación + POS (11/09/2026)

El usuario compartió mockups de referencia para 2 pantallas ya funcionales (construidas en
sesiones/fases anteriores) y reportó que el Dashboard "no se ve igual" — las tarjetas "Próximos
eventos"/"Solicitudes nuevas" salían apiladas en vez de lado a lado.

## Bug real: CSS sin recompilar

Diagnóstico: el manifest de Vite (`public/build/manifest.json`) tenía fecha ANTERIOR al último
cambio en `dashboard.blade.php` — el rebuild del dashboard (fase anterior) nunca corrió
`npm run build` después de tocar el Blade. Tailwind extrae las clases utilitarias escaneando los
archivos Blade AL MOMENTO DEL BUILD — sin recompilar, `lg:grid-cols-2` (y cualquier otra clase
nueva introducida en esa edición) no existía en el CSS servido, y el navegador caía al
`grid-cols-1` default (apiladas). **Fix**: `npm run build` — de paso corrigió también cualquier
clase nueva que el landing del producto (fase inmediatamente anterior) tampoco había recompilado.

## Ajustes visuales — Ficha de reservación (`admin/reservaciones/show.blade.php`)

- Título ahora antepone el tipo de evento: "Boda — María Fernández" (mismo mapeo de
  `tipo_evento` ya usado en Dashboard/Portal público).
- Tabla de abonos: columna "Estado" se fusionó dentro de "Método" (ícono + banco si es QR +
  "(pendiente)" en ámbar si no está confirmado) — 4 columnas en vez de 5, igual que el mockup.
  "Registrado por" ahora incluye el rol entre paréntesis.
- Botones "Descargar estado de cuenta"/"Registrar abono" se movieron del header de la tarjeta al
  pie de la pestaña "Estado de cuenta", full-width, con los mismos estilos del mockup (outline +
  sólido negro).
- Pestaña "Ventas POS": nuevo botón "+ Vender en el POS" que navega a
  `admin.pos.index(reservacion=X)` — antes esa pestaña era de solo lectura, sin forma de llegar
  al POS ya vinculado a esa reservación específica.
- Controller: se agregó `pagosAbonos.pagoQr` al eager load (necesario para mostrar el banco del
  abono QR).

## Ajustes visuales — POS (`admin/pos/index.blade.php` + `PosController`)

- **Nuevo**: `PosController::index()` acepta `?reservacion=` — si viene, la reservación se
  bloquea (badge "Vinculado a: RSV-XXXX — Boda María Fernández" con link a su ficha, campo
  oculto en el form) en vez del `<select>` libre. `findOrFail` scoped a `pendiente`/`confirmada`
  — un id de otra reservación (cancelada, de otro negocio, inexistente) da 404 en vez de dejar
  pasar en silencio hacia una venta de mostrador no pedida.
- Filtro de categoría como píldoras clickeables ("Todas"/"Bebidas"/"Decoración"/"Mobiliario",
  derivadas de las categorías realmente presentes) combinado con el buscador de texto — antes
  solo había buscador.
- Cada producto muestra un ícono según su categoría (🥤🪑💐🍽️📦).
- Carrito: cantidad ahora se ve apilada bajo el nombre del producto (antes en una tabla con
  columnas separadas), más parecido al mockup.
- Método de pago: los 2 botones de acción SON el método (💵 Efectivo / 📲 Cobrar QR) en vez de un
  toggle + un botón "Cobrar" separado — un clic cobra directo con ese método.

## Verificado

- 2 tests nuevos (`PosControllerTest`): reservación vinculada bloquea el selector y muestra sus
  datos; una reservación cancelada/inexistente en `?reservacion=` da 404. Suite completa:
  **132/132 passed**.
- Manual, contra Apache (`:8080`) + MySQL reales: comparado visualmente contra ambos mockups —
  título con tipo de evento, tabla de abonos de 4 columnas con ícono de banco, botones al pie,
  link "+ Vender en el POS" funcionando, POS con el badge "Vinculado a" + píldoras de categoría +
  iconos + los 2 botones de cobro, todo con datos reales de la BD.

## Commiteado y pusheado

A `main` en https://github.com/clarosgerd/renta-salon.
