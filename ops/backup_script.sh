#!/bin/bash
# Deployment prepends private settings; never commit database credentials.
set -euo pipefail
umask 077
BACKUPS_PATH=${BACKUPS_PATH:-/home/shefng1z/backups_gibbon_gyansetu}
SQL_PREFIX=${SQL_PREFIX:-shefng1z_gibb752}
SQL_USER=${SQL_USER:-$SQL_PREFIX}
ZIP_PREFIX=${ZIP_PREFIX:-core_gyansetu_bak}
SOURCE_PATH=${SOURCE_PATH:-/home/shefng1z/public_html/core_gyansetu}
MYSQLDUMP_BIN=${MYSQLDUMP_BIN:-/usr/bin/mysqldump}
ZIP_BIN=${ZIP_BIN:-/usr/bin/zip}
: "${SQL_PASSWD:?Private database password is required}"
cd -- "$BACKUPS_PATH"
today=$(date +%F)
current_month=${today:0:7}
previous_month=$(date -d "$current_month-01 -1 day" +%Y-%m)
temporary_sql=''
temporary_zip=''
cleanup() {
    [[ -z "$temporary_sql" ]] || rm -f -- "$temporary_sql"
    [[ -z "$temporary_zip" ]] || rm -f -- "$temporary_zip"
    :
}
trap cleanup EXIT
trap 'exit 130' INT
trap 'exit 143' TERM
# Concurrent manual and scheduled runs must not overwrite one another.
exec 9>"$BACKUPS_PATH/.backup.lock"
flock -n 9 || { echo 'Another backup is running' >&2; exit 1; }
temporary_sql=$(mktemp "$BACKUPS_PATH/.sql-backup.XXXXXX")
MYSQL_PWD="$SQL_PASSWD" "$MYSQLDUMP_BIN" -u "$SQL_USER" "$SQL_PREFIX" | gzip -c > "$temporary_sql"
gzip -t -- "$temporary_sql"
mv -f -- "$temporary_sql" "$BACKUPS_PATH/${SQL_PREFIX}_${today}.sql.gz"
temporary_sql=''
echo "Database backup OK: ${SQL_PREFIX}_${today}.sql.gz"
# A new archive avoids retaining entries deleted from the source on same-day reruns.
temporary_zip=$(mktemp "$BACKUPS_PATH/.source-backup.XXXXXX.zip")
rm -f -- "$temporary_zip"
if [[ "${today:8:2}" == 28 ]]; then
    # The retained monthly snapshot includes all uploaded documents and installer.
    "$ZIP_BIN" -qr "$temporary_zip" "$SOURCE_PATH"
    echo 'Source scope: monthly full snapshot (includes uploads and installer)'
else
    # Match only these paths at the application root, not nested runtime assets.
    archive_root=${SOURCE_PATH#/}
    archive_root=${archive_root%/}
    "$ZIP_BIN" -qr "$temporary_zip" "$SOURCE_PATH" -x \
        "$archive_root/uploads/*" \
        "$archive_root/dist/GibbonEduCore-InstallBundle.tar.gz"
    echo 'Source scope: daily code snapshot (excludes uploads and installer)'
fi
unzip -tq "$temporary_zip" > /dev/null
mv -f -- "$temporary_zip" "$BACKUPS_PATH/${ZIP_PREFIX}_${today}.zip"
temporary_zip=''
echo "Source backup OK: ${ZIP_PREFIX}_${today}.zip"
# Cleanup only runs after both complete backups have passed integrity checks.
# Keep all current-month copies, days 04/08/12/16/20/24/28 of the previous
# month, and day 28 of every older month, including across year boundaries.
shopt -s nullglob
for file in *.sql *.sql.gz *.zip; do
    case "$file" in
        "${SQL_PREFIX}_"*.sql|"${SQL_PREFIX}_"*.sql.gz) stamp=${file#"${SQL_PREFIX}_"} ;;
        "${ZIP_PREFIX}_"*.zip) stamp=${file#"${ZIP_PREFIX}_"} ;;
        *) continue ;;
    esac
    stamp=${stamp%%.*}
    [[ "$stamp" =~ ^[0-9]{4}-[0-9]{2}-[0-9]{2}$ ]] || continue
    month=${stamp:0:7}
    day=${stamp:8:2}
    if [[ "$month" == "$current_month" || "$day" == 28 ]]; then
        continue
    fi
    if [[ "$month" == "$previous_month" && "$day" =~ ^(04|08|12|16|20|24)$ ]]; then
        continue
    fi
    rm -- "$file"
    echo "Removed expired backup: $file"
done
