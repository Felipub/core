# Registro de verificación

Fecha de preparación: 24-10-2026 (Europe/Madrid).

- Consulta `git ls-remote` autenticada: ambos commits en `refs.json` confirmados.
- Checkout nuevo `C:\GyansetuApp\core-test-environment`, rama `dev/isolated-regression-environment`; instalaciones anteriores preservadas.
- Se leyeron README.md, `.github/CONTRIBUTING.md` y el registro del baseline; no hay AGENTS.md versionado. El README de SHEF y la instrucción expresa del usuario establecen master como destino, frente a la recomendación genérica upstream de development.
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
- CI `40108746090` (`dccd5142b`): **correcto**, incluidas las imágenes fijadas por digest, redes internas exclusivas de PHP, ausencia de puertos MySQL publicados y reset después de los accesos. Tras reset se volvieron a comprobar datos equivalentes, herramientas y diez accesos. Resultado: https://github.com/Felipub/core/actions/runs/40108746090.
- CI `40109029414` (`d3b92ede1`): el nuevo config de Codeception falló por concatenar sus rutas absolutas con `/harness`. Es un fallo de configuración de herramientas; se cambia a rutas relativas al config, conforme al comportamiento de Codeception 4.1.14. Las imágenes, reset y comprobaciones previas de infraestructura continuaron correctas.
- CI `40109418128` (`fa9467459`): **correcto**. Generación del actor compartido, dos configuraciones PHPUnit, directorios montados, runner vacío de Playwright en las dos versiones, y repetición completa de reset/verificación correctos. No se ejecutaron casos funcionales. Resultado: https://github.com/Felipub/core/actions/runs/40109418128.
- CI `40109843417` (`6c70c4f34`): límites PHP, infraestructura, reset después del uso, veinte accesos y runner vacío de Playwright correctos. La ejecución vacía de Codeception detectó que faltaba su subdirectorio convencional `acceptance/`; se añade sin casos funcionales. No fue un fallo de aplicación.
- **Comprobación final pendiente:** ejecutar la suite vacía de Codeception con ese directorio y repetir el pipeline final.
- Suites funcionales existentes ejecutadas: ninguna. Suite unitaria existente ejecutada: ninguna. No hay veredicto funcional de la rama.
- Producción, clasp, Supabase, FTP y mysqltosheets reales: no conectados ni modificados.

Veredicto actual: preparación implementada, aceptación del entorno pendiente de ejecución. No declarar completo el alcance mientras existan pendientes de infraestructura.
