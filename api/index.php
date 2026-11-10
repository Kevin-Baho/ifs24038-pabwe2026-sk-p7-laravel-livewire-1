<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

define('LARAVEL_START', microtime(true));

require __DIR__.'/../vendor/autoload.php';

if (getenv('VERCEL')) {
    // Jika DB_HOST belum diisi di Vercel, jangan crash 500: pakai SQLite sementara.
    if (! getenv('DB_HOST')) {
        foreach (['DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => '/tmp/database.sqlite'] as $k => $v) {
            putenv("$k=$v");
            $_ENV[$k] = $_SERVER[$k] = $v;
        }
    }

    // Diagnosa koneksi DB: aktifkan env DB_CHECK=1 lalu buka /db-check
    if (getenv('DB_CHECK') && ($_SERVER['REQUEST_URI'] ?? '') === '/db-check') {
        header('Content-Type: text/plain');
        echo 'PHP '.PHP_VERSION."\n";
        echo 'pdo_mysql: '.(extension_loaded('pdo_mysql') ? 'ada' : 'TIDAK ADA')."\n";
        echo 'DB_CONNECTION='.getenv('DB_CONNECTION').' DB_HOST='.getenv('DB_HOST').' DB_PORT='.getenv('DB_PORT').' DB_DATABASE='.getenv('DB_DATABASE')."\n";
        echo 'APP_KEY: '.(getenv('APP_KEY') ? 'ada' : 'KOSONG')."\n";
        echo 'vite manifest: '.(file_exists(__DIR__.'/../public/build/manifest.json') ? 'ada' : 'TIDAK ADA')."\n";
        try {
            $opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
            $ca = getenv('MYSQL_ATTR_SSL_CA') ?: (file_exists(__DIR__.'/../certs/ca.pem') ? __DIR__.'/../certs/ca.pem' : null);
            if ($ca) { $opts[PDO::MYSQL_ATTR_SSL_CA] = $ca; }
            if (getenv('DB_SSL_VERIFY') === 'false') { $opts[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false; }
            $pdo = new PDO('mysql:host='.getenv('DB_HOST').';port='.(getenv('DB_PORT') ?: 3306).';dbname='.getenv('DB_DATABASE'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'), $opts);
            echo "KONEKSI DB: OK\n";
            echo 'Tabel: '.implode(', ', $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN))."\n";
        } catch (Throwable $e) {
            echo 'KONEKSI DB GAGAL: '.$e->getMessage()."\n";
        }
        exit;
    }
}

/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

if (isset($_ENV['VERCEL']) || getenv('VERCEL')) {
    $storagePath = '/tmp/storage';

    foreach ([
        $storagePath.'/app/public',
        $storagePath.'/framework/cache/data',
        $storagePath.'/framework/sessions',
        $storagePath.'/framework/views',
        $storagePath.'/logs',
        '/tmp/views',
    ] as $dir) {
        if (! is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
    }

    $app->useStoragePath($storagePath);

    $dbPath = getenv('DB_DATABASE') ?: '/tmp/database.sqlite';
    if ((getenv('DB_CONNECTION') ?: 'sqlite') === 'sqlite' && ! file_exists($dbPath)) {
        @touch($dbPath);
    }

    $app->booted(function () {
        URL::forceScheme('https');

        try {
            if (! Schema::hasTable('users')) {
                // sqlite: buat ulang penuh; mysql: hanya buat tabel yang belum ada (data aman)
                Artisan::call(
                    config('database.default') === 'sqlite' ? 'migrate:fresh' : 'migrate',
                    ['--seed' => true, '--force' => true]
                );
            }
        } catch (Throwable $e) {
            error_log('DB bootstrap gagal: '.$e->getMessage());
        }
    });
}

$app->handleRequest(Request::capture());

