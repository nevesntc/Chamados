$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path $PSScriptRoot -Parent
$localIni = Join-Path $projectRoot '.tools\php.ini'
if (Test-Path -LiteralPath $localIni) {
    & php -c $localIni @args
} else {
    & php @args
}
exit $LASTEXITCODE
