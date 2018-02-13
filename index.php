<?php

use Eccube\Kernel;
use Symfony\Component\Debug\Debug;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;

// システム要件チェック
if (version_compare(PHP_VERSION, '7.0.8') < 0) {
    die('Your PHP installation is too old. EC-CUBE requires at least PHP 7.0.8. See the <a href="http://www.ec-cube.net/product/system.php" target="_blank">system requirements</a> page for more information.');
}

$autoload = __DIR__.'/vendor/autoload.php';

if (!file_exists($autoload) && !is_readable($autoload)) {
    die('Composer is not installed.');
}
require $autoload;

// The check is to ensure we don't use .env in production
if (!isset($_SERVER['APP_ENV'])) {
    if (!class_exists(Dotenv::class)) {
        throw new \RuntimeException('APP_ENV environment variable is not defined. You need to define environment variables for configuration or add "symfony/dotenv" as a Composer dependency to load variables from a .env file.');
    }

    if (file_exists(__DIR__.'/.env')) {
        (new Dotenv())->load(__DIR__.'/.env');
    } else {
        (new Dotenv())->load(__DIR__.'/.env.install');
    }
}

$env = isset($_SERVER['APP_ENV']) ? $_SERVER['APP_ENV'] : 'dev';
$debug = isset($_SERVER['APP_DEBUG']) ? $_SERVER['APP_DEBUG'] : ('prod' !== $env);

if ($debug) {
    umask(0000);

    Debug::enable();
}

$trustedProxies = isset($_SERVER['TRUSTED_PROXIES']) ? $_SERVER['TRUSTED_PROXIES'] : false;
if ($trustedProxies) {
    Request::setTrustedProxies(explode(',', $trustedProxies), Request::HEADER_X_FORWARDED_ALL ^ Request::HEADER_X_FORWARDED_HOST);
}

$trustedHosts = isset($_SERVER['TRUSTED_HOSTS']) ? $_SERVER['TRUSTED_HOSTS'] : false;
if ($trustedHosts) {
    Request::setTrustedHosts(explode(',', $trustedHosts));
}

$kernel = new Kernel($env, $debug);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);