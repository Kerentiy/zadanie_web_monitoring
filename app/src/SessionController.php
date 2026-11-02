<?php

declare(strict_types=1);

namespace App;

final class SessionController extends AbstractController
{
    public function __invoke(Request $request): Response
    {
        Session::start();
        if ($request->method === 'POST') {
            // UTF-8 input is capped by bytes here; 320 bytes allow 80 four-byte characters.
            $_SESSION['visitor_name'] = substr(trim((string) ($request->post['name'] ?? '')), 0, 320);
        }
        $_SESSION['visits'] = (int) ($_SESSION['visits'] ?? 0) + 1;
        $name = $this->escape((string) ($_SESSION['visitor_name'] ?? 'гость'));
        $visits = (int) $_SESSION['visits'];
        return $this->html('PostgreSQL-сессия', '<h1>Сессия хранится в PostgreSQL</h1>'
            . '<p>Привет, <strong>' . $name . '</strong>! Посещений в этой сессии: <strong>' . $visits . '</strong>.</p>'
            . '<form method="post"><label>Имя <input name="name" maxlength="80"></label> <button>Сохранить</button></form>'
            . '<p>Handler: <code>PostgresSessionHandler</code>, cookie HttpOnly + SameSite=Lax.</p>'
            . '<p><a href="/">На главную</a></p>');
    }
}
