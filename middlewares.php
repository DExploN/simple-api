<?php
declare(strict_types=1);
use Slim\Psr7\Response;
use Psr\Http\Message\ServerRequestInterface as Request;

$errorHandler = function (Request $request, $handler) {
    try{
        $response = $handler->handle($request);
    }catch (\Throwable $throwable){
        $response = new Response();
        $response = $response->withStatus(400);
        $response->getBody()->write(custom_json_encode(['error'=>$throwable->getMessage()]));
    }
    return $response;
};

$jsonTypeResponse  = function (Request $request, $handler) {
    /** @var  Response $response */
    $response = $handler->handle($request);
    $response = $response->withHeader('Content-type', 'application/json');
    return $response;
};

$app->add($errorHandler);
$app->add($jsonTypeResponse);