<?php

declare(strict_types=1);

namespace App;

use PDOException;

final class UserRepository
{
    /** @return array{id: int|string, email: string}|null */
    public function create(string $email, string $passwordHash): ?array
    {
        try {
            $stmt = Database::connect()->prepare(
                'INSERT INTO app_users (email, password_hash) VALUES (:email, :password_hash) RETURNING id, email'
            );
            $stmt->execute(['email' => $email, 'password_hash' => $passwordHash]);
            $user = $stmt->fetch();
            return $user === false ? null : $user;
        } catch (PDOException $e) {
            $sqlState = (string) ($e->errorInfo[0] ?? $e->getCode());
            if ($sqlState === '23505') {
                return null;
            }
            throw $e;
        }
    }

    /** @return array{id: int|string, email: string, password_hash: string}|null */
    public function findByEmail(string $email): ?array
    {
        $stmt = Database::connect()->prepare(
            'SELECT id, email, password_hash FROM app_users WHERE email = :email'
        );
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        return $user === false ? null : $user;
    }

    /** @return array{id: int|string, email: string}|null */
    public function findById(int $id): ?array
    {
        $stmt = Database::connect()->prepare('SELECT id, email FROM app_users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();
        return $user === false ? null : $user;
    }
}
