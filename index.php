<?php
declare(strict_types=1);


use Slim\Factory\AppFactory;

require __DIR__ . '/vendor/autoload.php';
define('ROOT_DIR', __DIR__);
try {
    $container = new League\Container\Container;

    $container->add(PDO::class, function () {
        $pdo = new PDO('sqlite:database.sqlite');

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    });
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
    $config = [
        'product_dir' => '/uploads/products',
        'audio_dir' => '/uploads/audios',
        'photo_dir' => '/uploads/photos',
        'host_name' => $protocol . $_SERVER['HTTP_HOST']
    ];
    $container->add('config', function () use ($config) {
        return $config;
    });
    AppFactory::setContainer($container);
    $app = AppFactory::create();

    require_once 'functions.php';
    require_once 'routes.php';
    require_once 'migrations.php';
    require_once 'middlewares.php';

    $app->run();
} catch (\Throwable $throwable) {
    echo "Ошибка: " . $throwable->getMessage();
}