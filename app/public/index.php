<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\HomeController;
use App\NoteController;
use App\Request;
use App\Response;
use App\Router;
use App\SessionController;

$request = Request::fromGlobals();
$router = (new Router())
    ->add('GET', '/', new HomeController())
    ->add('POST', '/notes', new NoteController())
    ->add('GET', '/session', new SessionController())
    ->add('POST', '/session', new SessionController());

try {
    $router->dispatch($request)->send();
} catch (Throwable $e) {
    error_log('request failed: ' . $e->getMessage());
    (new Response('Внутренняя ошибка сервера', 500, ['Content-Type' => 'text/plain; charset=utf-8']))->send();
}
