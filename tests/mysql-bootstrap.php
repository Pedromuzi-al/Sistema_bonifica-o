<?php

require dirname(__DIR__).'/vendor/autoload.php';

$projectRoot = dirname(__DIR__);
$applicationEnvironment = is_file($projectRoot.'/.env')
    ? Dotenv\Dotenv::parse(file_get_contents($projectRoot.'/.env')) : [];

Dotenv\Dotenv::createImmutable($projectRoot)->safeLoad();
$database = $_ENV['DB_DATABASE'] ?? $_SERVER['DB_DATABASE'] ?? getenv('DB_DATABASE');

if (! is_string($database) || ! preg_match('/\A[a-zA-Z0-9_]+_test\z/', $database)) {
    throw new RuntimeException('A suíte MySQL exige um banco exclusivo com nome terminado em _test.');
}
if ($database === ($applicationEnvironment['DB_DATABASE'] ?? null)) {
    throw new RuntimeException('O banco de testes não pode ser o mesmo banco configurado para a aplicação no .env.');
}
if (is_file($projectRoot.'/bootstrap/cache/config.php')) {
    throw new RuntimeException('Execute php artisan config:clear antes dos testes MySQL para evitar uma conexão em cache.');
}
