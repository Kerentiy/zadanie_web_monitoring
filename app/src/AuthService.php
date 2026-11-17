<?php

declare(strict_types=1);

namespace App;

final class AuthService
{
    public function __construct(private readonly UserRepository $users) {}

    public function csrfToken(): string
    {
        Session::start();
        if (!isset($_SESSION['_csrf_token']) || !is_string($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }

    public function hasValidCsrfToken(mixed $candidate): bool
    {
        Session::start();
        return is_string($candidate)
            && isset($_SESSION['_csrf_token'])
            && is_string($_SESSION['_csrf_token'])
            && hash_equals($_SESSION['_csrf_token'], $candidate);
    }

    public function register(string $email, string $password): bool
    {
        $user = $this->users->create(strtolower(trim($email)), password_hash($password, PASSWORD_DEFAULT));
        if ($user === null) {
            return false;
        }
        $this->authenticate((int) $user['id']);
        return true;
    }

    public function login(string $email, string $password): bool
    {
        $user = $this->users->findByEmail(strtolower(trim($email)));
        if ($user === null || !password_verify($password, $user['password_hash'])) {
            return false;
        }
        $this->authenticate((int) $user['id']);
        return true;
    }

    /** @return array{id: int|string, email: string}|null */
    public function currentUser(): ?array
    {
        Session::start();
        $id = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id === null || $id < 1) {
            return null;
        }

        $user = $this->users->findById($id);
        if ($user === null) {
            unset($_SESSION['user_id']);
        }
        return $user;
    }

    public function logout(): void
    {
        Session::start();
        $_SESSION = [];
        $cookie = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $cookie['path'] ?: '/',
            'domain' => $cookie['domain'],
            'secure' => $cookie['secure'],
            'httponly' => $cookie['httponly'],
            'samesite' => $cookie['samesite'] ?: 'Lax',
        ]);
        session_destroy();
    }

    private function authenticate(int $userId): void
    {
        Session::start();
        if (!session_regenerate_id(true)) {
            throw new \RuntimeException('Не удалось обновить идентификатор сессии.');
        }
        $_SESSION['user_id'] = $userId;
    }
}
