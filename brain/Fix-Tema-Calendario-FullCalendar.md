# Fix — Tema de FullCalendar nunca se aplicaba (11/09/2026)

El usuario reportó que en el Calendario "no se ve el texto correctamente".

## Diagnóstico

Bug real desde la Fase 3 (cuando se construyó el calendario por primera vez), que se copió sin
querer al calendario público de la Fase 6. `calendario.js`/`portal-calendario.js` importaban el
CSS del tema "classic" de FullCalendar:
```js
import 'fullcalendar/skeleton.css';
import 'fullcalendar/themes/classic/theme.css';
import 'fullcalendar/themes/classic/palette.css';
```
pero **en FullCalendar v7 (`^7.1.0`, instalado en este proyecto), los temas son PLUGINS, no solo
CSS** — así lo dice el propio README del paquete:
```js
import classicThemePlugin from 'fullcalendar/themes/classic'
// ...
plugins: [dayGridPlugin, classicThemePlugin, /* ... */]
```
Sin registrar ese plugin, FullCalendar renderiza usando sus clases internas por default (nombres
hasheados genéricos, ej. `.fc-1U`, `.fc-pp`) — el CSS del tema "classic" (que define clases
`.fc-classic-*`, un naming COMPLETAMENTE distinto) queda huérfano: cargado en la página pero sin
ningún elemento del DOM al que aplicarse. Resultado: el calendario se ve sin la tipografía/
espaciado/bordes que el tema define — exactamente "texto que no se ve correctamente".

Confirmado revisando `node_modules/fullcalendar/all/index.js`: la clase `Calendar` de la
build "all" mergea `plugins` de forma ADITIVA (`[...pluginsInternos, ...(optionOverrides.plugins || [])]`),
así que agregar el plugin del tema es un cambio seguro, no reemplaza nada de lo ya funcionando
(vistas de mes/semana, interacción, etc., que sí vienen incluidas en `fullcalendar/all`).

## Fix

`import classicThemePlugin from 'fullcalendar/themes/classic';` + `plugins: [classicThemePlugin]`
en las opciones del `new Calendar(...)`, en los 2 archivos que usan FullCalendar:
- `resources/js/calendario.js` (Fase 3, admin)
- `resources/js/portal-calendario.js` (Fase 6, portal público)

## Verificado

- Suite completa sin regresión (**132/132** — este bug era puramente de JS/runtime del navegador,
  no afecta ningún test de backend).
- Confirmado en el bundle compilado que la cadena `fc-classic` (clases del tema) ahora SÍ aparece
  en el JS — antes de este fix no estaba, porque el módulo del plugin nunca se importaba.
- Manual, contra Apache (`:8080`) real: ambas pantallas (`/admin/calendario` y la ficha pública de
  un salón) sirven los assets recompilados con el fix.

## Recordatorio (relacionado con el bug de CSS sin recompilar de la tarea anterior)

Este bug estuvo latente desde la Fase 3 sin que ningún test lo detectara — es puramente visual/
runtime de JS en el navegador, invisible para PHPUnit y para verificaciones por curl (que no
ejecutan JS). Solo se hizo evidente cuando el usuario lo vio en un navegador real. Vale la pena
recordar: para librerías de terceros con su propio sistema de theming (FullCalendar, y
potencialmente otras), confirmar en su documentación oficial CÓMO se activa un tema para la
versión instalada exacta — "importar el CSS" no siempre alcanza.

## Commiteado y pusheado

A `main` en https://github.com/clarosgerd/renta-salon.
