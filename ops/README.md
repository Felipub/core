# Gyansetu backups

## ZIP failure correction — deployed 2026-10-31

Read-only FTP inspection confirmed that the live source contains
`mysqltosheets/.htaccess.cpmh3129` with mode 0000. The old July source archive
does not contain this disabled cPanel copy. Running the previous script locally
as UID 1000 against that fixture reproduces zip exit 18: the SQL is published,
but strict error handling removes the temporary ZIP before final publication.
No hosting cron failure trace was accessible to establish direct attribution;
the matching failure mechanism has been reproduced locally.

The local correction excludes only that exact root-relative disabled cPanel
copy, on daily and monthly runs. Active `.htaccess`, integration configuration,
and all other files keep their original treatment. Any unrelated unreadable file
still fails the run and preserves previous backups. Last ZIP diagnostics are
saved privately to `.source-backup-last.log` in the backup directory.

Validation: the original failure was reproduced on daily and day-28 fixtures.
The corrected script passed the existing 13 scenarios plus two daily/monthly
unprivileged regression runs, including active configuration readback and
unrelated unreadable file failures. Tests used an independent offline local
container limited to one CPU and 256 MB.

The first deployment attempt was rejected before execution; work remained local
until the user explicitly authorized FTP deployment, commit and PR on 2026-10-31.
The exact private deployment candidate passed local syntax validation and was
uploaded by FTP, downloaded again and verified byte-for-byte. FTP confirmed mode
0744. Credential-free deployed body SHA-256:
3341a21b563e99c5a7a33d347e6ec697daa487d14d9690b0b3488167c0e8b386.
No manual production backup was run; the next scheduled run and a complete
restore remain unverified. Existing tests and services were not modified.
Rollback: restore the private snapshot at
`C:\GyansetuApp\.backup-zip-fix-work\original-production.sh` through the sibling
`ftp-transfer.ps1 -Action rollback`, preserving mode 0744. That snapshot is the
previous monthly-policy script and retains the unreadable-file failure.

`backup_script.sh` is the credential-free source for the production backup job.
The live root `backup_script.sh` remains ignored by Git. Deployment preserves its
five-line private header, then appends this source without its shebang. Do not
commit the deployed file or private header.

SQL files are written through gzip into a private temporary file, checked, and
renamed into place. Source ZIPs are also built and checked before replacement.
Failures stop the job before retention cleanup. A lock prevents overlapping runs.
The database export options otherwise retain the existing mysqldump behavior.

Retention applies to the configured database's `.sql` and `.sql.gz` files and
the configured source prefix's `.zip` files:

- Keep every current-month copy.
- Keep days 04, 08, 12, 16, 20, 24 and 28 of the previous month.
- Keep day 28 of all older months without an age limit.
- Ignore unrelated filenames and malformed dates.

January correctly treats December of the previous year as the previous month.
Existing SQL files are not recompressed or deleted merely because a gzip version
exists. No historical recovery or off-server replication is included.

Configuration: `BACKUPS_PATH`, `SQL_PREFIX` (database), `SQL_USER` (defaults to
the database name), `SQL_PASSWD`, `ZIP_PREFIX`, `SOURCE_PATH`. `MYSQLDUMP_BIN` and
`ZIP_BIN` allow isolated failure tests. Output directory must already exist.
Requires GNU bash/date, gzip, zip/unzip, flock, mktemp and mysqldump.

## Monthly uploads policy — deployed 2026-10-25

Source ZIPs dated day 28 include the complete application, including root
`uploads/` and `dist/GibbonEduCore-InstallBundle.tar.gz`. These are the monthly
snapshots retained indefinitely by the existing policy. All other daily ZIPs
exclude only those two paths. Other `dist` assets and nested folders named
`uploads` remain included. SQL gzip backups and retention are unchanged.

Restoring a daily ZIP now requires the latest day-28 full snapshot for uploaded
files. Files uploaded after that snapshot have no newer daily backup under this
proposal; up to roughly a month of new or modified uploads could be lost. Daily
ZIPs are code snapshots, not complete standalone application restores. There is
no automatic merge/restore procedure or incremental uploads backup in this change.
Existing historical ZIPs are not rewritten. The user reviewed the local diff and
authorized FTP deployment, commit and PR on 2026-10-25.

## Local validation

The test image extends the already available local PHP image; it creates no
connections to the running regression containers or the hosting account.

```powershell
docker build -t gyansetu-backup-check:local -f ops/Dockerfile.test ops
docker run --rm --network none --cpus 1 --memory 256m --mount "type=bind,source=$($PWD.Path)\ops,target=/checks,readonly" gyansetu-backup-check:local
```

Seven scenarios cover gzip round-trip, real ZIP creation, all retention tiers
for SQL/gzip/ZIP, the year boundary, failed exports, failed ZIP creation,
temporary cleanup, preservation of existing backups on failure, and locking.
Database exports in these local tests use a fixture; no live database is read.

## Deployment record — 2026-10-24

- Branch based on verified `Felipub/core` master `de2a6cb79`.
- Isolated local tests: seven scenarios passed. Exact deployment file passed
  `bash -n` locally before upload.
- The initial hosting dev experiment was interrupted at the user's request;
  its SQL export had completed, but the full source ZIP test did not complete.
  No test processes remained. Private artifacts are in
  `/home1/shefng1z/backup_gzip_dev_20261024`; they are not an active scheduled job.
- Uploaded only the live script through FTP; downloaded it again and verified
  exact byte equality. Live permissions restored to 0744.
- Production syntax check passed. Its credential-free body SHA-256 matches
  `80bbe3ee62b1c30ecab10c27b92d14751c265e87f48ebf7858f291a1bca27d04`.
- Cron unchanged: `24 3 * * *` in server time (India, UTC+05:30).
- No manual full production backup was run, per the user's revised scope.
  First scheduled execution and end-to-end restore remain unverified.
- Existing regression containers and checkout were not modified. No Apps Script,
  Supabase, application deployment, or database changes are involved.

Rollback: the exact prior production script is held privately at
`C:\GyansetuApp\.backup-gzip-work\original-production.sh`. Restore it via FTP
using the private helper `ftp-transfer.ps1 -Action rollback`, retaining mode 0744.
This reverts code only; gzip files already created remain valid and preserved,
but the old script does not apply retention to `.sql.gz` files.

Monthly policy validation: 13 local scenarios passed in the independent, offline
container. The exact production candidate passed local syntax validation, was
uploaded by FTP and downloaded again with matching SHA-256. Production syntax
passed and mode 0744 and the existing cron were verified without running a backup.
Credential-free deployed body SHA-256:
d3ae17048f69a69d3712fc3f32ff13cc0fc01cb1677c86543b48b7a41e36ef9e.
Rollback for this change: restore the private snapshot at
C:\GyansetuApp\.backup-monthly-work\original-production.sh through the sibling
ftp-transfer.ps1 helper with -Action rollback, keeping mode 0744. This restores
the previous gzip script, which includes uploads and installer every day.
First scheduled daily/monthly execution of this policy remains unverified.
