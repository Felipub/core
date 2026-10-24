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

Docker Engine/Desktop con contenedores Linux, Compose >=2.24.4 (por `!override`), Git, tar y PowerShell >=5.1 (PowerShell 7 en Linux). Internet se necesita para clonar, construir y descargar dependencias; la aplicación ejecutada queda en redes internas. No hacen falta PHP, Composer ni Node locales.

Se eligen PHP 7.4.33, Composer 2.2.25 y MySQL 5.7.44: `composer.json` exige `^7.3`, la plataforma Composer es 7.3 y CI ya contemplaba PHP 7.4 y MySQL 5.7. No se cambia `composer.lock` ni se hace `composer update`. Las herramientas bloqueadas son PHPUnit 9.5.1 y Codeception 4.1.14, con PhpBrowser 1.0.2. Se añaden Playwright 1.56.1, su lock independiente y la imagen correspondiente con Chromium real. PHP 8 no satisface el requisito PHP raíz.

El Dockerfile incluye curl, mbstring, gettext, PDO/MySQL, mysqli, GD/JPEG/Freetype, zip, intl, SOAP, bcmath, y las extensiones XML, JSON, fileinfo y OpenSSL del PHP base. `composer check-platform-reqs` valida la instalación real; su resultado prevalece sobre esta inspección. Las imágenes tienen etiquetas de versión; no se fijan digests y los paquetes Debian pueden variar. Para reproducibilidad binaria estricta, registrar digests y un mirror apt antes de aprobar ese requisito adicional. Estas versiones antiguas se limitan a este laboratorio.

La CI existente usa ubuntu-20.04, acciones v2, una matriz 7.3/7.4/8.0/8.1 y un PHPUnit XML con opciones antiguas. Puede fallar por plataforma/acciones o configuración; no se moderniza como parte de este cambio. El workflow nuevo verifica solamente la infraestructura aislada.

## Instalación y operación

Desde la raíz de este checkout:

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
```

Dentro de cada contenedor, `cd /var/www/html/tests && ../vendor/bin/codecept build` genera actores. Se conservan `tests/codeception.yml`, `tests/acceptance.suite.yml` y `tests/install.suite.yml`. No ejecutar `composer test` indiscriminadamente: la suite install y el dump de acceptance pueden reemplazar datos y configuración. Para ejecutar acceptance existente, preparar primero una DB de suite separada y revisar el dump; **esa ejecución no forma parte del check automático nuevo**. `tests/config/environment.php` admite las variables `TEST_ENV=codeception` y la configuración sintética que ya genera el harness.

Futuras ubicaciones (sin casos implementados):

| Cobertura futura | Ubicación propuesta |
|---|---|
| Admisiones, fotos, vista previa y subida con JavaScript | `tests/browser/admissions/` |
| Permisos por rol | `tests/browser/roles/` y `tests/acceptance/User Admin/` |
| Passed Out y cierre de centros | `tests/browser/students/`, `tests/browser/centres/` |
| Dashboard | `tests/browser/dashboard/` |
| Matrícula, asistencia e informes | `tests/acceptance/Students/`, nuevas carpetas Attendance/Reports y `tests/browser/regressions/` |
| Lógica sin navegador | `tests/unit/` |

PhpBrowser hace peticiones HTTP pero **no ejecuta JavaScript**. No sirve para verificar el preview de fotos, campos condicionales, eventos de navegador o dashboard dinámico. Playwright está separado del Codeception heredado para evitar cambios de dependencias PHP. El archivo `browser-check.cjs` es sólo un check de instalación; no contiene assertions de funcionalidades de Manjeet.

## Datos sintéticos y diferencias

`reset.php` importa únicamente DDL y metadatos permitidos de `gibbon.sql.gz`; no importa `gibbon_demo.sql.gz`, dumps privados ni tablas de personas. Crea dos cursos 2025-2026 y 2026-2027, un nivel Beginner, cinco roles de acceso y una baja, una familia vinculada, matrículas actual/pasada, asistencia de ejemplo y centros activo, vacío, cerrado y pasado. El administrador, profesor y personal tienen entradas de staff. Se valida que IDs 001/002/003/004/006 significan Administrator/Teacher/Student/Parent/Support Staff.

`custom-fields.json` define los IDs exactos observados en el formulario: 0001, 0003–0016, 0023 y 0027–0033 (sólo los presentes en el archivo). Son definiciones sintéticas explícitas, no una afirmación sobre los IDs o catálogos de producción. 0006 también se utiliza para salario de familiares; 0023 para educación. Las opciones que la rama compara literalmente, como `Free seat`, se conservan. El cargador comprueba ID/nombre después de insertar. Los catálogos restantes deben revisarse al diseñar casos funcionales.

No hay diferencias entre los commits fijados en `composer.json`, `composer.lock`, `gibbon.sql.gz`, tests o `.github`. No se añade migración de esquema. La rama usa el sufijo `Closed` y `attendance=N` para centros cerrados; se reproduce esa representación en ambas DB, sin añadir columnas ni corregir el código. El comportamiento Passed Out se verificará después con sus propios casos.

## Aislamiento y servicios

Cada versión tiene red interna, volumen MySQL y árbol de archivos propios: fotos/adjuntos en uploads, sesiones, temporales y logs en var, caché de plantillas y logs de tests. Sólo se publican los dos puertos web en loopback. No se monta una configuración de conexión real. PHP mail usa `/bin/false`; SMTP, SMS, OAuth y claves se vacían en la DB. **No se envía correo**, tampoco se configura un capturador que pudiera reenviarlo. Los contactos usan example.invalid.

No se inicia cron, FTP ni scripts de respaldo. Las exportaciones omiten `.vscode`, `mysqltosheets`, `backup_script.sh` y error_log; la PR conserva `.gitignore` y `modules/.gitignore` de master. No se copian credenciales locales. La red interna impide que PHP/SDKs alcancen servicios externos; una CSP y el navegador del check bloquean conexiones externas del cliente. No desactivar estas barreras para resolver errores de aplicación.

`mysqltosheets` es una integración local excluida por master; la documentación previa indica DB y tokens OAuth. Se desactiva omitiéndola por completo y no se inspeccionan sus secretos. Google API, OneWaySMS/Twilio/Nexmo y SMTP aparecen en código/dependencias: sin credenciales y sin salida externa. No se encontraron archivos de clasp/Apps Script ni Supabase en los archivos versionados de los commits revisados; esto no prueba que no existan servicios fuera del repo. No se ha conectado ni modificado ningún servicio real. Para ensayar integraciones posteriormente hacen falta mocks locales y contratos explícitos; no bastará con poner credenciales reales.

## Verificación y límites pendientes

En el equipo de preparación se confirmó acceso remoto, commits exactos, ausencia de diferencias de esquema/dependencias/tests, sintaxis PowerShell y JavaScript y exportación separada de ambos snapshots. El PHP, Composer y Docker no estaban disponibles localmente. Por tanto, no se han verificado localmente build, DB, reset, HTTP, recursos ni login, ni se han ejecutado suites PHPUnit/Codeception. Consultar `VERIFICATION.md` y la ejecución de la PR para el resultado vigente.

El workflow nuevo ejecuta `prepare`, `install`, `start`, `reset` y `verify` en Ubuntu. `verify` comprueba DB Testing, extensiones, carpetas escribibles, hashes ficticios, requisitos Composer, versiones de PHPUnit/Codeception y Chromium con cinco accesos y respuestas de recursos. Un fallo de descarga, build, permisos o DB es infraestructura; un error PHP/página después de arrancar debe investigarse y clasificarse, sin corregir funcionalidades aquí ni presentarlo como regresión demostrada.

## Rollback

`lab.ps1 stop` detiene sólo el proyecto Compose `core-regression`. Para desechar **todos** los datos sintéticos, desde este directorio ejecutar `docker compose down -v` (irreversible para esos volúmenes). Con los contenedores detenidos se puede renombrar `.runtime` para conservarlo o eliminar exclusivamente esa carpeta tras comprobar su ruta. Ninguna de estas operaciones debe apuntar a otro proyecto Compose.

El cambio de repositorio es aditivo: si se incorporara, revertir su commit elimina el harness y workflow sin tocar código funcional. Antes de revertir detener contenedores. No hay rollback de producción porque no se despliega ni se modifica. No fusionar esta PR hasta completar las verificaciones pendientes.
