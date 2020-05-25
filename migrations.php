<?php
declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Faker\Factory;

$app->get('/migrate', function (Request $request, Response $response, $args) {

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
    for ($i = 1; $i <= 100; $i++) {
        $data = [
            'id' => $i,
            'name' => $db->quote($faker->name),
            'category' => $db->quote($faker->randomElement($categories)),
            'price' => $faker->numberBetween(100, 1000),
            'summary' => $db->quote($faker->sentence),
            'description' => $db->quote($faker->text),
            'picture' => $db->quote($faker->url)
        ];
        $rows [] = "(" . implode(',',  $data) . ")";
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