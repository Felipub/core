param([ValidateSet('master','manjeet','both')][string]$Target='master')
$ErrorActionPreference='Stop'
$runtime=Join-Path $PSScriptRoot '.runtime-tests'
$refs=Get-Content (Join-Path $PSScriptRoot 'refs.json') -Raw | ConvertFrom-Json
$targets=if ($Target -eq 'both') {@('master','manjeet')} else {@($Target)}
$rows=@(foreach ($version in $targets) {
    foreach ($suite in @('unit','integration','browser')) {
        $relative=if ($suite -eq 'browser') {"browser-results/$version/browser.xml"} else {"$version/tests/log/$suite.xml"}
        $path=Join-Path $runtime $relative
        if (!(Test-Path -LiteralPath $path)) {
            [pscustomobject]@{target=$version;commit=$refs.$version;suite=$suite;status='not-run';tests=0;passed=0;failures=0;errors=0;skipped=0}
            continue
        }
        [xml]$document=Get-Content -LiteralPath $path -Raw
        $node=$document.DocumentElement
        if (!$node.HasAttribute('tests')) {$node=$document.SelectSingleNode('/testsuites/testsuite')}
        if (!$node -or !$node.HasAttribute('tests')) {throw "Unrecognized JUnit report: $path"}
        $tests=[int]$node.GetAttribute('tests');$failures=[int]$node.GetAttribute('failures');$errors=[int]$node.GetAttribute('errors');$skipped=[int]$node.GetAttribute('skipped')
        [pscustomobject]@{target=$version;commit=$refs.$version;suite=$suite;status='executed';tests=$tests;passed=($tests-$failures-$errors-$skipped);failures=$failures;errors=$errors;skipped=$skipped}
    }
})
$complete=@($rows | Where-Object status -ne 'executed').Count -eq 0
$green=$complete -and @($rows | Where-Object { $_.failures -gt 0 -or $_.errors -gt 0 -or $_.skipped -gt 0 }).Count -eq 0
$summary=[pscustomobject]@{collectedUTC=[DateTime]::UtcNow.ToString('o');complete=$complete;green=$green;suites=$rows}
$json=$summary | ConvertTo-Json -Depth 5
[IO.File]::WriteAllText((Join-Path $runtime 'test-results.json'),$json,[Text.UTF8Encoding]::new($false))
$rows | Format-Table target,suite,status,tests,passed,failures,errors,skipped
if (!$green) {exit 1}
