<?php
declare(strict_types=1);


use Slim\Factory\AppFactory;

require __DIR__ . '/vendor/autoload.php';
try {
    $container = new League\Container\Container;

    $container->add(PDO::class, function () {
        $pdo = new PDO('sqlite:database.sqlite');

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    });
    AppFactory::setContainer($container);
    $app = AppFactory::create();

    require_once 'functions.php';
    require_once 'routes.php';
    require_once 'migrations.php';
    require_once 'middlewares.php';

    $app->run();
}catch (\Throwable $throwable){
    echo "Ошибка: ". $throwable->getMessage();
}