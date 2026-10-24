# Entorno de comparación aislado

## Alcance y estado

Preparar infraestructura para añadir pruebas posteriormente. No se implementan casos funcionales, no se corrige ni fusiona la rama y no se despliega a producción. Los checks de acceso son comprobaciones de instalación, no validación funcional. **La preparación no está aprobada hasta que el workflow `Isolated regression environment` termine correctamente y se revisen sus resultados.**

Referencias consultadas remotamente el 24-10-2026, en `refs.json`:

| Versión | Commit |
|---|---|
| master | `de2a6cb79e8391f229a0acb4eabfd7c48ce1049a` |
| manjeet/dashboard-and-form | `9a6ac054ac76c658e571887fff6431801751b2b1` |

No se siguen ramas móviles durante la preparación. Para otra comparación, revisar expresamente nuevos commits, actualizar `refs.json` y volver a preparar snapshots; nunca hacer pull dentro de `.runtime`.

## Requisitos y elección

Docker Engine/Desktop con contenedores Linux, Compose >=2.24.4, Git, tar y PowerShell >=5.1 (PowerShell 7 en Linux). Internet se necesita para clonar, construir y descargar dependencias; la aplicación ejecutada queda en redes internas. No hacen falta PHP, Composer ni Node locales.

Se eligen PHP 7.4.33, Composer 2.2.25 y MySQL 5.7.44: `composer.json` exige `^7.3`, la plataforma Composer es 7.3 y CI ya contemplaba PHP 7.4 y MySQL 5.7. No se cambia `composer.lock` ni se hace `composer update`. Las herramientas bloqueadas son PHPUnit 9.5.1 y Codeception 4.1.14, con PhpBrowser 1.0.2. Se añaden Playwright 1.56.1, su lock independiente y la imagen correspondiente con Chromium real. PHP 8 no satisface el requisito PHP raíz.

El Dockerfile incluye curl, mbstring, gettext, PDO/MySQL, mysqli, GD/JPEG/Freetype, zip, intl, SOAP, bcmath, y las extensiones XML, JSON, fileinfo y OpenSSL del PHP base. `composer check-platform-reqs` valida la instalación real; su resultado prevalece sobre esta inspección. Debian se fija al snapshot 20240601T000000Z para evitar repositorios retirados; PHP, Composer, MySQL, nginx y Playwright se fijan por versión y digest inmutable. Estas versiones antiguas se limitan a este laboratorio. La plataforma verificada es Linux amd64; otras arquitecturas necesitan una comprobación adicional.

La CI existente usa ubuntu-20.04, acciones v2, una matriz 7.3/7.4/8.0/8.1 y un PHPUnit XML con opciones antiguas. Puede fallar por plataforma/acciones o configuración; no se moderniza como parte de este cambio. El workflow nuevo verifica solamente la infraestructura aislada.

## Instalación y operación

Desde la raíz de este checkout:

Para una instalación nueva, clonar el repositorio completo (sin `--depth` ni `--single-branch`) y seleccionar la rama de preparación mientras la PR no esté incorporada:

```powershell
git clone https://github.com/Felipub/core.git core-regression
cd core-regression
git switch dev/isolated-regression-environment-final
```

Después de integrar la PR, usar `master` en lugar de esa rama. El clon completo conserva los dos commits fijados. A continuación:

```powershell
pwsh -File dev/regression/lab.ps1 prepare
pwsh -File dev/regression/lab.ps1 install
pwsh -File dev/regression/lab.ps1 start
pwsh -File dev/regression/lab.ps1 reset
pwsh -File dev/regression/lab.ps1 verify
pwsh -File dev/regression/lab.ps1 status
pwsh -File dev/regression/lab.ps1 stop
```

En Windows sin `pwsh`, usar `powershell -NoProfile -ExecutionPolicy Bypass -File ...`; la excepción sólo afecta ese proceso. No cambiar la política global. Para operar una sola versión añadir `-Target master` o `-Target manjeet`. `prepare` se niega a sobrescribir snapshots existentes. Son exportaciones separadas de commits Git, no copias de instalaciones privadas. Para editar código durante futuras pruebas, trabajar explícitamente en un checkout separado y revisar cómo exportarlo; los snapshots no contienen `.git`.

Aplicaciones: http://127.0.0.1:8081 (master), http://127.0.0.1:8082 (Manjeet). Usuarios `admin`, `teacher`, `staff`, `student`, `parent`; contraseña exclusivamente sintética `Sandbox-Only-2026!`. `leftstudent` representa una baja y tiene el login desactivado. Las credenciales de la DB son públicas y sintéticas, la DB no publica puertos. No reutilizarlas en ningún servicio.

`reset` detiene cada aplicación, borra su almacenamiento generado y recrea sólo su DB `regression`. Es destructivo únicamente para los datos ficticios de esa versión. `stop` conserva los volúmenes. Las dos DB tienen igual nombre dentro de contenedores y redes diferentes; no son una DB compartida.

Los scripts fallan al primer error: si `install` se interrumpe no continuar con `start`. Los scripts Composer y plugins están desactivados durante la descarga. La descarga usa una conexión externa en un contenedor separado, sin ejecutar la aplicación. No montar credenciales de host, sockets Docker ni directorios ajenos dentro del laboratorio.

## Herramientas y pruebas futuras

```powershell
# Ejecutar exclusivamente la suite unitaria existente, cuando se desee:
pwsh -File dev/regression/lab.ps1 phpunit
# Disponibilidad de Codeception, sin ejecutar suites destructivas:
pwsh -File dev/regression/lab.ps1 codeception
# Arranque de Chromium, recursos y accesos ficticios:
pwsh -File dev/regression/lab.ps1 browser
# Ejecutar futuros casos compartidos (actualmente vacíos):
pwsh -File dev/regression/lab.ps1 browser-tests
pwsh -File dev/regression/lab.ps1 codeception-tests
```

Dentro de cada contenedor, `cd /var/www/html/tests && ../vendor/bin/codecept build` genera actores. Se conservan `tests/codeception.yml`, `tests/acceptance.suite.yml` y `tests/install.suite.yml`. No ejecutar `composer test` indiscriminadamente: la suite install y el dump de acceptance pueden reemplazar datos y configuración. Para ejecutar acceptance existente, preparar primero una DB de suite separada y revisar el dump; **esa ejecución no forma parte del check automático nuevo**. `tests/config/environment.php` admite las variables `TEST_ENV=codeception` y la configuración sintética que ya genera el harness.

`lab.ps1 phpunit` utiliza `/harness/phpunit.xml`, una configuración de PHPUnit 9.5 para la suite unitaria existente y el directorio común `tests/regression/unit`; se conserva intacto el XML antiguo del proyecto. `browser-tests` usa `playwright.config.cjs` y `tests/regression/browser`; `codeception-tests` usa el config separado del harness y `tests/regression/codeception/acceptance.suite.yml`, sin importar dumps ni limpiar datos de forma implícita. Los tres directorios comunes están montados para ambas versiones: basta añadir casos en ellos, sin regenerar snapshots. `prepare` añade únicamente el actor de soporte `RegressionTester.php` a cada snapshot y `verify` genera sus métodos. No hay casos funcionales nuevos.

Playwright guarda resultados separados en `.runtime/tools/results/master` y `manjeet`. El runner vacío sale correctamente con `--pass-with-no-tests`; no representa ninguna prueba funcional pasada. Reiniciar los datos antes de comparar ejecuciones de futuros casos. Para ver opciones del navegador: `docker compose -f dev/regression/compose.yaml run --rm browser-master npx playwright test --help`.

Futuras ubicaciones (sin casos implementados):

| Cobertura futura | Ubicación propuesta |
|---|---|
| Admisiones, fotos, vista previa y subida con JavaScript | `tests/regression/browser/admissions/` |
| Permisos por rol | `tests/regression/browser/roles/`, `tests/regression/codeception/roles/` |
| Passed Out y cierre de centros | `tests/regression/browser/students/`, `tests/regression/browser/centres/` |
| Dashboard | `tests/regression/browser/dashboard/` |
| Matrícula, asistencia e informes | `tests/regression/codeception/students/`, `attendance/`, `reports/` y `tests/regression/browser/regressions/` |
| Lógica sin navegador | `tests/regression/unit/` (suite heredada: `tests/unit/`) |

PhpBrowser hace peticiones HTTP pero **no ejecuta JavaScript**. No sirve para verificar el preview de fotos, campos condicionales, eventos de navegador o dashboard dinámico. Playwright está separado del Codeception heredado para evitar cambios de dependencias PHP. El archivo `browser-check.cjs` es sólo un check de instalación; no contiene assertions de funcionalidades de Manjeet.

## Datos sintéticos y diferencias

`reset.php` importa únicamente DDL y metadatos permitidos de `gibbon.sql.gz`; no importa `gibbon_demo.sql.gz`, dumps privados ni tablas de personas. Crea dos cursos 2025-2026 y 2026-2027, un nivel Beginner, cinco roles de acceso y una baja, una familia vinculada, matrículas actual/pasada, asistencia de ejemplo y centros activo, vacío, cerrado y pasado. El administrador, profesor y personal tienen entradas de staff. Se valida que IDs 001/002/003/004/006 significan Administrator/Teacher/Student/Parent/Support Staff.

Las exportaciones omiten todos los demás archivos SQL/comprimidos, CSV y XLSX, incluso los versionados, para no llevar posibles datos históricos al laboratorio. El dump heredado de Codeception tampoco se copia: se debe preparar uno exclusivamente sintético antes de ejecutar acceptance existente.

`custom-fields.json` define los IDs exactos observados en el formulario: 0001, 0003–0016, 0023 y 0027–0033 (sólo los presentes en el archivo). Son definiciones sintéticas explícitas, no una afirmación sobre los IDs o catálogos de producción. 0006 también se utiliza para salario de familiares; 0023 para educación. Las opciones que la rama compara literalmente, como `Free seat`, se conservan. El cargador comprueba ID/nombre después de insertar. Los catálogos restantes deben revisarse al diseñar casos funcionales.

No hay diferencias entre los commits fijados en `composer.json`, `composer.lock`, `gibbon.sql.gz`, tests o `.github`. No se añade migración de esquema. La rama usa el sufijo `Closed` y `attendance=N` para centros cerrados; se reproduce esa representación en ambas DB, sin añadir columnas ni corregir el código. El comportamiento Passed Out se verificará después con sus propios casos.

El servidor de laboratorio ignora los `.htaccess` de cPanel en ambas copias (`AllowOverride None`), porque incluyen un handler `ea-php74` y sesiones en `/var/cpanel/...` inexistentes en Docker. Se conserva el código exportado, se fija la sesión en `var/sessions` y se reproduce en Apache el bloqueo de ejecutables en uploads. Ésta es una diferencia de configuración explícita y común a ambas versiones.

Chromium comparte el espacio de red aislado de su aplicación, de modo que usa las mismas URLs de loopback sin reescribir orígenes, cookies ni formularios. `verify` debe ejecutarse tras `reset`: compara hashes de los datos y del esquema de las tablas de fixtures antes de los accesos. Si se han editado datos desde la UI, reiniciarlos para volver a comparar.

## Aislamiento y servicios

Cada versión tiene red interna, volumen MySQL y árbol de archivos propios: fotos/adjuntos en uploads, sesiones, temporales y logs en var, caché de plantillas y logs de tests. Sólo se publican los dos puertos web en loopback. No se monta una configuración de conexión real. PHP mail usa `/bin/false`; SMTP, SMS, OAuth y claves se vacían en la DB. **No se envía correo**, tampoco se configura un capturador que pudiera reenviarlo. Los contactos usan example.invalid.

Las cookies se llaman `CORE_master` y `CORE_manjeet`; los puertos de un mismo host no aíslan cookies por sí solos. Sus archivos de sesión también quedan separados.

Docker no publicó los puertos de contenedores conectados exclusivamente a redes internas durante la verificación. Se añaden dos gateways nginx 1.26.3, uno por versión: sólo ellos conectan una red de entrada y su red interna, sólo publican loopback y sólo reenvían al upstream fijo de su aplicación. No contienen código, credenciales ni acceso a archivos de la aplicación, y no son proxies abiertos. PHP, MySQL y Chromium conservan exclusivamente las redes internas.

No se inicia cron, FTP ni scripts de respaldo. Las exportaciones omiten `.vscode`, `mysqltosheets`, `backup_script.sh` y error_log; la PR conserva `.gitignore` y `modules/.gitignore` de master. No se copian credenciales locales. La red interna impide que PHP/SDKs alcancen servicios externos; una CSP y el navegador del check bloquean conexiones externas del cliente. No desactivar estas barreras para resolver errores de aplicación.

`mysqltosheets` es una integración local excluida por master; la documentación previa indica DB y tokens OAuth. Se desactiva omitiéndola por completo y no se inspeccionan sus secretos. Google API, OneWaySMS/Twilio/Nexmo y SMTP aparecen en código/dependencias: sin credenciales y sin salida externa. No se encontraron archivos de clasp/Apps Script ni Supabase en los archivos versionados de los commits revisados; esto no prueba que no existan servicios fuera del repo. No se ha conectado ni modificado ningún servicio real. Para ensayar integraciones posteriormente hacen falta mocks locales y contratos explícitos; no bastará con poner credenciales reales.

Los módulos personalizados Data Admin, Reporting y Query Builder están en el código del baseline, pero el esquema mínimo no los registra como módulos instalados. Permanecen desactivados en los fixtures de ambas versiones; los módulos core Students, Attendance y Reports sí están registrados. Data Admin tiene un Composer/lock propio que fija `symfony/yaml v2.8.34` y requiere su vendor local, que no se instala en este laboratorio al no activar ese módulo. Una futura cobertura de esos módulos requerirá instalar sus dependencias bloqueadas y registrar sus metadatos/esquema sólo en el sandbox. Los informes de alumnos afectados por la rama sí tienen acciones en el esquema core. No se presupone paridad con la configuración de módulos del servidor real.

## Verificación y límites pendientes

En el equipo de preparación se confirmó acceso remoto, commits exactos, ausencia de diferencias de esquema/dependencias/tests, sintaxis PowerShell y JavaScript y exportación separada de ambos snapshots. El PHP, Composer y Docker no estaban disponibles localmente. Por tanto, no se han verificado localmente build, DB, reset, HTTP, recursos ni login, ni se han ejecutado suites PHPUnit/Codeception. Consultar `VERIFICATION.md` y la ejecución de la PR para el resultado vigente.

El workflow nuevo ejecuta `prepare`, `install`, `start`, `reset` y `verify` en Ubuntu, y vuelve a reiniciar y verificar después de usar los datos. `verify` comprueba redes internas exclusivas de la aplicación, ausencia de puertos DB publicados, equivalencia de fixtures/esquema, DB Testing, extensiones, carpetas escribibles, cookies, hashes ficticios, requisitos Composer, versiones/configuración de PHPUnit/Codeception y Chromium con cinco accesos y respuestas de recursos. Un fallo de descarga, build, permisos o DB es infraestructura; un error PHP/página después de arrancar debe investigarse y clasificarse, sin corregir funcionalidades aquí ni presentarlo como regresión demostrada.

## Rollback

`lab.ps1 stop` detiene sólo el proyecto Compose `core-regression`. Para desechar **todos** los datos sintéticos, desde este directorio ejecutar `docker compose down -v` (irreversible para esos volúmenes). Con los contenedores detenidos se puede renombrar `.runtime` para conservarlo o eliminar exclusivamente esa carpeta tras comprobar su ruta. Ninguna de estas operaciones debe apuntar a otro proyecto Compose.

Antes de incorporar la PR, basta detener los contenedores y volver al commit base con `git switch --detach de2a6cb79e8391f229a0acb4eabfd7c48ce1049a`; los datos ignorados se conservan. Si se incorpora con squash, `git revert <commit de integración>` elimina el cambio aditivo; si se integra mediante merge, usar `git revert -m 1 <commit de merge>`. Revisar y tramitar ese rollback por PR según las reglas del repositorio. No hay rollback de producción porque no se despliega ni se modifica.
