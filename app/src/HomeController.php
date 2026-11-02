<?php

declare(strict_types=1);

namespace App;

final class HomeController extends AbstractController
{
    public function __invoke(Request $request): Response
    {
        $dbStatus = 'доступна';
        $rows = [];
        try {
            $rows = Database::connect()->query('SELECT id, created_at, note FROM demo ORDER BY id DESC LIMIT 50')->fetchAll();
        } catch (\Throwable $e) {
            $dbStatus = 'ошибка подключения';
        }

        $content = '<h1>nginx + php-fpm + PostgreSQL</h1>'
            . '<p>PostgreSQL: <strong>' . $this->escape($dbStatus) . '</strong>; записей показано: ' . count($rows) . '</p>'
            . '<nav class="nav"><a href="/session">Проверить PostgreSQL-сессию</a></nav>'
            . '<h2>Таблица demo</h2>';
        if ($rows !== []) {
            $content .= '<table><tr><th>id</th><th>создано</th><th>note</th></tr>';
            foreach ($rows as $row) {
                $content .= '<tr><td>' . (int) $row['id'] . '</td><td>'
                    . $this->escape((string) $row['created_at']) . '</td><td>'
                    . $this->escape((string) $row['note']) . '</td></tr>';
            }
            $content .= '</table>';
        } else {
            $content .= '<p>Нет данных (таблица пуста или БД недоступна).</p>';
        }
        $content .= '<h2>Добавить заметку</h2><form method="post" action="/notes">'
            . '<input name="note" placeholder="Новая запись" required maxlength="500"> '
            . '<button>Добавить</button></form>';
        return $this->html('Зоопарк — мониторинг', $content);
    }
}
