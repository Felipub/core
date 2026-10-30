param(
    [ValidateSet('prepare','install','stage','start','stop','reset','verify','phpunit','integration','codeception','browser','browser-tests','codeception-tests','status')][string]$Action='status',
    [ValidateSet('master','manjeet','both')][string]$Target='both',
    [switch]$Functional
)
$ErrorActionPreference='Stop'
$labRoot=$PSScriptRoot
$repoRoot=(Resolve-Path (Join-Path $labRoot '../..')).Path
$runtimeRoot=Join-Path $labRoot '.runtime'
$composeFile=Join-Path $labRoot 'compose.yaml'
if ($Functional) {
    $runtimeRoot=Join-Path $labRoot '.runtime-tests'
    New-Item -ItemType Directory -Force $runtimeRoot | Out-Null
    # Separate project, bind mounts, published ports and volumes from restored copies.
    $composeText=(Get-Content $composeFile -Raw).Replace('name: core-regression','name: core-functional-tests').Replace('./.runtime/','./.runtime-tests/').Replace('127.0.0.1:8081:8081','127.0.0.1:8181:8081').Replace('127.0.0.1:8082:8082','127.0.0.1:8182:8082').Replace('TEST_ENV: codeception',"TEST_ENV: codeception`n    REGRESSION_DISPOSABLE: functional-tests")
    if (!$composeText.StartsWith('name: core-functional-tests') -or $composeText.Contains('./.runtime/') -or $composeText.Contains('127.0.0.1:8081:8081') -or $composeText.Contains('127.0.0.1:8082:8082') -or !$composeText.Contains('REGRESSION_DISPOSABLE: functional-tests')) {
        throw 'Disposable Compose transformation failed; refusing to use restored paths or project.'
    }
    $composeText=$composeText.Replace('./.runtime-tests/master:/var/www/html','app-master:/var/www/html').Replace('./.runtime-tests/manjeet:/var/www/html','app-manjeet:/var/www/html')
    $composeFile=Join-Path $runtimeRoot 'compose.yaml'
    if (!(Test-Path -LiteralPath $composeFile) -or [IO.File]::ReadAllText($composeFile) -ne $composeText) {
        [IO.File]::WriteAllText($composeFile,$composeText)
    }
}
if ($Action -in @('install','stage','reset','phpunit','integration','browser-tests','codeception-tests') -and !$Functional) {
    throw 'Use -Functional: reset and runners require the disposable test project; restored databases are preserved.'
}
$harnessRoot=Join-Path $runtimeRoot 'harness'
New-Item -ItemType Directory -Force $harnessRoot | Out-Null
foreach ($toolFile in @('reset-storage.php','reset.php','custom-fields.json','verify.php','fixture-manifest.php','phpunit.xml','integration.xml','codeception.yml','browser-check.cjs','playwright.config.cjs','stage.sh','stage-check.php','config.php','RegressionTester.php')) {
    $sourceTool=Join-Path $labRoot $toolFile
    $mountedTool=Join-Path $harnessRoot $toolFile
    if (!(Test-Path -LiteralPath $mountedTool) -or (Get-FileHash -LiteralPath $sourceTool).Hash -ne (Get-FileHash -LiteralPath $mountedTool).Hash) {
        Copy-Item -LiteralPath $sourceTool -Destination $mountedTool
    }
}
$refs=Get-Content (Join-Path $labRoot 'refs.json') -Raw | ConvertFrom-Json
$targets=if ($Target -eq 'both') { @('master','manjeet') } else { @($Target) }
function Run([string]$Program,[string[]]$Arguments) {
    if ($Program -eq 'docker' -and $env:OS -eq 'Windows_NT') {
        $endpoint=(& docker context inspect desktop-linux --format '{{.Endpoints.docker.Host}}') -join ''
        if ($LASTEXITCODE -ne 0 -or $endpoint -ne 'npipe:////./pipe/dockerDesktopLinuxEngine') { throw 'Local Docker Desktop Linux engine required' }
        $Arguments=@('--context','desktop-linux')+$Arguments
    }
    & $Program @Arguments
    if ($LASTEXITCODE -ne 0) { throw "$Program failed ($LASTEXITCODE)" }
}
function Compose([string[]]$Arguments) { Run 'docker' (@('compose','--project-directory',$labRoot,'-f',$composeFile)+$Arguments) }
if ($Action -eq 'prepare') {
    New-Item -ItemType Directory -Force $runtimeRoot | Out-Null
    foreach ($targetName in $targets) {
        $destination=Join-Path $runtimeRoot $targetName
        if (Test-Path -LiteralPath $destination) { throw "$destination already exists; preserving it. Use reset for data, or rename it before preparing again." }
        $sha=$refs.$targetName
        Run 'git' @('-C',$repoRoot,'cat-file','-e',"$sha^{commit}")
        $archive=Join-Path $runtimeRoot "$targetName.tar"
        Run 'git' @('-C',$repoRoot,'archive','--format=tar',"--output=$archive",$sha)
        New-Item -ItemType Directory $destination | Out-Null
        Run 'tar' @('-xf',$archive,'-C',$destination,'--exclude=.vscode','--exclude=*/error_log','--exclude=error_log','--exclude=mysqltosheets','--exclude=backup_script.sh','--exclude=*.sql*','--exclude=*.csv','--exclude=*.xlsx')
        # Only the reviewed empty application schema/metadata is permitted, never exported data.
        Run 'git' @('-C',$repoRoot,'archive','--format=tar',"--output=$archive",$sha,'gibbon.sql.gz')
        Run 'tar' @('-xf',$archive,'-C',$destination)
        Remove-Item -LiteralPath $archive
        Copy-Item -LiteralPath (Join-Path $labRoot 'config.php') -Destination (Join-Path $destination 'config.php')
        Copy-Item -LiteralPath (Join-Path $labRoot 'RegressionTester.php') -Destination (Join-Path $destination 'tests/config/support/RegressionTester.php')
        foreach ($folder in @('var/sessions','var/tmp','uploads','resources/templates/cache','tests/log')) { New-Item -ItemType Directory -Force (Join-Path $destination $folder) | Out-Null }
        Set-Content -LiteralPath (Join-Path $destination 'SANDBOX_COMMIT') -Value $sha
    }
    Write-Output 'Snapshots ready. No dependencies or data installed yet.'
    exit
}
if ($Action -eq 'install') {
    Compose @('build','master')
    Compose @('pull','db-master','db-manjeet','browser-master','browser-manjeet','gateway-master','gateway-manjeet')
    foreach ($targetName in $targets) {
        Run (Get-Process -Id $PID).Path @('-NoProfile','-ExecutionPolicy','Bypass','-File',$PSCommandPath,'stage','-Target',$targetName,'-Functional')
    }
    New-Item -ItemType Directory -Force (Join-Path $runtimeRoot 'tools') | Out-Null
    New-Item -ItemType Directory -Force (Join-Path $runtimeRoot 'tools/tests') | Out-Null
    foreach ($targetName in $targets) {New-Item -ItemType Directory -Force (Join-Path $runtimeRoot "browser-results/$targetName") | Out-Null}
    Copy-Item (Join-Path $labRoot 'package*.json') (Join-Path $runtimeRoot 'tools')
    Run 'docker' @('run','--rm','-v',"${runtimeRoot}/tools:/tools",'-w','/tools',$refs.images.playwright,'npm','ci','--ignore-scripts')
    Run 'docker' @('run','--rm','--network','none','-v',"${runtimeRoot}/tools:/tools",$refs.images.playwright,'chmod','-R','a+rwX','/tools')
    exit
}
if ($Action -eq 'stage') {
    foreach ($targetName in $targets) {
        $snapshot=Join-Path $runtimeRoot $targetName
        $sha=$refs.$targetName
        if ((Get-Content (Join-Path $snapshot 'SANDBOX_COMMIT') -Raw).Trim() -ne $sha) {throw 'Snapshot commit mismatch'}
        $resultsRoot=Join-Path $runtimeRoot "browser-results/$targetName"
        $reportsRoot=Join-Path $snapshot 'tests/log'
        New-Item -ItemType Directory -Force $resultsRoot,$reportsRoot | Out-Null
        Run 'docker' @('run','--rm','--network','none','-v',"${resultsRoot}:/results",'-v',"${reportsRoot}:/reports",'core-regression-php:7.4.33','chmod','a+rwx','/results','/reports')
        $volume="core-functional-tests_app-$targetName"
        $existing=(Run 'docker' @('volume','ls','--filter',"name=^${volume}$",'--format','{{.Name}}')) -join ''
        if ($existing) {
            $info=((Run 'docker' @('volume','inspect',$volume)) -join "`n") | ConvertFrom-Json
            $label=$info[0].Labels.'core.functional.commit'
            $marker=(Run 'docker' @('run','--rm','--network','none','-v',"${volume}:/staged:ro",'-v',"${harnessRoot}:/harness:ro",'core-regression-php:7.4.33','php','/harness/stage-check.php')) -join ''
            if ($label -ne $sha -or $marker -ne $sha) {throw "Existing stage is incomplete or has another commit: $volume. Preserve/review it before recreating."}
            Write-Output "${targetName}: pinned native application volume ready"
            continue
        }
        $archive=Join-Path $runtimeRoot "$targetName-app.tar"
        $schemaArchive=Join-Path $runtimeRoot "$targetName-schema.tar"
        Run 'git' @('-C',$repoRoot,'archive','--format=tar',"--output=$archive",$sha)
        Run 'git' @('-C',$repoRoot,'archive','--format=tar',"--output=$schemaArchive",$sha,'gibbon.sql.gz')
        Run 'docker' @('volume','create','--label',"core.functional.commit=$sha",$volume)
        Run 'docker' @('run','--rm','--network','none','-e',"EXPECTED_COMMIT=$sha",'-v',"${volume}:/var/www/html",'-v',"${archive}:/snapshot.tar:ro",'-v',"${schemaArchive}:/schema.tar:ro",'-v',"${harnessRoot}:/harness:ro",'core-regression-php:7.4.33','sh','/harness/stage.sh')
        # Download locked dependencies in a separate job. Never execute application scripts/plugins.
        Run 'docker' @('run','--rm','--entrypoint','composer','-e','COMPOSER_ALLOW_SUPERUSER=1','-v',"${volume}:/var/www/html",'core-regression-php:7.4.33','install','--no-interaction','--prefer-dist','--no-scripts','--no-plugins')
        Run 'docker' @('run','--rm','--network','none','-v',"${volume}:/var/www/html",'core-regression-php:7.4.33','sh','-ec','composer check-platform-reqs; chown -R 33:33 /var/www/html; touch .FUNCTIONAL_STAGE_COMPLETE')
        Remove-Item -LiteralPath $archive
        Remove-Item -LiteralPath $schemaArchive
        Write-Output "$targetName staged on its disposable native volume"
    }
    exit
}
switch ($Action) {
    start { Compose (@('up','-d')+@($targets|ForEach-Object {"gateway-$_"})) }
    stop { Compose @('--profile','tools','down') } # Include stopped fixture sidecars; keep volumes.
    status { Compose @('ps') }
    reset {
        foreach ($targetName in $targets) {
            Compose @('stop',$targetName)
            Compose @('run','--rm','--no-deps',$targetName,'php','/harness/reset-storage.php')
            Compose @('up','-d',"db-$targetName")
            Compose @('run','--rm',$targetName,'php','/harness/reset.php')
            Compose @('run','--rm',$targetName,'php','/regression-tests/data/seed.php')
            Compose @('up','-d',$targetName,"gateway-$targetName")
        }
    }
    verify {
        $baselineManifest=$null
        foreach ($targetName in $targets) {
            $project=if ($Functional) {'core-functional-tests'} else {'core-regression'}
            $privateNetwork="${project}_$targetName"
            $isInternal=(Run 'docker' @('network','inspect',$privateNetwork,'--format','{{.Internal}}')) -join ''
            if ($isInternal -ne 'true') { throw "Network is not internal: $privateNetwork" }
            $appId=(Compose @('ps','-q',$targetName)) -join ''
            $appNetworks=(Run 'docker' @('inspect','--format','{{json .NetworkSettings.Networks}}',$appId)) | ConvertFrom-Json
            $networkNames=@($appNetworks.PSObject.Properties.Name)
            if ($networkNames.Count -ne 1 -or $networkNames[0] -ne $privateNetwork) { throw "Unexpected application network: $targetName" }
            $dbId=(Compose @('ps','-q',"db-$targetName")) -join ''
            $bindings=(Run 'docker' @('inspect','--format','{{json .HostConfig.PortBindings}}',$dbId)) -join ''
            if ($bindings -notin @('null','{}')) { throw "Database ports published: $targetName" }
            $port=if ($targetName -eq 'master') {8081} else {8082}
            if ($Functional) { $port+=100 }
            $response=$null
            foreach ($attempt in 1..15) {
                try { $response=Invoke-WebRequest -Uri "http://127.0.0.1:$port/" -UseBasicParsing -TimeoutSec 10; break }
                catch { if ($attempt -eq 15) { throw }; Start-Sleep -Seconds 1 }
            }
            if ($response.StatusCode -ne 200) { throw "Host HTTP failed: $targetName" }
            $manifest=(Compose @('exec','-T',$targetName,'php','/harness/fixture-manifest.php')) -join "`n"
            if ($null -eq $baselineManifest) { $baselineManifest=$manifest } elseif ($baselineManifest -ne $manifest) { throw 'Fixture/schema mismatch between branches. Reset both before verifying.' }
            Compose @('exec','-T',$targetName,'php','/harness/verify.php')
            Compose @('run','--rm',"browser-$targetName",'node','/harness/browser-check.cjs')
        }
    }
    phpunit { foreach ($targetName in $targets) { Compose @('exec','-T','-w','/var/www/html/tests',$targetName,'../vendor/bin/phpunit','--configuration','/harness/phpunit.xml') } }
    integration { foreach ($targetName in $targets) { Compose @('exec','-T','-w','/var/www/html',$targetName,'vendor/bin/phpunit','--configuration','/harness/integration.xml') } }
    codeception { foreach ($targetName in $targets) { Compose @('exec','-T','-w','/var/www/html/tests',$targetName,'../vendor/bin/codecept','--version') } }
    browser { foreach ($targetName in $targets) { Compose @('run','--rm',"browser-$targetName",'node','/harness/browser-check.cjs') } }
    browser-tests {
        foreach ($targetName in $targets) {
            Compose @('up','-d','--force-recreate',"fixture-$targetName")
            try { Compose @('run','--rm',"browser-$targetName",'npx','playwright','test','--config=/harness/playwright.config.cjs') }
            finally { Compose @('stop',"fixture-$targetName") }
        }
    }
    codeception-tests { foreach ($targetName in $targets) { Compose @('exec','-T',$targetName,'vendor/bin/codecept','run','--config','/harness/codeception.yml') } }
}
