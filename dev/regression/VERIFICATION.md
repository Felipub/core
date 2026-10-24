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
- **Pendiente:** verificar HTTP desde el host mediante gateways, equivalencia de fixtures/esquema, cookies separadas, configuración PHPUnit compatible, recursos y cinco accesos ficticios en ambas versiones.
- Suites funcionales existentes ejecutadas: ninguna. Suite unitaria existente ejecutada: ninguna. No hay veredicto funcional de la rama.
- Producción, clasp, Supabase, FTP y mysqltosheets reales: no conectados ni modificados.

Veredicto actual: preparación implementada, aceptación del entorno pendiente de ejecución. No declarar completo el alcance mientras existan pendientes de infraestructura.
