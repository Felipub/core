param([string]$SourceDump,[string]$BaseDump,[string]$Output)
$ErrorActionPreference='Stop'
function Read-DDL([string]$Path) {
    $stream=[IO.File]::OpenRead((Resolve-Path -LiteralPath $Path))
    $gzip=[IO.Compression.GzipStream]::new($stream,[IO.Compression.CompressionMode]::Decompress)
    $reader=[IO.StreamReader]::new($gzip)
    $tables=@{}
    try {
        while ($null -ne ($line=$reader.ReadLine())) {
            if ($line -match '^CREATE TABLE (?:IF NOT EXISTS )?`(gibbon[A-Za-z0-9]+)`') {
                $name=$Matches[1]
                $ddl=[Text.StringBuilder]::new()
                [void]$ddl.AppendLine($line)
                while (!$line.TrimEnd().EndsWith(';')) {
                    $line=$reader.ReadLine()
                    if ($null -eq $line) {throw 'Incomplete DDL'}
                    [void]$ddl.AppendLine($line)
                }
                $tables[$name]=$ddl.ToString()
            }
        }
    } finally {$reader.Dispose();$gzip.Dispose();$stream.Dispose()}
    return $tables
}
$source=Read-DDL $SourceDump
$baseline=Read-DDL $BaseDump
$result=[Collections.Generic.List[string]]::new()
$result.Add('-- DDL ONLY: missing deployed schema, extracted from the local 2026-10-23 backup.')
$result.Add('-- No INSERTs, data rows, credentials, triggers or routines.')
$result.Add('-- Source SHA256: '+(Get-FileHash -LiteralPath $SourceDump -Algorithm SHA256).Hash.ToLower())
$missingTables=0;$missingColumns=0
foreach ($table in ($source.Keys | Sort-Object)) {
    if (!$baseline.ContainsKey($table)) {
        # Strip volatile auto increment counters, retaining structure only.
        $result.Add(($source[$table] -replace 'AUTO_INCREMENT=\d+\s*',''))
        $missingTables++
        continue
    }
    $existing=@{}
    foreach ($match in [regex]::Matches($baseline[$table],'(?m)^\s+`([A-Za-z0-9_]+)` ')) {$existing[$match.Groups[1].Value]=$true}
    foreach ($line in ($source[$table] -split '\r?\n')) {
        if ($line -match '^\s+`([A-Za-z0-9_]+)` ' -and !$existing.ContainsKey($Matches[1])) {
            $result.Add('ALTER TABLE `'+$table+'` ADD COLUMN '+$line.Trim().TrimEnd(',')+';')
            $missingColumns++
        }
    }
}
[IO.File]::WriteAllText($Output,($result -join "`n"),[Text.UTF8Encoding]::new($false))
Write-Output "DDL only: $missingTables missing tables; $missingColumns missing columns."
