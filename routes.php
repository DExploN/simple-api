<?php
declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Psr7\UploadedFile;

$app->get('/', function (Request $request, Response $response, $args) {

    $response->getBody()->write("Hello to api!");
    return $response;
});
$app->get('/products', function (Request $request, Response $response, $args) {
    $page = (int)($request->getQueryParams()['page'] ?? 1);
    $offset = ($page - 1) * 10;
    $result = $this->get(PDO::class)->query("select * from products order by id limit $offset ,10");
    $products = $result->fetchAll();
    if (!$products) {
        return $response->withStatus(404);
    }

    array_walk($products, function (&$item) {
        $item['picture'] = web_path('product_dir', $item['picture']);
    });

    $response->getBody()->write(custom_json_encode($products));
    return $response;
})->setName('products.get');

$app->get('/products/{id}', function (Request $request, Response $response, $args) {
    $response = $response->withHeader('Content-type', 'application/json');
    $id = (int)$args['id'];

    $sth = $this->get(PDO::class)->prepare("select * from products where id = :id limit 1");
    $sth->execute(['id' => $id]);
    $product = $sth->fetch();
    if (!$product) {
        return $response->withStatus(404);
    }
    $product['picture'] = web_path('product_dir', $product['picture']);
    $response->getBody()->write(custom_json_encode($product));
    return $response;
});

$app->get('/orders', function (Request $request, Response $response, $args) {
    $response = $response->withHeader('Content-type', 'application/json');
    $page = (int)($request->getQueryParams()['page'] ?? 1);
    $offset = ($page - 1) * 10;
    $result = $this->get(PDO::class)->query("select * from orders order by id desc limit $offset ,10");
    $orders = $result->fetchAll();
    if (!$orders) {
        return $response->withStatus(404);
    }

    foreach ($orders as &$order) {
        $order['products'] = json_decode($order['products'], true);
    }
    $response->getBody()->write(custom_json_encode($orders));
    return $response;
});

$app->post('/orders', function (Request $request, Response $response, $args) {
    $response = $response->withHeader('Content-type', 'application/json');

    $data = json_decode($request->getBody()->getContents(), true);

    if ($data) {
        foreach ($data as &$row) {

            $row = array_intersect_key($row, ['id' => 1, 'quantity' => 1]);
            if (empty($row['id']) || !is_int($row['id'])) {
                throw new Exception("Один из id отсутствует или передан не в формате int");
            }
            if (empty($row['quantity']) || !is_int($row['quantity'])) {
                throw new Exception("Один из quantity отсутствует или передан не в формате int");
            }
        }
    } else {
        throw new Exception("Заказ пуст");
    }

    $data = custom_json_encode($data);
    $db = $this->get(PDO::class);
    $db->query("insert into orders(`products`) values('$data')");
    $id = $db->lastInsertId();
    $response = $response->withStatus(201);
    $response->getBody()->write(custom_json_encode(['orderId' => $id]));
    return $response;

});


$app->get('/bids', function (Request $request, Response $response, $args) {
    $response = $response->withHeader('Content-type', 'application/json');
    $page = (int)($request->getQueryParams()['page'] ?? 0);
    $offset = $page * 10;
    $result = $this->get(PDO::class)->query("select * from bids order by id desc limit $offset ,10");
    $bids = $result->fetchAll();
    if (!$bids) {
        return $response->withStatus(404);
    }

    foreach ($bids as &$bid) {
        $bid['audio_bid'] = $bid['audio_bid'] ? web_path('audio_dir', $bid['audio_bid']) : null;

        $photos = json_decode($bid['photos'], true);
        $bid['photos'] = array_map(function ($filename) {
            return web_path('photo_dir',  $filename);
        }, $photos);
    };
    $response->getBody()->write(custom_json_encode($bids));
    return $response;
}
);

$app->post('/bids', function (Request $request, Response $response, $args) {
    $response = $response->withHeader('Content-type', 'application/json');

    $data = $request->getParsedBody();
    $files = $request->getUploadedFiles();

    if (empty($data['name'])) {
        throw new Exception("Отсутствует поле name");
    }
    if (empty($data['surname'])) {
        throw new Exception("Отсутствует поле surname");
    }
    if (empty($data['phone'])) {
        throw new Exception("Отсутствует поле phone");
    }
    if (empty($data['phone'])) {
        throw new Exception("Отсутствует поле phone");
    }
    if (!is_numeric($data['phone'])) {
        throw new Exception("Поле phone должно быть числом");
    }
    if (empty('text_bid') && empty($files['audio_bid'])) {
        throw new Exception("Должна быть заполенно text_bid или передан файл в audio_bid");
    }

    $data['text_bid'] = $data['text_bid'] ?? '';
    $data['audio_bid'] = '';

    /** @var UploadedFile $audioBid */
    if (isset($files['audio_bid'])) {
        $audioBid = $files['audio_bid'];
        if ($audioBid->getError() === UPLOAD_ERR_OK && $audioBid->getSize() > 0) {
            if($audioBid->getClientMediaType()!=='audio/mpeg'){
                throw new Exception("Mime Type аудио заявки должен быть audio/mpeg");
            }
            $filename = uniqid('', true) . '.' . pathinfo($audioBid->getClientFilename(),
                    PATHINFO_EXTENSION);
            $audioBid->moveTo(full_path('audio_dir',$filename));
            $data['audio_bid'] = $filename;
        } else {
            throw new Exception("Ошибка при загрузке audio_bid");
        }
    }

    $photos = $files['photos'] ?? [];
    $data['photos'] = [];

    foreach ($photos as $photo) {
        if ($photo->getError() === UPLOAD_ERR_OK  && $photo->getSize() > 0) {

            if(!($photo->getClientMediaType()==='image/jpeg' || $photo->getClientMediaType()==='image/png') ){
                throw new Exception("Mime Type фото должен быть image/jpeg или image/png'");
            }
            $filename = uniqid('', true) . '.' . pathinfo($photo->getClientFilename(),
                    PATHINFO_EXTENSION);
            $photo->moveTo(full_path('photo_dir',$filename));

            $data['photos'][] = $filename;
        } else {
            throw new Exception("Ошибка при загрузке одного из photos");
        }
    }

    $data['photos'] = custom_json_encode($data['photos']);

    $columns = ['name', 'surname', 'phone', 'text_bid', 'audio_bid', 'photos'];

    $data = array_intersect_key($data, array_flip($columns));

    $db = $this->get(PDO::class);

    $stm = $db->prepare('insert into bids(' . implode(',', array_map(function ($column) {
            return "`$column`";
        }, $columns)) . ') values(' . implode(',', array_map(function ($column) {
            return ":$column";
        }, $columns)) . ')');
    $stm->execute($data);

    $id = $db->lastInsertId();
    $response = $response->withStatus(201);
    $response->getBody()->write(custom_json_encode(['bidId' => $id]));
    return $response;

});




