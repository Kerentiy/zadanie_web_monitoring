<?php

declare(strict_types=1);

namespace App;

use Throwable;

final class CronRunner
{
    public function __construct(private readonly CronTask $task) {}

    public function run(): int
    {
        $pdo = Database::connect();
        $lockKey = 'zoo-cron:' . $this->task->name();
        $lock = $pdo->prepare('SELECT pg_try_advisory_lock(hashtext(:job))');
        $lock->execute(['job' => $lockKey]);
        $lockValue = $lock->fetchColumn();
        $acquired = $lockValue === true || $lockValue === 1 || $lockValue === '1' || $lockValue === 't';
        if (!$acquired) {
            Log::info('cron task skipped: lock held', ['job' => $this->task->name()]);
            return 0;
        }

        $job = $this->task->name();
        $started = hrtime(true);
        $pdo->prepare('UPDATE app_cron_status SET last_started_at = now() WHERE job_name = :job')->execute(['job' => $job]);
        try {
            $this->task->run();
            $duration = (hrtime(true) - $started) / 1e9;
            $stmt = $pdo->prepare(<<<'SQL'
                UPDATE app_cron_status SET last_success_at = now(), last_duration_seconds = :duration,
                    consecutive_failures = 0, runs_success_total = runs_success_total + 1,
                    last_error = NULL WHERE job_name = :job
                SQL);
            $stmt->execute(['duration' => $duration, 'job' => $job]);
            Log::info('cron task completed', ['job' => $job, 'duration_seconds' => round($duration, 4)]);
            return 0;
        } catch (Throwable $e) {
            $duration = (hrtime(true) - $started) / 1e9;
            $stmt = $pdo->prepare(<<<'SQL'
                UPDATE app_cron_status SET last_duration_seconds = :duration,
                    consecutive_failures = consecutive_failures + 1,
                    runs_failed_total = runs_failed_total + 1,
                    last_error = :error WHERE job_name = :job
                SQL);
            $stmt->execute(['duration' => $duration, 'error' => substr($e->getMessage(), 0, 500), 'job' => $job]);
            Log::error('cron task failed', ['job' => $job, 'exception' => $e::class]);
            return 1;
        } finally {
            $pdo->prepare('SELECT pg_advisory_unlock(hashtext(:job))')->execute(['job' => $lockKey]);
        }
    }
}
