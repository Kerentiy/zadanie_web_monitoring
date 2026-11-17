<?php

declare(strict_types=1);

namespace App;

final class FrontendController extends AbstractController
{
    public function __construct(private readonly AuthService $auth) {}

    public function __invoke(Request $request): Response
    {
        return match ([$request->method, $request->path]) {
            ['GET', '/register'], ['POST', '/register'] => $this->register($request),
            ['GET', '/login'], ['POST', '/login'] => $this->login($request),
            ['POST', '/logout'] => $this->logout($request),
            default => new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']),
        };
    }

    public function register(Request $request): Response
    {
        if ($request->method === 'GET') {
            return $this->form('Регистрация', '/register', $this->auth->csrfToken(), [
                'email' => 'Email',
                'password' => 'Пароль (не менее 8 символов)',
                'password_confirmation' => 'Повторите пароль',
            ]);
        }
        $result = (new RegisterUserRequest($request, $this->auth))->run();
        return $this->renderResult($result, 'Регистрация', '/register', [
            'email' => 'Email',
            'password' => 'Пароль (не менее 8 символов)',
            'password_confirmation' => 'Повторите пароль',
        ]);
    }

    public function login(Request $request): Response
    {
        if ($request->method === 'GET') {
            return $this->form('Вход', '/login', $this->auth->csrfToken(), [
                'email' => 'Email',
                'password' => 'Пароль',
            ]);
        }
        $result = (new LoginUserRequest($request, $this->auth))->run();
        return $this->renderResult($result, 'Вход', '/login', [
            'email' => 'Email',
            'password' => 'Пароль',
        ]);
    }

    public function logout(Request $request): Response
    {
        $result = (new LogoutUserRequest($request, $this->auth))->run();
        return $this->renderResult($result, 'Выход', '/login', []);
    }

    private function renderResult(ResponseModel $result, string $title, string $action, array $fields): Response
    {
        if ($result->success && $result->redirectTo !== null) {
            return new Response('', 303, ['Location' => $result->redirectTo]);
        }
        $message = '<p role="alert">' . $this->escape($result->message) . '</p>';
        foreach ($result->errors as $field => $error) {
            $message .= '<p role="alert"><strong>' . $this->escape((string) $field) . ':</strong> '
                . $this->escape((string) $error) . '</p>';
        }
        return $this->form($title, $action, $this->auth->csrfToken(), $fields, $message, $result->status);
    }

    private function form(
        string $title,
        string $action,
        string $csrfToken,
        array $fields,
        string $message = '',
        int $status = 200,
    ): Response {
        $content = '<h1>' . $this->escape($title) . '</h1>' . $message
            . '<form method="post" action="' . $this->escape($action) . '">'
            . '<input type="hidden" name="_csrf_token" value="' . $this->escape($csrfToken) . '">';
        foreach ($fields as $name => $label) {
            $type = $name === 'email' ? 'email' : 'password';
            $maxLength = $name === 'email' ? 254 : 128;
            $content .= '<label>' . $this->escape($label) . '<br><input type="' . $type . '" name="'
                . $this->escape($name) . '" maxlength="' . $maxLength . '" required autocomplete="'
                . ($name === 'email' ? 'email' : 'new-password') . '"></label><br><br>';
        }
        $content .= '<button type="submit">Отправить</button></form><p><a href="/">На главную</a></p>';
        return $this->html($title, $content, $status);
    }
}
