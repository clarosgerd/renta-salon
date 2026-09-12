# Verificación — Calendario tras el fix del tema (11/09/2026)

El usuario reportó `http://saloneslapaz.rentsalon-pro.test:8080/admin/calendario` "no se puede
ver", inmediatamente después de subir el fix del plugin de tema de FullCalendar (ver
`brain/Fix-Tema-Calendario-FullCalendar.md`).

## Diagnóstico

Dado que "no se puede ver" es más severo que "el texto no se ve bien" (el síntoma original), se
investigó si el fix había introducido una regresión real. Se descartaron, en orden:
1. Error 500/PHP en la ruta — confirmado 200 vía curl.
2. JS con error de sintaxis en el bundle compilado — `node --check` OK en ambos chunks
   (`calendario-*.js` y el chunk compartido `palette-*.js` donde vive el plugin del tema).
3. Import sin resolver de `preact/jsx-runtime` (dependencia real del módulo de tema) — confirmado
   que solo existe UNA copia de `preact` en `node_modules` (sin conflicto de instancias) y que el
   bundle no contiene ningún specifier sin resolver.

Como el bug es puramente de runtime en el navegador (curl no ejecuta JS), se levantó Chrome
headless con un perfil limpio y aislado (`--user-data-dir` temporal, sin tocar el perfil real del
usuario) vía el protocolo DevTools, con captura de `Runtime.consoleAPICalled`/
`Runtime.exceptionThrown` para ver errores reales de consola:

- **Calendario público** (`/salones/1`, sin login): capturado con screenshot — se ve
  correctamente, con eventos "Ocupado", día actual resaltado, meses/días en español.
- **Calendario admin** (`/admin/calendario`, sesión real vía login programático con
  `admin@saloneslapaz.test`): **0 mensajes de consola** (sin errores/excepciones) — screenshot
  confirma renderizado correcto: eventos con nombre de cliente y color por estado, checkboxes de
  salones, leyenda, grilla completa con tipografía normal.

## Conclusión

El servidor ya sirve la versión corregida sin ningún problema — confirmado con evidencia real
(capturas + 0 errores de consola), no solo inferencia. La explicación más probable de lo que vio
el usuario es **caché del navegador** con el HTML/JS/CSS de ANTES del fix (los nombres de archivo
cambiaron por el hash de Vite, pero si el navegador tenía la página HTML cacheada, seguía
apuntando a los archivos viejos). Se le pidió al usuario probar un refresco forzado
(Ctrl+Shift+R / Cmd+Shift+R) o ventana privada — pendiente de confirmación.

## Nota técnica: cómo se verificó (para la próxima vez que haga falta este tipo de diagnóstico)

Chrome/Edge headless local + protocolo DevTools vía WebSocket (paquete `ws` de npm, instalado
temporalmente en un directorio aparte, no como dependencia del proyecto) — permite: navegar,
ejecutar JS en la página (para simular el submit del login), y capturar tanto screenshot PNG como
los eventos de consola/excepciones reales del navegador. Mucho más confiable que inferir desde
curl (que no ejecuta JS) o desde lectura estática del bundle compilado.
