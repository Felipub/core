# Pruebas locales de master

Referencia: `Felipub/core:master`, commit fijado en `dev/regression/refs.json`.
Las expectativas describen resultados funcionales concretos. Un defecto de master deja
el caso rojo: no se usa `test.fail`, skip, retries ni expectativas rebajadas para ocultarlo.

## Alcance

- Unitarias: suite existente más valores de flujos y campos personalizados.
- Integración: alumnos vigentes, fechas límite, curso, tutor, búsqueda, paginación,
  matrícula, cambio de grupo, datos personales/familiares, totales y asistencia.
- Navegador: acceso/cierre de sesión de administrador/docente/personal, permisos,
  admisión, ficha, foto, cambio de grupo, baja, asistencia, informes, dashboard y temas.
- Regresión: estas mismas suites se repiten sobre cada snapshot con datos equivalentes.
- Fuera: acceso de familiares/alumnos, asignaturas y planificación de clases.
  Los familiares figuran únicamente como datos de admisiones/fichas.

## Entorno independiente

Usar siempre `-Functional`. El proyecto es `core-functional-tests`, los volúmenes
son `core-functional-tests_db-master` y `core-functional-tests_db-manjeet`.
El código, dependencias y archivos de ejecución viven en los volúmenes nativos
`core-functional-tests_app-master` y `core-functional-tests_app-manjeet`; se evita
leer miles de archivos PHP desde carpetas compartidas de Windows durante las pruebas.
Los snapshots de preparación, herramientas e informes quedan en
`dev/regression/.runtime-tests` (ignorado por Git).
Los volúmenes de aplicación se crean desde `git archive` del commit fijado y
Composer instala allí el lockfile sin scripts/plugins. No se copian dependencias
ni configuración de instalaciones privadas.
Las restauraciones del otro laboratorio `core-regression` y `.runtime` no se usan.
Puertos del host 8181/8182; navegador en la red interna de su aplicación usa 8081/8082.
Las URLs de host son sólo diagnósticas: el origen canónico usado por los tests es interno.
No se publican MySQL ni la API de fixtures. Correo, SMS, pagos y servicios externos
permanecen desactivados. Los contactos terminan en `example.invalid`.

PHP 7.4.33, MySQL 5.7.44, PHPUnit 9.5.1, Playwright 1.56.1/Chromium.
Composer y npm usan sus lockfiles; no ejecutar actualizaciones de dependencias.
Docker Desktop Linux se selecciona explícitamente en Windows y se comprueba su conexión local.

## Primera preparación

Desde la raíz del repositorio, con Docker Desktop arrancado:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File dev/regression/lab.ps1 prepare -Target master -Functional
powershell -NoProfile -ExecutionPolicy Bypass -File dev/regression/lab.ps1 install -Target master -Functional
```

`prepare` se niega a sobrescribir snapshots. Para comparar Manjeet, preparar e instalar
también `-Target manjeet`; no es necesario para validar master.

## Ejecutar

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File dev/regression/run-tests.ps1 -Target master
```

El comando prepara una vez el volumen nativo de cada snapshot fijado, arranca,
reinicia **sólo los datos desechables**, ejecuta las tres suites
aunque alguna falle y detiene el proyecto conservando resultados. Devuelve código 1
si hay fallos. `-KeepRunning` permite investigar antes de detenerlo.
La ejecución local no despliega ni dispara el workflow espejo, que sólo tiene
ejecución manual. El workflow heredado `ci.yml` conserva sus disparadores de push/PR
y puede ejecutarse en GitHub al publicar la rama; sus resultados son independientes.

Ejecución individual, después de `stage`, `start` y `reset`, todos con `-Functional`:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File dev/regression/lab.ps1 phpunit -Target master -Functional
powershell -NoProfile -ExecutionPolicy Bypass -File dev/regression/lab.ps1 integration -Target master -Functional
powershell -NoProfile -ExecutionPolicy Bypass -File dev/regression/lab.ps1 browser-tests -Target master -Functional
```

Ejecutar las versiones secuencialmente (`-Target both` lo hace así). El navegador
restaura fixtures y la ejecución paralela sobre carpetas compartidas produjo errores
intermitentes de lectura en este equipo; esos intentos no acreditan fallos de aplicación.
Los casos de integración revierten sus transacciones; cada caso de navegador restaura
las tablas mutables y limpia uploads. La primera restauración importa el esquema.
Las fechas se generan en UTC para coincidir con PHP/MySQL del laboratorio; se comprueban
inicio/fin iguales al día actual y días anterior/posterior. La caché de fixtures caduca por día.
No hay dependencias entre casos ni conexiones con servicios reales.

## Datos y esquema

`data/seed.php` añade los datos sintéticos al esquema/metadata revisados del harness.
`data/deployed-schema.sql` contiene **sólo DDL** de dos definiciones ausentes en
`gibbon.sql.gz`: `gibbonApplicationForm.religion` y `gibbonStudentEnrolmentChanged`.
Proceden del backup local de 2026-10-23, cuyo SHA256 figura en el fichero.
No incluye INSERTs, filas, credenciales, triggers, rutinas ni contadores del servidor.
`dev/regression/extract-schema.ps1` documenta cómo se obtuvo. No es una migración de producción.
No hacen falta el backup ni credenciales privadas para ejecutar las pruebas desde un clon.

La API de fixtures corre en otro contenedor, sin puerto publicado ni código dentro del
webroot de la aplicación. Sólo admite operaciones predefinidas: restaurar datos,
consultarlos, preparar una solicitud sintética, leer un workbook exportado y cambiar
entre dos temas. Rechaza cualquier entorno sin la marca `REGRESSION_DISPOSABLE`.

## Resultados y límites

- Resumen: `dev/regression/.runtime-tests/results-summary.json`.
- Recuentos de los últimos XML por suite: `.runtime-tests/test-results.json`;
  `collect-results.ps1 -Target master` los actualiza después de ejecuciones individuales.
- PHPUnit: `.runtime-tests/master/tests/log/unit.xml` e `integration.xml`.
- Playwright: `.runtime-tests/browser-results/master/html/index.html`, `browser.xml`,
  capturas y trazas de fallos. Todo queda fuera de Git.
- Resultado comprobado y defectos: `dev/regression/FUNCTIONAL-RESULTS.md`.

Los módulos personalizados Data Admin, Reporting y Query Builder no están instalados
en los metadatos mínimos del laboratorio. Sus flujos completos no quedan acreditados.
No se afirma cobertura completa de todos los módulos de Gibbon ni validación de producción.
PayPal/SMTP/SMS/OAuth/mysqltosheets necesitan contratos y sustitutos locales propios;
Apps Script/clasp y Supabase no aparecen en los archivos versionados revisados.
No se realizan envíos de correo ni pruebas con cuentas de familiares/alumnos.

## Rollback

`lab.ps1 stop -Functional` detiene únicamente este proyecto y conserva volúmenes.
Para retirar el cambio de código, revertir el commit de pruebas; no hay despliegue que revertir.
No usar `down -v` contra `core-regression`: contiene las restauraciones que se preservan.
Para actualizar la referencia de master, revisar y cambiar `refs.json`, conservar los resultados
anteriores y preparar nuevos snapshots expresamente; nunca hacer pull dentro de `.runtime-tests`.
`stage` comprueba la etiqueta y el marcador del commit y se niega a sobrescribir
un volumen con otra referencia o incompleto. Si se cambia la referencia, conservar
primero lo necesario y revisar expresamente la sustitución de su volumen desechable.
