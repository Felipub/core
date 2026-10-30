# Resultado de las pruebas funcionales locales

Fecha: 30-10-2026, Europe/Madrid. Datos y reloj del laboratorio: UTC.
Repositorio de destino: `Felipub/core`; no se crea ninguna PR a `GibbonEdu/core`.

## Referencias y entorno

- Master comprobado con `git ls-remote`: `de2a6cb79e8391f229a0acb4eabfd7c48ce1049a`.
- Comparación Manjeet fijada: `9a6ac054ac76c658e571887fff6431801751b2b1`.
- PHP 7.4.33, MySQL 5.7.44, PHPUnit 9.5.1 y Playwright 1.56.1 con Chromium.
- Proyecto Docker `core-functional-tests`, volúmenes y snapshots desechables separados.
- Aplicaciones y dependencias en volúmenes nativos Docker; informes compartidos con Windows.
  Los intentos previos con carpetas compartidas y ejecución paralela tuvieron errores
  intermitentes de lectura y no sirven como evidencia de regresión de aplicación.
- No se modifican fuentes de aplicación, composer.json/lock ni el SQL original de master.
- El backup local se lee exclusivamente para obtener dos definiciones DDL ausentes;
  no se importan ni publican sus registros. Las cuentas y documentos de prueba son sintéticos.

## Ejecución

Comando de aceptación: `powershell -NoProfile -ExecutionPolicy Bypass -File dev/regression/run-tests.ps1 -Target both`.
Las versiones se ejecutan secuencialmente, sin reintentos automáticos. El comando
continúa después de fallos funcionales, conserva los resultados y devuelve código 1.

| Versión | Tipo | Casos | Correctos | Fallos | Errores |
|---|---|---:|---:|---:|---:|
| master | Unitarias | 202 | 201 | 1 | 0 |
| master | Integración | 10 | 8 | 0 | 2 |
| master | Navegador | 22 | 21 | 1 | 0 |
| **master total** | | **234** | **230** | **2** | **2** |
| Manjeet | Unitarias | 202 | 201 | 1 | 0 |
| Manjeet | Integración | 10 | 10 | 0 | 0 |
| Manjeet | Navegador, pasada completa | 22 | 20 | 2 | 0 |
| **Manjeet total bruto** | | **234** | **231** | **3** | **0** |

Son 46 casos nuevos (14 unitarios contando proveedores de datos, 10 de integración
y 22 de navegador), además de los 188 unitarios existentes. No hay casos omitidos.
El resultado completo de la última ejecución y sus marcas de tiempo está en
`.runtime-tests/test-results.json`, con los XML de PHPUnit y el informe HTML/JUnit de Playwright.

## Comparación Manjeet

La misma batería ejecuta 202 casos unitarios, 10 de integración y 22 de navegador.
El defecto del campo obligatorio también se reproduce. Las diez pruebas de integración
pasan, incluidos ambos porcentajes de asistencia que fallan en master.

En `ADM-02` la comparación se detiene antes del envío: Manjeet sustituye el campo
editable `homeAddress` por un input oculto con valor fijo `Gyansetu`. Se señala como
**diferencia de funcionalidad respecto a master**, que requiere decidir si es intencionada;
no se considera automáticamente un defecto ni se fuerza el valor oculto para obtener verde.
La comprobación exige visibilidad explícita y evita esperar dos minutos intentando editarlo.
Esa ejecución no acredita las aserciones posteriores de alta de admisión en Manjeet.

La pasada completa de Manjeet también registró `socket hang up` en `USR-02`.
Una comprobación dirigida posterior del mismo caso, sin modificar código ni expectativas,
pasó **1/1** en 18,5 s. Su XML se conserva aparte en
`browser-results/manjeet/recheck-USR02.xml`; no reemplaza ni maquilla el informe completo.
Se registra como incidencia de transporte no reproducida en esa comprobación, sin
afirmar una regresión funcional. Los recuentos brutos anteriores se conservan.

## Defectos confirmados de master

1. **Campo personalizado obligatorio ausente no activa el rechazo.**
   `tests/regression/unit/CustomFieldsTest.php` lo reproduce con un gateway simulado.
   En `src/Forms/CustomFieldHandler.php:176`, `&= true` mantiene a falso el indicador
   inicial. La expectativa sigue siendo rechazar el valor obligatorio ausente.
2. **Dos porcentajes de asistencia fallan al consultar MySQL.**
   Los casos `testAttendanceTakenPercentageMatchesTwoOfThreePupils` y
   `testAttendancePresentPercentageMatchesTwoOfThreePupils` esperan 66,67% para
   dos alumnos de tres. Las consultas de `StudentGateway.php:404` y `:418` envían
   `gibbonSchoolYearID` sin un marcador SQL correspondiente: `SQLSTATE[HY093]`.
3. **El alta pública de admisión no guarda la solicitud.**
   `ADM-02` completa el formulario válido y exige una fila nueva, pero recibe
   «Your request failed due to a database error» y no hay inserción.
   `applicationFormProcess.php:322`–`:326` escribe `block`, `district`, `hasAadhar`,
   `entryLevel`, `feeStatus`, `feeReason`, `schoolingStatus` e `image_240`, ausentes
   tanto en el esquema versionado como en el DDL del respaldo revisado.
   Una consulta diagnóstica de sólo lectura confirma `Unknown column 'block'`.
   No se inventan columnas ni se altera el código para ocultar el fallo.

En master son cuatro casos rojos de tres categorías funcionales. No se usan skips,
fallos esperados o reintentos para convertirlos en verdes. No se confirma aquí
el estado de producción actual: sólo el commit y los esquemas locales revisados.

## Cobertura y límites

- Los 188 casos unitarios existentes se ejecutan junto a 14 nuevos.
- Integración usa el conector MySQL de la aplicación y revierte cada transacción.
- El navegador restaura datos por caso mediante un fixture automático y comprueba
  el nombre inicial de control. Las pruebas que sólo consultan páginas también restauran.
- Se comprueba persistencia, historial de cambios de grupo y conservación de entidades
  de control. Impresión usa `report.php`; exportación usa `export.php` y se lee el XLSX real.
- Se cubren administración, docentes y personal. No se prueban accesos de alumnos/familiares,
  asignaturas ni planificación de clases. Las familias se usan sólo como datos relacionados.
- La prueba de «mis grupos» configura expresamente ese permiso existente. Los valores
  iniciales del instalador conceden al docente el permiso de todos los grupos.
- `ADM-02` no alcanza sus comprobaciones posteriores de consulta/edición: quedan bloqueadas
  por el fallo de inserción. Aceptación y rechazo usan solicitudes sintéticas preparadas aparte.
- Data Admin, Reporting y Query Builder no están instalados en los metadatos mínimos;
  sus flujos completos quedan pendientes. No se afirma cobertura de todos los módulos de Gibbon.
- SMTP/SMS/OAuth/PayPal/mysqltosheets están desactivados. No hay envíos ni llamadas reales.
  Apps Script/clasp y Supabase no aparecen en los archivos versionados revisados; no se conectan.
- El workflow nuevo es manual. El CI heredado conserva sus disparadores de push/PR;
  cualquier resultado de ese CI es independiente de esta evidencia local.
- Revisión visual: el informe impreso resulta legible. En el tema Gyansetu, cabecera
  y menú presentan poco contraste con esta configuración sintética; la paridad visual
  con la configuración real queda pendiente y no se considera aprobada por los casos DOM.

## Veredicto y rollback

La implementación de pruebas y la validación del producto son resultados distintos.
La condición solicitada de «todas pasan en master» **no se cumple** por los defectos
anteriores; master no queda aprobado funcionalmente. Corregir la aplicación requiere
un cambio posterior, sin rebajar las expectativas de estas pruebas.

No hay despliegue ni pruebas en producción: este cambio añade pruebas locales.
Para detener, usar `lab.ps1 stop -Functional`; conserva resultados y volúmenes.
Para retirar las pruebas del repositorio, revertir su commit y los del harness si se
retira también el laboratorio. No borrar `.runtime` ni los volúmenes `core-regression`.
Las fuentes y datos restaurados del checkout previo se preservan.
