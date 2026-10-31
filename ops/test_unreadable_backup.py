"""Reproduce the hosting failure without root privileges or a real database."""
import os
from pathlib import Path
import subprocess
import sys
import tempfile
import zipfile

assert os.geteuid() != 0, 'Run this regression test as an unprivileged user'
script = Path(__file__).with_name('backup_script.sh')
expect_failure = '--expect-original-failure' in sys.argv
with tempfile.TemporaryDirectory(prefix='unreadable-backup-') as tmp:
    root = Path(tmp)
    source = root/'source'
    (source/'mysqltosheets').mkdir(parents=True)
    (source/'index.php').write_text('application code')
    (source/'mysqltosheets/.htaccess').write_text('active access rules')
    (source/'mysqltosheets/config.php').write_text('integration configuration fixture')
    ignored = source/'mysqltosheets/.htaccess.cpmh3129'
    ignored.write_text('disabled hosting-generated old configuration fixture')
    ignored.chmod(0)
    binaries = root/'bin'
    binaries.mkdir()
    for name, text in {
        'dump': '#!/bin/bash\necho "CREATE TABLE fixture (id INT);"\n',
        'date': '#!/bin/bash\nif [[ "$1" == +%F ]]; then echo "$TEST_DATE"; else /bin/date "$@"; fi\n',
    }.items():
        path = binaries/name
        path.write_text(text)
        path.chmod(0o700)
    try:
        for day in ('2026-10-31', '2026-11-28'):
            output = root/day
            output.mkdir()
            env = dict(os.environ, SOURCE_PATH=str(source), BACKUPS_PATH=str(output), SQL_PREFIX='fixturedb', SQL_PASSWD='fixture', ZIP_PREFIX='fixturecode', MYSQLDUMP_BIN=str(binaries/'dump'), TEST_DATE=day, PATH=str(binaries)+':'+os.environ['PATH'])
            result = subprocess.run(['bash',str(script)], env=env, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
            if expect_failure:
                assert result.returncode == 18, (result.returncode, result.stdout, result.stderr)
                assert (output/('fixturedb_'+day+'.sql.gz')).exists()
                assert not (output/('fixturecode_'+day+'.zip')).exists()
                print('REPRODUCED: unreadable cPanel file => zip exit 18, SQL exists, final ZIP absent:',day)
                continue
            assert result.returncode == 0, (result.returncode,result.stdout,result.stderr)
            with zipfile.ZipFile(output/('fixturecode_'+day+'.zip')) as archive:
                assert archive.testzip() is None
                names=archive.namelist()
                assert not any(n.endswith('/.htaccess.cpmh3129') for n in names)
                for required in ('/index.php','/mysqltosheets/.htaccess','/mysqltosheets/config.php'):
                    assert any(n.endswith(required) for n in names)
            unexpected=source/'important.php'
            unexpected.write_text('required application file')
            unexpected.chmod(0)
            try:
                before=(output/('fixturecode_'+day+'.zip')).read_bytes()
                result=subprocess.run(['bash',str(script)],env=env,stdout=subprocess.PIPE,stderr=subprocess.PIPE)
                assert result.returncode != 0
                assert b'Source ZIP failed' in result.stderr
                assert b'Permission denied' in (output/'.source-backup-last.log').read_bytes()
                assert (output/('fixturecode_'+day+'.zip')).read_bytes() == before
            finally:
                unexpected.chmod(0o600)
                unexpected.unlink()
            print('PASS: daily/monthly ZIP, active configuration preserved, unrelated unreadable file still fails:',day)
    finally:
        ignored.chmod(0o600)
