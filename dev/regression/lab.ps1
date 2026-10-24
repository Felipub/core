param(
    [ValidateSet('prepare','install','start','stop','reset','verify','phpunit','codeception','browser','status')][string]$Action='status',
    [ValidateSet('master','manjeet','both')][string]$Target='both'
)
$ErrorActionPreference='Stop'
$labRoot=$PSScriptRoot
$repoRoot=(Resolve-Path (Join-Path $labRoot '../..')).Path
$runtimeRoot=Join-Path $labRoot '.runtime'
$refs=Get-Content (Join-Path $labRoot 'refs.json') -Raw | ConvertFrom-Json
$targets=if ($Target -eq 'both') { @('master','manjeet') } else { @($Target) }
function Run([string]$Program,[string[]]$Arguments) {
    & $Program @Arguments
    if ($LASTEXITCODE -ne 0) { throw "$Program failed ($LASTEXITCODE)" }
}
function Compose([string[]]$Arguments) { Run 'docker' (@('compose','--project-directory',$labRoot,'-f',(Join-Path $labRoot 'compose.yaml'))+$Arguments) }
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
        Run 'tar' @('-xf',$archive,'-C',$destination,'--exclude=.vscode','--exclude=*/error_log','--exclude=error_log','--exclude=mysqltosheets','--exclude=backup_script.sh')
        Remove-Item -LiteralPath $archive
        Copy-Item -LiteralPath (Join-Path $labRoot 'config.php') -Destination (Join-Path $destination 'config.php')
        foreach ($folder in @('var/sessions','var/tmp','uploads','resources/templates/cache','tests/log')) { New-Item -ItemType Directory -Force (Join-Path $destination $folder) | Out-Null }
        Set-Content -LiteralPath (Join-Path $destination 'SANDBOX_COMMIT') -Value $sha
    }
    Write-Output 'Snapshots ready. No dependencies or data installed yet.'
    exit
}
if ($Action -eq 'install') {
    Compose @('build','master')
    Compose @('pull','db-master','db-manjeet','browser-master','browser-manjeet')
    foreach ($targetName in $targets) {
        # Dependency download is a separate networked job. No application scripts, no private config.
        Run 'docker' @('run','--rm','--entrypoint','composer','-e','COMPOSER_ALLOW_SUPERUSER=1','-v',"${runtimeRoot}/${targetName}:/var/www/html",'core-regression-php:7.4.33','install','--no-interaction','--prefer-dist','--no-scripts','--no-plugins')
        Run 'docker' @('run','--rm','--network','none','-v',"${runtimeRoot}/${targetName}:/var/www/html",'core-regression-php:7.4.33','chown','-R','33:33','/var/www/html')
    }
    New-Item -ItemType Directory -Force (Join-Path $runtimeRoot 'tools') | Out-Null
    Copy-Item (Join-Path $labRoot 'package*.json') (Join-Path $runtimeRoot 'tools')
    Run 'docker' @('run','--rm','-v',"${runtimeRoot}/tools:/tools",'-w','/tools','mcr.microsoft.com/playwright:v1.56.1-noble','npm','ci','--ignore-scripts')
    Run 'docker' @('run','--rm','--network','none','-v',"${runtimeRoot}/tools:/tools",'mcr.microsoft.com/playwright:v1.56.1-noble','chmod','-R','a+rwX','/tools')
    exit
}
switch ($Action) {
    start { Compose (@('up','-d')+$targets) }
    stop { Compose @('down') } # Volumes retained. Never use down -v for ordinary stop.
    status { Compose @('ps') }
    reset {
        foreach ($targetName in $targets) {
            Compose @('stop',$targetName)
            Compose @('run','--rm','--no-deps',$targetName,'php','/harness/reset-storage.php')
            Compose @('up','-d',"db-$targetName")
            Compose @('run','--rm',$targetName,'php','/harness/reset.php')
            Compose @('up','-d',$targetName)
        }
    }
    verify {
        foreach ($targetName in $targets) {
            Compose @('exec','-T',$targetName,'php','/harness/verify.php')
            Compose @('run','--rm',"browser-$targetName",'node','/harness/browser-check.cjs')
        }
    }
    phpunit { foreach ($targetName in $targets) { Compose @('exec','-T','-w','/var/www/html/tests',$targetName,'../vendor/bin/phpunit','--configuration','phpunit.xml') } }
    codeception { foreach ($targetName in $targets) { Compose @('exec','-T','-w','/var/www/html/tests',$targetName,'../vendor/bin/codecept','--version') } }
    browser { foreach ($targetName in $targets) { Compose @('run','--rm',"browser-$targetName",'node','/harness/browser-check.cjs') } }
}
