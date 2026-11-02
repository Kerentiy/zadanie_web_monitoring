<?php

declare(strict_types=1);

namespace App;

use PDO;
use SessionHandlerInterface;
use SessionUpdateTimestampHandlerInterface;
use Throwable;

/** Persistent PHP session handler backed by PostgreSQL (never session files). */
final class PostgresSessionHandler implements SessionHandlerInterface, SessionUpdateTimestampHandlerInterface
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connect();
    }

    public function open(string $path, string $name): bool { return true; }
    public function close(): bool { return true; }

    public function read(string $id): string|false
    {
        try {
            $stmt = $this->pdo->prepare('SELECT payload FROM app_sessions WHERE session_id = :id AND expires_at > now()');
            $stmt->execute(['id' => $id]);
            $payload = $stmt->fetchColumn();
            Metrics::sessionOperation('read', 'success');
            return $payload === false ? '' : (string) $payload;
        } catch (Throwable $e) {
            Metrics::sessionOperation('read', 'error');
            error_log('session read failed: ' . $e->getMessage());
            return false;
        }
    }

    public function write(string $id, string $data): bool
    {
        try {
            $stmt = $this->pdo->prepare(<<<'SQL'
                INSERT INTO app_sessions (session_id, payload, expires_at)
                VALUES (:id, :payload, now() + make_interval(secs => :ttl))
                ON CONFLICT (session_id) DO UPDATE
                SET payload = EXCLUDED.payload, expires_at = EXCLUDED.expires_at
                SQL);
            $ok = $stmt->execute(['id' => $id, 'payload' => $data, 'ttl' => $this->ttl()]);
            Metrics::sessionOperation('write', $ok ? 'success' : 'error');
            return $ok;
        } catch (Throwable $e) {
            Metrics::sessionOperation('write', 'error');
            error_log('session write failed: ' . $e->getMessage());
            return false;
        }
    }

    public function destroy(string $id): bool
    {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM app_sessions WHERE session_id = :id');
            $ok = $stmt->execute(['id' => $id]);
            Metrics::sessionOperation('destroy', $ok ? 'success' : 'error');
            return $ok;
        } catch (Throwable $e) {
            Metrics::sessionOperation('destroy', 'error');
            return false;
        }
    }

    public function gc(int $max_lifetime): int|false
    {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM app_sessions WHERE expires_at < now()');
            $stmt->execute();
            return $stmt->rowCount();
        } catch (Throwable $e) {
            error_log('session gc failed: ' . $e->getMessage());
            return false;
        }
    }

    public function validateId(string $id): bool
    {
        try {
            $stmt = $this->pdo->prepare('SELECT 1 FROM app_sessions WHERE session_id = :id AND expires_at > now()');
            $stmt->execute(['id' => $id]);
            return $stmt->fetchColumn() !== false;
        } catch (Throwable $e) {
            Metrics::sessionOperation('validate', 'error');
            return false;
        }
    }

    public function updateTimestamp(string $id, string $data): bool
    {
        try {
            $stmt = $this->pdo->prepare('UPDATE app_sessions SET expires_at = now() + make_interval(secs => :ttl) WHERE session_id = :id');
            $ok = $stmt->execute(['ttl' => $this->ttl(), 'id' => $id]) && $stmt->rowCount() > 0;
            Metrics::sessionOperation('touch', $ok ? 'success' : 'error');
            return $ok;
        } catch (Throwable $e) {
            Metrics::sessionOperation('touch', 'error');
            return false;
        }
    }

    private function ttl(): int
    {
        return max(60, (int) (getenv('SESSION_TTL') ?: 1440));
    }
}
