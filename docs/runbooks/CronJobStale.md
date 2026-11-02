# CronJobStale / CronJobFailures

## Симптом

`cleanup_sessions` не завершалась успешно более 180 секунд либо за последние 10 минут была ошибка.

## Проверки

1. `docker compose ps scheduler postgres` — оба контейнера должны быть запущены.
2. `docker compose logs --since=10m scheduler` — найдите `cron task failed` и проверьте причину.
3. Откройте `make psql` и выполните `SELECT * FROM app_cron_status;` — проверьте `last_started_at`, `last_success_at`, `last_error` и счётчики.
4. Проверьте `make migrate`, если `app_cron_status` отсутствует на старом томе.
5. PromQL: `time() - app_cron_last_success_timestamp_seconds{task="cleanup_sessions"}`.

## Восстановление

Перезапустите scheduler после устранения причины: `docker compose restart scheduler`. Cron повторяет задачу раз в минуту; очистка удаляет только сессии с истёкшим `expires_at`. При проблеме с PostgreSQL сначала восстановите БД.
