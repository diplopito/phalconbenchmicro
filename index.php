<?php

use Phalcon\Db\Adapter\Pdo\Mysql;
use Phalcon\Mvc\Micro;
use Phalcon\Exception as PhalconException;

try {
    $app = new Micro();

    // Setting up the database connection
    $app['db'] = function () {
        return new Mysql([
            'host' => 'tfb-database',
            'dbname' => 'hello_world',
            'username' => 'benchmarkdbuser',
            'password' => 'benchmarkdbpass',
            'options' => [
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'",
                PDO::ATTR_PERSISTENT => true,
            ],
        ]);
    };


    /**
     * Routes
     */
    $app->get('/plaintext', function () {
        header("Content-Type: text/plain; charset=UTF-8");
        echo "Hello, World!";
    });

    $app->get('/json', function () {
        header("Content-Type: application/json");
        echo json_encode(['message' => 'Hello, World!']);
    });

    $app->get('/db', function () use ($app) {
        $world = $app['db']->fetchOne('SELECT * FROM world WHERE id = ' . mt_rand(1, 10000));

        header("Content-Type: application/json");
        echo json_encode($world);
    });

    $app->get('/queries', function ($count = 1) use ($app) {
		
        $db = $app['db'];

        $queries = min(max(intval($count), 1), 500);

        $worlds = [];

        for ($i = 0; $i < $queries; ++$i) {
            $worlds[] = $db->fetchOne('SELECT * FROM world WHERE id = ' . mt_rand(1, 10000));
        }

        header("Content-Type: application/json");
        echo json_encode($worlds);
    });

    $app->get('/fortunes', function () use ($app) {
		
        $fortunes = $app['db']->fetchAll('SELECT * FROM fortune');
        $fortunes[] = [
            'id' => 0,
            'message' => 'Additional fortune added at request time.'
        ];

        usort($fortunes, function ($left, $right) {
            return $left['message'] <=> $right['message'];
        });

        header("Content-Type: text/html; charset=utf-8");
		
        $view = new Phalcon\Mvc\View\Simple;
        $view->setViewsDir('views');

        echo $view->render('bench/fortunes',
                [
                  'fortunes' => $fortunes
                ]
             );
    });

    $url = $_REQUEST['_url'] ?? '/';
    $app->handle($url);
} catch (PhalconException $e) {
    echo "PhalconException: ", $e->getMessage();
}
