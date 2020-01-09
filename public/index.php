<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;


$container = new Container();
$container->set('renderer', function () {
    // Параметром передается базовая директория в которой будут храниться шаблоны
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
AppFactory::setContainer($container);
$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

$app->get('/', function ($request, $response) {
    return $this->get('renderer')->render($response, 'index.phtml');
});
///////////////////////////////////////////////////////////////////////////////////////////////
$app->get('/salute', function ($request, $response) {
    return $response->write('Welcome to Slim!');
});
///////////////////////////////////////////////////////////////////////////////////////////////
//$app->get('/users', function ($request, $response) {
//    return $response->write('GET /users');
//});

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
});


$app->get('/names/{id}', function ($request, $response, $args) {;
    $params = ['id' => $args['id'], 'nickname' => 'user-' . $args['id']];
    // Указанный путь считается относительно базовой директории для шаблонов, заданной на этапе конфигурации;
    // $this доступен внутри анонимной функции благодаря http://php.net/manual/ru/closure.bindto.php;
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
});
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
});
////////////////////////////////////////////////////////////////////////////////////////////////////
$app->get('/courses/{id}', function ($request, $response, array $args) {
    $id = $args['id'];
    return $response->write("Course id: {$id}");
});

$app->get('/courses/{courseId}/lessons/{id}', function ($request, $response, array $args) {
    $courseId = $args['courseId'];
    $id = $args['id'];
    return $response->write("Course id: {$courseId}; ")
        ->write("Lesson id: {$id}");
});

var_dump($_SERVER);

$app->get('/users/new', function ($request, $response) {
    $params = [
        'user' => ['name' => '', 'email' => '', 'password' => '', 'passwordConfirmation' => '', 'city' => ''],
        'errors' => []
    ];
    return $this->get('renderer')->render($response, "users/new.phtml", $params);
});

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
        return $response->withHeader('Location', '/')
            ->withStatus(302);
    }
    $params = [
        'user' => $user,
        'errors' => $errors
    ];
    return $this->get('renderer')->render($response, "users/new.phtml", $params);
});

$app->run();
