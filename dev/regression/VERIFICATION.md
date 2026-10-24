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
- **Pendiente:** build, instalación Composer, validación Compose, reset MySQL, HTTP, recursos, accesos ficticios y ejecución real de herramientas. El workflow nuevo debe aportar esos resultados.
- Suites funcionales existentes ejecutadas: ninguna. Suite unitaria existente ejecutada: ninguna. No hay veredicto funcional de la rama.
- Producción, clasp, Supabase, FTP y mysqltosheets reales: no conectados ni modificados.

Veredicto actual: preparación implementada, aceptación del entorno pendiente de ejecución. No declarar completo el alcance mientras existan pendientes de infraestructura.
