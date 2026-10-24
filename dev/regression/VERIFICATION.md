# Registro de verificación

Fecha de preparación: 24-10-2026 (Europe/Madrid).

- Consulta `git ls-remote` autenticada: ambos commits en `refs.json` confirmados.
- Checkout nuevo `C:\GyansetuApp\core-test-environment`, rama `dev/isolated-regression-environment`; instalaciones anteriores preservadas.
- Se leyó `.github/CONTRIBUTING.md`; no hay AGENTS.md versionado. Se aplica la instrucción expresa del usuario de dirigir la PR a master frente a la recomendación genérica upstream de development.
- `composer.json`, lock, esquema comprimido, tests y workflows son idénticos entre los commits de referencia.
- Parser PowerShell: sin errores; `node --check browser-check.cjs`: correcto.
- `prepare`: correcto para ambos snapshots; no se sobrescribió trabajo existente.
- Lock de Playwright generado con `npm install --package-lock-only --ignore-scripts`; no se actualizaron dependencias PHP.
- Inspección del parser SQL: reconoce las 179 sentencias CREATE TABLE y 357 ALTER TABLE del esquema. No equivale a importarlo en MySQL.
- Docker/PHP/Composer no disponibles en PATH ni en ubicaciones habituales de este equipo. WSL no accesible en la comprobación inicial.
- CI `40106625102`: fallo de infraestructura en apt (paquetes Debian retirados, HTTP 404); corregido fijando el snapshot Debian.
- CI `40106824608` y `40107053922`: build, instalación Composer/npm, validación Compose, arranque de los dos servidores y reset de las dos DB correctos. En master: conexión DB Testing, extensiones, carpetas escribibles, hashes de credenciales ficticias, requisitos Composer, PHPUnit 9.5.1, Codeception 4.1.14 y generación de actores correctos. No se llegó al check de herramientas de Manjeet por ejecución secuencial con parada al primer fallo.
- CI `40107412820`: mismos checks básicos correctos; Chromium arrancó y recibió HTTP 200, pero el acceso de admin no se completó. Diagnóstico: LiveValidation, jQuery y gibbonFormSubmitted no cargados durante la configuración con reescritura de orígenes. Se sustituye esa configuración por loopback compartido con la aplicación, sin tocar funcionalidades.
- CI `40107814186` (`8b2fb840f`): build y reset correctos; el nuevo check HTTP del host detectó `Connection refused`. Docker no publicaba los puertos de la red interna. Se añaden gateways nginx de destino fijo sin habilitar salida externa a las aplicaciones. Ese intento no llegó a equivalencia de datos ni navegador.
- CI `40108214648` (`6d44a42d4`): **correcto**. Build e instalación completos, arranque, reset de ambas DB, HTTP del anfitrión mediante gateways, hashes equivalentes de datos/esquema, DB Testing, cookies separadas y directorios escribibles correctos. En ambas versiones: `composer check-platform-reqs`, `phpunit --version`, `phpunit --configuration /harness/phpunit.xml --list-suites`, `codecept --version` y `codecept build` correctos. Chromium: admin, teacher, staff, student y parent con login y recursos correctos (10 accesos). Contenedores detenidos al terminar. Resultado: https://github.com/Felipub/core/actions/runs/40108214648.
- **Comprobación final pendiente:** repetir el reset después de los accesos y validar la revisión que fija digests inmutables y comprueba las redes/puertos de los contenedores en ejecución.
- Suites funcionales existentes ejecutadas: ninguna. Suite unitaria existente ejecutada: ninguna. No hay veredicto funcional de la rama.
- Producción, clasp, Supabase, FTP y mysqltosheets reales: no conectados ni modificados.

Veredicto actual: preparación implementada, aceptación del entorno pendiente de ejecución. No declarar completo el alcance mientras existan pendientes de infraestructura.
