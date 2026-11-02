<?php

declare(strict_types=1);

namespace App;

final class CleanupSessionsTask implements CronTask
{
    public function name(): string { return 'cleanup_sessions'; }

    public function run(): void
    {
        Database::connect()->exec('DELETE FROM app_sessions WHERE expires_at < now()');
    }
}
