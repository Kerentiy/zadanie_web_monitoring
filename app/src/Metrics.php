<?php

declare(strict_types=1);

namespace App;

use Prometheus\CollectorRegistry;
use Prometheus\Storage\APCng;
use Throwable;

/** Shared metric registry for HTTP workers (APCu) plus durable PostgreSQL scheduler state. */
final class Metrics
{
    private static ?CollectorRegistry $registry = null;

    public static function enabled(): bool
    {
        return getenv('APP_METRICS_ENABLED') === '1'
            && extension_loaded('apcu')
            && apcu_enabled();
    }

    public static function registry(): CollectorRegistry
    {
        return self::$registry ??= new CollectorRegistry(new APCng(decimalPrecision: 6), false);
    }

    public static function noteCreated(): void
    {
        self::safely(static function (): void {
            self::registry()->getOrRegisterCounter('app', 'notes_created_total', 'Количество созданных записей')->inc();
        });
    }

    /** Count bounded session-store operations; failures remain visible even if the DB session write failed. */
    public static function sessionOperation(string $operation, string $result): void
    {
        self::safely(static function () use ($operation, $result): void {
            self::registry()
                ->getOrRegisterCounter('app', 'session_store_operations_total', 'Операции хранилища сессий', ['operation', 'result'])
                ->inc([$operation, $result]);
        });
    }

    /** Pull durable scheduler totals into the PHP exporter; values survive scheduler restarts. */
    public static function collectCronMetrics(): void
    {
        try {
            $rows = Database::connect()->query(
                'SELECT job_name, COALESCE(extract(epoch FROM last_success_at), 0) AS last_success, '
                . 'runs_success_total, runs_failed_total, last_duration_seconds, consecutive_failures FROM app_cron_status'
            )->fetchAll();
            $registry = self::registry();
            $lastSuccess = $registry->getOrRegisterGauge(
                'app', 'cron_last_success_timestamp_seconds',
                'Время последнего успешного запуска cron-задачи (Unix time)', ['task']
            );
            $runs = $registry->getOrRegisterGauge(
                'app', 'cron_runs', 'Накопленное число запусков фоновых задач', ['task', 'result']
            );
            $duration = $registry->getOrRegisterGauge(
                'app', 'cron_last_duration_seconds', 'Длительность последнего запуска фоновой задачи, секунды', ['task']
            );
            $failures = $registry->getOrRegisterGauge(
                'app', 'cron_consecutive_failures', 'Последовательные ошибки фоновой задачи', ['task']
            );
            foreach ($rows as $row) {
                $task = (string) $row['job_name'];
                $lastSuccess->set((float) $row['last_success'], [$task]);
                $runs->set((float) $row['runs_success_total'], [$task, 'success']);
                $runs->set((float) $row['runs_failed_total'], [$task, 'failure']);
                $duration->set((float) $row['last_duration_seconds'], [$task]);
                $failures->set((float) $row['consecutive_failures'], [$task]);
            }
        } catch (Throwable $e) {
            // Do not suppress other HTTP metrics when cron schema or PostgreSQL is unavailable.
            error_log('cron metrics unavailable: ' . $e->getMessage());
        }
    }

    /** Metrics must never cause a user request to fail. */
    public static function safely(callable $fn): void
    {
        if (!self::enabled()) {
            return;
        }
        try {
            $fn();
        } catch (Throwable $e) {
            error_log('metrics: ' . $e->getMessage());
        }
    }
}
