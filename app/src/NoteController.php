<?php

declare(strict_types=1);

namespace App;

final class NoteController extends AbstractController
{
    public function __invoke(Request $request): Response
    {
        $note = trim((string) ($request->post['note'] ?? ''));
        if ($note === '' || strlen($note) > 2000) {
            return $this->html('Некорректная заметка', '<p>Заметка должна содержать от 1 до 500 символов.</p><a href="/">Назад</a>', 422);
        }
        try {
            $stmt = Database::connect()->prepare('INSERT INTO demo (note) VALUES (:note)');
            $stmt->execute(['note' => $note]);
            Metrics::noteCreated();
        } catch (\Throwable $e) {
            Log::error('note creation failed', ['exception' => $e::class]);
            return $this->html('Ошибка', '<p>Не удалось сохранить заметку.</p><a href="/">Назад</a>', 503);
        }
        return new Response('', 303, ['Location' => '/']);
    }
}
