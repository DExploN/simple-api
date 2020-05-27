<?php
declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Faker\Factory;
use Symfony\Component\Filesystem\Filesystem;

$app->get('/migrate', function (Request $request, Response $response, $args) {

    $fs = new Filesystem();
    $fs->remove(ROOT_DIR . '/uploads');
    $fs->mkdir(ROOT_DIR . $this->get('config')['product_dir']);
    $fs->mkdir(ROOT_DIR . $this->get('config')['audio_dir']);
    $fs->mkdir(ROOT_DIR . $this->get('config')['photo_dir']);

    $faker = Factory::create();

    $db = $this->get(PDO::class);
    $db->query('drop table if exists products');
    $db->query('CREATE TABLE "products" (
	"id"	INTEGER,
	"name"	TEXT,
	"category"	TEXT,
	"price"	INTEGER,
	"summary"	TEXT,
	"description"	TEXT,
	"picture"	TEXT,
	PRIMARY KEY("id" AUTOINCREMENT)
);');
    $categories = [$faker->company, $faker->company, $faker->company];
    for ($i = 1; $i <= 25; $i++) {
        $imageName = uniqid('', true) . '.jpg';
        copy('https://picsum.photos/200/200', full_path('product_dir', $imageName));
        $data = [
            'id' => $i,
            'name' => $db->quote($faker->name),
            'category' => $db->quote($faker->randomElement($categories)),
            'price' => $faker->numberBetween(100, 1000),
            'summary' => $db->quote($faker->sentence),
            'description' => $db->quote($faker->text),
            'picture' => $db->quote($imageName)
        ];
        $rows [] = "(" . implode(',', $data) . ")";
    }

    $query = 'insert into products values ' . implode(',', $rows);

    $db->query($query);


    $db->query('drop table if exists orders');
    $db->query('CREATE TABLE "orders" (
	"id"	INTEGER,
	"products"	TEXT,
	PRIMARY KEY("id" AUTOINCREMENT)
);');


    $db->query('drop table if exists bids');
    $db->query('CREATE TABLE "bids" (
	"id"	INTEGER,
	"name"	TEXT,
	"surname"	TEXT,
	"phone"	TEXT,
	"text_bid"	TEXT,
	"audio_bid"	TEXT,
	"photos"	TEXT,
	PRIMARY KEY("id" AUTOINCREMENT)
);');


    return $response;
});