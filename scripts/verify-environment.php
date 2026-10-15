<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    if (! $app->environment('local')) {
        throw new RuntimeException('Verification is restricted to the local environment.');
    }
    foreach (['curl', 'fileinfo', 'mbstring', 'openssl', 'pdo_mysql', 'pdo_sqlite', 'sqlite3', 'intl', 'zip'] as $extension) {
        if (! extension_loaded($extension)) {
            throw new RuntimeException("Missing extension: {$extension}");
        }
    }
    foreach (['database.default' => 'mariadb', 'mail.default' => 'log', 'queue.default' => 'sync', 'cache.default' => 'database', 'session.driver' => 'database'] as $setting => $expected) {
        if (config($setting) !== $expected) {
            throw new RuntimeException("Unexpected setting: {$setting}");
        }
    }
    $version = Illuminate\Support\Facades\DB::selectOne('select version() as version')->version;
    $key = 'mitamura-environment-verification';
    $mode = $argv[1] ?? 'check';
    if ($mode === 'write') {
        Illuminate\Support\Facades\Cache::put($key, 'persistence-check', 3600);
    } elseif ($mode === 'read') {
        if (Illuminate\Support\Facades\Cache::get($key) !== 'persistence-check') {
            throw new RuntimeException('DB persistence marker was not retained.');
        }
    } elseif ($mode === 'cleanup') {
        Illuminate\Support\Facades\Cache::forget($key);
    } elseif ($mode !== 'check') {
        throw new RuntimeException('Unknown verification mode.');
    }
    foreach ([storage_path('framework'), base_path('bootstrap/cache')] as $directory) {
        $file = tempnam($directory, 'verification-');
        if ($file === false || file_put_contents($file, 'ok') !== 2) {
            throw new RuntimeException('Runtime directory is not writable.');
        }
        unlink($file);
    }
    echo json_encode(['result' => 'PASS', 'php' => PHP_VERSION, 'laravel' => $app->version(), 'database' => $version, 'extensions' => 'PASS', 'runtime_write' => 'PASS', 'mail' => 'log', 'mode' => $mode], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT).PHP_EOL;
} catch (Throwable $error) {
    // Connection exceptions can contain credentials, so output only the error class.
    fwrite(STDERR, 'FAIL: '.get_class($error).'. Check local configuration privately.'.PHP_EOL);
    exit(1);
}
