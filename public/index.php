<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;

session_start();

$container = new Container();

$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

$container->set('renderer', function () {
    // Параметром передается базовая директория в которой будут храниться шаблоны
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
AppFactory::setContainer($container);
$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response) use ($router) {

    $router->urlFor('main');
    $router->urlFor('salute');
    $router->urlFor('users');
    $router->urlFor('user');
    $router->urlFor('names');
    $router->urlFor('children');

    $messages = $this->get('flash')->getMessages();
    print_r($messages);

    return $this->get('renderer')->render($response, 'index.phtml');
})->setName('main');
///////////////////////////////////////////////////////////////////////////////////////////////
$app->get('/salute', function ($request, $response) {
    return $response->write('Welcome to Slim!');
})->setName('salute');
///////////////////////////////////////////////////////////////////////////////////////////////
$users = ['mike', 'mishel', 'adel', 'keks', 'kamila'];

$app->get('/names', function ($request, $response) use ($users) {
    $term = $request->getQueryParam('term');
    if (isset($term)) {
        $users = array_filter($users, function ($user) use ($term) {
            return strpos($user, $term) !== false;
        });
    }
    $params = [
        'users' => $users,
        'term' => $term
    ];
    return $this->get('renderer')->render($response, 'users/list.phtml', $params);
})->setName('names');


$app->get('/names/{id}', function ($request, $response, $args) use ($router) {
    $nameRouter = $router->urlFor('name', ['id' => $args['id']]);
    $params = ['id' => $args['id'], 'nickname' => 'user-' . $args['id'], 'url' => $nameRouter];
    // Указанный путь считается относительно базовой директории для шаблонов, заданной на этапе конфигурации;
    // $this доступен внутри анонимной функции благодаря http://php.net/manual/ru/closure.bindto.php;
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
})->setName('name');
/////////////////////////////////////////////////////////////////////////////////////////////////
$children = [
['id' => 1, 'name' => 'Maxim', 'age' => 12],
['id' => 2, 'name' => 'Lena', 'age' => 5],
['id' => 3, 'name' => 'Roman', 'age' => 7],
['id' => 4, 'name' => 'Jenya', 'age' => 10]
];

$app->get('/children', function ($request, $response) use ($children) {
    $params = [
        'children' => $children
    ];
    return $this->get('renderer')->render($response, 'children/child.phtml', $params);
})->setName('children');
////////////////////////////////////////////////////////////////////////////////////////////////////
$app->get('/courses/{id}', function ($request, $response, array $args) {
    $id = $args['id'];
    return $response->write("Course id: {$id}");
})->setName('courses');

$app->get('/courses/{courseId}/lessons/{id}', function ($request, $response, array $args) {
    $courseId = $args['courseId'];
    $id = $args['id'];
    return $response->write("Course id: {$courseId}; ")
        ->write("Lesson id: {$id}");
});
///////////////////////////////////////////////////////////////////////////////////////////////////////
var_dump($_SERVER);

$app->get('/users/new', function ($request, $response) {
    $params = [
        'user' => ['name' => '', 'email' => '', 'password' => '', 'passwordConfirmation' => '', 'city' => ''],
        'errors' => []
    ];
    return $this->get('renderer')->render($response, "users/new.phtml", $params);
})->setName('users/new');

$app->get('/users/{id}', function ($request, $response, array $args) {
    $id = $args['id'];
    $lines = file('./fixtures/users.json');

    foreach ($lines as $line) {
        $users[] = json_decode($line, true);
    }
    
    $user = collect($users)->firstWhere('id', $id);
    if (!$user) {
        return $response->withStatus(404)->write('User not found');
    }
    return $response->write(json_encode($user));
})->setName('user');

//$app->post('/users', function ($request, $response) {
//    return $response->withStatus(302);
//});

function validate($user)
{
    $errors = [];
    foreach ($user as $key => $item) {
        if(empty(trim($user[$key]))) {
            $errors[$key] = "Can't be blank";
        }
    }
    return $errors;
}

$app->post('/users', function ($request, $response) {
    $handle = fopen("./fixtures/users.json", "a", "t");
    $user = $request->getParsedBodyParam('user');
    $errors = validate($user);
    if (empty($errors)) {
        fwrite($handle, json_encode($user));
        fclose($handle);
        $this->get('flash')->addMessage('success', 'This is a message');
        return $response->withStatus(302)->withHeader('Location', '/');
    }
        $params = [
        'user' => $user,
        'errors' => $errors
    ];
        return $this->get('renderer')->render($response, "users/new.phtml", $params);
});

$app->get('/users', function ($request, $response) {
    $lines = file('./fixtures/users.json');

    foreach ($lines as $line) {
        $users[] = json_decode($line, true);
    }

    $params = [
        'users' => $users
    ];
    return $this->get('renderer')->render($response, "users/index.phtml", $params);
})->setName('users');

$app->run();
