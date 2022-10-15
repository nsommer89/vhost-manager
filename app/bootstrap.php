<?php
require __DIR__.'/vendor/autoload.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    $dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/');
    $dotenv->load();
} catch (Dotenv\Exception\InvalidPathException $e) {
    die($e->getMessage() . PHP_EOL);
}

try {
    date_default_timezone_set(getenv('DEFAULT_TIMEZONE'));
} catch (\Exception $e) {
    die($e->getMessage() . PHP_EOL);
}

function hook_brand($appName, $appVersion, $locale) {
    $brand_file_path = __DIR__ . '/bin/brand';
    if (file_exists($brand_file_path)) {
        echo file_get_contents($brand_file_path) . PHP_EOL . $appName." v/" . $appVersion . ' ' . $locale . PHP_EOL;
    }
}