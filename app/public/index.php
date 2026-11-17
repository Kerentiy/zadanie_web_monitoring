<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\AuthService;
use App\FrontendController;
use App\HomeController;
use App\NoteController;
use App\Request;
use App\Response;
use App\Router;
use App\SessionController;
use App\UserRepository;

$request = Request::fromGlobals();
$auth = new AuthService(new UserRepository());
$frontend = new FrontendController($auth);
$router = (new Router())
    ->add('GET', '/', new HomeController($auth))
    ->add('POST', '/notes', new NoteController())
    ->add('GET', '/session', new SessionController())
    ->add('POST', '/session', new SessionController())
    ->add('GET', '/register', $frontend)
    ->add('POST', '/register', $frontend)
    ->add('GET', '/login', $frontend)
    ->add('POST', '/login', $frontend)
    ->add('POST', '/logout', $frontend);

try {
    $router->dispatch($request)->send();
} catch (Throwable $e) {
    error_log('request failed: ' . $e->getMessage());
    (new Response('Внутренняя ошибка сервера', 500, ['Content-Type' => 'text/plain; charset=utf-8']))->send();
}
