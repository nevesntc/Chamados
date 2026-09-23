<?php

declare(strict_types=1);

// A small, explicit backup tool for this deployment. Keep the key outside Git.
$root = dirname(__DIR__);
$private = $root.'/.tools';
$credentialFile = $private.'/runtime-credential.json';
$keyFile = $private.'/backup-key.base64';
$backupDir = $private.'/backups';
$mode = $argv[1] ?? '';
if (! in_array($mode, ['create', 'verify', 'decrypt'], true)) {
    fwrite(STDERR, "Uso: php scripts/backup-production.php create|verify <backup>|decrypt <backup> <destino>\n");
    exit(2);
}

if (! is_dir($backupDir) && ! mkdir($backupDir, 0700, true) && ! is_dir($backupDir)) {
    throw new RuntimeException('Não foi possível criar o diretório privado de backups.');
}
if (! is_file($keyFile)) {
    if ($mode !== 'create') {
        throw new RuntimeException('Chave de backup ausente.');
    }
    if (file_put_contents($keyFile, base64_encode(random_bytes(32)), LOCK_EX) === false) {
        throw new RuntimeException('Não foi possível guardar a chave de backup.');
    }
}
$key = base64_decode(trim(file_get_contents($keyFile)), true);
if ($key === false || strlen($key) !== 32) {
    throw new RuntimeException('Chave de backup inválida.');
}

$decrypt = static function (string $encrypted) use ($key): string {
    if (strlen($encrypted) < 37 || substr($encrypted, 0, 5) !== 'CHBK1') {
        throw new RuntimeException('Formato de backup desconhecido.');
    }
    $nonce = substr($encrypted, 5, 12);
    $tag = substr($encrypted, 17, 16);
    $plaintext = openssl_decrypt(substr($encrypted, 33), 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag);
    if ($plaintext === false) {
        throw new RuntimeException('Falha na autenticação do backup: chave incorreta ou arquivo alterado.');
    }

    return $plaintext;
};

$run = static function (array $command, ?array $environment = null): void {
    $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, $environment);
    if (! is_resource($process)) {
        throw new RuntimeException('Não foi possível iniciar PostgreSQL client.');
    }
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    if (proc_close($process) !== 0) {
        throw new RuntimeException('Ferramenta PostgreSQL falhou: '.trim($stderr ?: $stdout));
    }
};

$restoreBin = getenv('PG_RESTORE_BIN') ?: 'pg_restore';
$verifyArchive = static function (string $archive) use ($run, $restoreBin): void {
    $temporary = tempnam(sys_get_temp_dir(), 'chamados-restore-');
    try {
        if (file_put_contents($temporary, $archive) === false) {
            throw new RuntimeException('Não foi possível verificar o arquivo restaurado.');
        }
        $run([$restoreBin, '--list', $temporary]);
    } finally {
        @unlink($temporary);
    }
};

if ($mode === 'create') {
    if (! is_file($credentialFile)) {
        throw new RuntimeException('Credencial de runtime local ausente.');
    }
    $database = json_decode(file_get_contents($credentialFile), true, 512, JSON_THROW_ON_ERROR);
    $dumpBin = getenv('PG_DUMP_BIN') ?: 'pg_dump';
    $temporary = tempnam($backupDir, 'dump-');
    try {
        $environment = array_merge(getenv(), [
            'PGPASSWORD' => $database['password'],
            'PGSSLMODE' => 'require',
        ]);
        $run([
            $dumpBin, '--host='.$database['host'], '--port='.(string) $database['port'],
            '--username='.$database['username'], '--dbname='.$database['database'],
            '--schema=chamados', '--format=custom', '--no-owner', '--no-acl', '--file='.$temporary,
        ], $environment);
        if (filesize($temporary) > 256 * 1024 * 1024) {
            throw new RuntimeException('Backup acima do limite de 256 MiB deste utilitário.');
        }
        $plain = file_get_contents($temporary);
        if ($plain === false) {
            throw new RuntimeException('Backup ilegível.');
        }
        $nonce = random_bytes(12);
        $cipher = openssl_encrypt($plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag);
        if ($cipher === false) {
            throw new RuntimeException('Falha ao cifrar o backup.');
        }
        $output = $backupDir.'/chamados-'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(3)).'.dump.enc';
        if (file_put_contents($output, 'CHBK1'.$nonce.$tag.$cipher, LOCK_EX) === false) {
            throw new RuntimeException('Não foi possível gravar o backup cifrado.');
        }
        unset($plain, $cipher);
        $verifyArchive($decrypt(file_get_contents($output)));
        echo "Backup cifrado criado e arquivo pg_restore verificado: {$output}\n";
        echo "Chave privada separada: {$keyFile}\n";
    } finally {
        @unlink($temporary);
    }
    exit(0);
}

$input = $argv[2] ?? '';
if (! is_file($input)) {
    throw new RuntimeException('Arquivo de backup ausente.');
}
if (filesize($input) > 256 * 1024 * 1024 + 64) {
    throw new RuntimeException('Backup acima do limite de 256 MiB deste utilitário.');
}
$plain = $decrypt(file_get_contents($input));
if ($mode === 'verify') {
    $verifyArchive($plain);
    echo "Backup autenticado e formato pg_restore válido.\n";
    exit(0);
}

$destination = $argv[3] ?? '';
if ($destination === '' || file_exists($destination)) {
    throw new RuntimeException('Informe um caminho de destino inexistente para o dump decifrado.');
}
if (file_put_contents($destination, $plain, LOCK_EX) === false) {
    throw new RuntimeException('Não foi possível gravar o dump decifrado.');
}
echo "Dump decifrado em {$destination}. Proteja e apague após restaurar em ambiente isolado.\n";
