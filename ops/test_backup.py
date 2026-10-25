"""Run with Python 3 and GNU bash/tools; all fixtures stay in a temporary directory."""
import datetime
import gzip
import os
from pathlib import Path
import subprocess
import sys
import tempfile
import zipfile

script = Path(sys.argv[1] if len(sys.argv) > 1 else Path(__file__).with_name('backup_script.sh')).resolve()
checks = 0
with tempfile.TemporaryDirectory(prefix='backup-tests-') as root:
    root = Path(root)
    binaries = root / 'bin'
    binaries.mkdir()
    for name, body in {
        'date': '#!/bin/bash\nif [[ "$1" == +%F ]]; then echo "$TEST_DATE"; else /bin/date "$@"; fi\n',
        'dump': '#!/bin/bash\nprintf "CREATE TABLE fixture (id INT);\\n-- Dump completed\\n"\nexit "${DUMP_FAIL:-0}"\n',
        'zip-fail': '#!/bin/bash\nexit 9\n',
    }.items():
        target = binaries / name
        target.write_text(body)
        target.chmod(0o700)
    source = root / 'source with spaces'
    source.mkdir()
    (source / 'index.php').write_text('<?php echo "fixture";')
    for folder in ('uploads/2026/10', 'dist', 'modules/example/uploads'):
        (source / folder).mkdir(parents=True)
    (source / 'uploads/2026/10/photo.jpg').write_bytes(os.urandom(8192))
    (source / 'uploads/document.pdf').write_bytes(os.urandom(8192))
    (source / 'dist/GibbonEduCore-InstallBundle.tar.gz').write_bytes(os.urandom(8192))
    (source / 'dist/required.js').write_text('runtime asset')
    (source / 'modules/example/uploads/required.js').write_text('nested runtime asset')
    sizes = {}
    for today in ('2026-10-24', '2026-10-28', '2027-01-02', '2027-01-28'):
        dest = root / today
        dest.mkdir()
        month = today[:7]
        previous = (datetime.datetime.strptime(month + '-01', '%Y-%m-%d').date() - datetime.timedelta(days=1)).strftime('%Y-%m')
        keep = set()
        for prefix, extensions in (('testdb', ('.sql', '.sql.gz')), ('testcode', ('.zip',))):
            for stamp, retained in ((month+'-01', True), (previous+'-04', True), (previous+'-08', True), (previous+'-12', True), (previous+'-16', True), (previous+'-20', True), (previous+'-24', True), (previous+'-28', True), ('2020-02-28', True), (previous+'-05', False), ('2020-02-27', False)):
                for extension in extensions:
                    name = prefix + '_' + stamp + extension
                    (dest / name).write_text('old fixture')
                    if retained:
                        keep.add(name)
        for name in ('unrelated.sql.gz', 'testdb_not-a-date.sql.gz'):
            (dest / name).write_text('preserve')
            keep.add(name)
        env = dict(os.environ, BACKUPS_PATH=str(dest), SOURCE_PATH=str(source), SQL_PREFIX='testdb', ZIP_PREFIX='testcode', SQL_PASSWD='fixture', MYSQLDUMP_BIN=str(binaries/'dump'), TEST_DATE=today, PATH=str(binaries)+':'+os.environ['PATH'])
        result = subprocess.run(['bash', str(script)], env=env, stdout=subprocess.PIPE, stderr=subprocess.PIPE, universal_newlines=True)
        assert result.returncode == 0, result.stderr
        keep |= {'testdb_'+today+'.sql.gz', 'testcode_'+today+'.zip', '.backup.lock'}
        assert {p.name for p in dest.iterdir()} == keep
        assert 'CREATE TABLE fixture' in gzip.open(dest/('testdb_'+today+'.sql.gz'), 'rt').read()
        with zipfile.ZipFile(dest/('testcode_'+today+'.zip')) as archive:
            assert archive.testzip() is None
            names = {name.split(source.name+'/', 1)[1] for name in archive.namelist() if source.name+'/' in name}
            assert 'index.php' in names
            assert 'dist/required.js' in names
            assert 'modules/example/uploads/required.js' in names
            for optional in ('uploads/2026/10/photo.jpg', 'uploads/document.pdf', 'dist/GibbonEduCore-InstallBundle.tar.gz'):
                assert (optional in names) == today.endswith('-28'), (today, optional)
            if today.endswith('-28'):
                assert archive.read(next(name for name in archive.namelist() if name.endswith('/uploads/document.pdf'))) == (source/'uploads/document.pdf').read_bytes()
        sizes[today] = (dest/('testcode_'+today+'.zip')).stat().st_size
        checks += 1
        for failure in ({'DUMP_FAIL':'7'}, {'ZIP_BIN':str(binaries/'zip-fail')}):
            expired = dest / ('testdb_'+previous+'-05.sql.gz')
            expired.write_text('must survive failed run')
            old_sql = (dest/('testdb_'+today+'.sql.gz')).read_bytes()
            old_zip = (dest/('testcode_'+today+'.zip')).read_bytes()
            result = subprocess.run(['bash', str(script)], env=dict(env, **failure), stdout=subprocess.PIPE, stderr=subprocess.PIPE)
            assert result.returncode != 0
            assert expired.exists()
            assert (dest/('testcode_'+today+'.zip')).read_bytes() == old_zip
            if 'DUMP_FAIL' in failure:
                assert (dest/('testdb_'+today+'.sql.gz')).read_bytes() == old_sql
            assert not list(dest.glob('.sql-backup.*')) and not list(dest.glob('.source-backup.*'))
            expired.unlink()
            checks += 1
    import fcntl
    with open(dest/'.backup.lock', 'w') as lock:
        fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)
        result = subprocess.run(['bash', str(script)], env=env, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        assert result.returncode != 0 and b'Another backup' in result.stderr
        checks += 1
assert sizes['2026-10-24'] < sizes['2026-10-28']
print('PASS: daily exclusions, monthly full contents/readback, root-only matching, paths with spaces, retention, year boundary, failure safety and locking; %d scenarios' % checks)
print('Fixture ZIP bytes: '+str(sizes))
