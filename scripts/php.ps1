$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path $PSScriptRoot -Parent
$localIni = Join-Path $projectRoot '.tools\php.ini'
if (Test-Path -LiteralPath $localIni) {
    $previousPhprc = $env:PHPRC
    try {
        # Artisan starts child PHP processes; they must inherit this project's extensions.
        $env:PHPRC = $localIni
        & php -c $localIni @args
        $result = $LASTEXITCODE
    } finally {
        $env:PHPRC = $previousPhprc
    }
} else {
    & php @args
    $result = $LASTEXITCODE
}
exit $result
