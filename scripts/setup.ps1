$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path $PSScriptRoot -Parent
Set-Location -LiteralPath $projectRoot
$phpExecutable = (Get-Command php -ErrorAction Stop).Source
$phpRoot = Split-Path $phpExecutable -Parent
$toolsPath = Join-Path $projectRoot '.tools'
New-Item -ItemType Directory -Force -Path $toolsPath | Out-Null
$localIni = Join-Path $toolsPath 'php.ini'
if (-not (Test-Path -LiteralPath $localIni)) {
    $loadedIni = & php -r 'echo php_ini_loaded_file();'
    if ($loadedIni -and (Test-Path -LiteralPath $loadedIni)) { Copy-Item -LiteralPath $loadedIni -Destination $localIni }
    else { Set-Content -LiteralPath $localIni -Value 'memory_limit=512M' }
    $extensionPath = Join-Path $phpRoot 'ext'
    Add-Content -LiteralPath $localIni -Value ('extension_dir="' + $extensionPath + '"')
    $loadedModules = & php -m
    foreach ($module in @('mbstring', 'openssl', 'curl', 'fileinfo', 'zip', 'pdo_sqlite', 'sqlite3')) {
        if ($loadedModules -notcontains $module) {
            $dllPath = Join-Path $extensionPath ('php_' + $module + '.dll')
            if (-not (Test-Path -LiteralPath $dllPath)) { throw "Extensão PHP ausente: $dllPath" }
            Add-Content -LiteralPath $localIni -Value ('extension=' + $module)
        }
    }
}
$env:PHPRC = $localIni
$env:COMPOSER_HOME = Join-Path $toolsPath 'composer-home'
$composerPath = Join-Path $toolsPath 'composer.phar'
if (-not (Test-Path -LiteralPath $composerPath)) {
    Invoke-WebRequest 'https://getcomposer.org/download/latest-stable/composer.phar' -OutFile $composerPath
    $expectedHash = (Invoke-WebRequest 'https://getcomposer.org/download/latest-stable/composer.phar.sha256sum').Content.Split(' ')[0].Trim()
    if ((Get-FileHash -LiteralPath $composerPath -Algorithm SHA256).Hash.ToLower() -ne $expectedHash) {
        Remove-Item -LiteralPath $composerPath
        throw 'O checksum do Composer não corresponde. Download descartado.'
    }
}
& php $composerPath install --no-interaction --prefer-dist
if ($LASTEXITCODE -ne 0) { throw 'Falha ao instalar dependências PHP.' }
& php $composerPath run setup
if ($LASTEXITCODE -ne 0) { throw 'Falha no setup. Confira a saída acima.' }
Write-Host 'Pronto. Execute .\scripts\php.ps1 artisan serve --host=127.0.0.1 --port=8000'
