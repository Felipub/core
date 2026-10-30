param(
    [ValidateSet('master','manjeet','both')][string]$Target='master',
    [switch]$KeepRunning
)
$ErrorActionPreference='Stop'
$lab=Join-Path $PSScriptRoot 'lab.ps1'
$runtime=Join-Path $PSScriptRoot '.runtime-tests'
$refs=Get-Content (Join-Path $PSScriptRoot 'refs.json') -Raw | ConvertFrom-Json
$targets=if ($Target -eq 'both') {@('master','manjeet')} else {@($Target)}
$shell=(Get-Process -Id $PID).Path
$outcomes=[Collections.Generic.List[object]]::new()
foreach ($version in $targets) {
    $snapshot=Join-Path $runtime $version
    if (!(Test-Path (Join-Path $snapshot 'SANDBOX_COMMIT'))) {throw 'Prepare the disposable snapshots first; see tests/regression/README.md'}
    if ((Get-Content (Join-Path $snapshot 'SANDBOX_COMMIT') -Raw).Trim() -ne $refs.$version) {throw "Snapshot commit mismatch: $version"}
}
try {
    foreach ($version in $targets) {
        foreach ($step in @('stage','start','reset','phpunit','integration','browser-tests')) {
            Write-Output "=== $version / $step ==="
            $started=[DateTime]::UtcNow
            & $shell -NoProfile -ExecutionPolicy Bypass -File $lab $step -Target $version -Functional
            $code=$LASTEXITCODE
            $outcomes.Add([pscustomobject]@{target=$version;commit=$refs.$version;suite=$step;exitCode=$code;startedUTC=$started.ToString('o');seconds=[math]::Round(([DateTime]::UtcNow-$started).TotalSeconds,2)})
            # Application test failures must not hide results from other suites.
            if ($code -ne 0 -and $step -in @('stage','start','reset')) {throw "Environment preparation failed: $step"}
        }
    }
} finally {
    if (!$KeepRunning) {& $shell -NoProfile -ExecutionPolicy Bypass -File $lab stop -Functional}
    $outcomes | ConvertTo-Json -Depth 4 | Set-Content -LiteralPath (Join-Path $runtime 'results-summary.json') -Encoding UTF8
    foreach ($version in $targets) {
        @($outcomes | Where-Object target -eq $version) | ConvertTo-Json -Depth 4 | Set-Content -LiteralPath (Join-Path $runtime "results-summary-$version.json") -Encoding UTF8
    }
    & $shell -NoProfile -ExecutionPolicy Bypass -File (Join-Path $PSScriptRoot 'collect-results.ps1') -Target $Target
    $reportExit=$LASTEXITCODE
}
if ($reportExit -ne 0 -or @($outcomes | Where-Object exitCode -ne 0).Count) {exit 1}
