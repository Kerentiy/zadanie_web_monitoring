## Что было найдено в мониторинге

Prometheus собирает метрики компонентов из конфигурации `docker/prometheus/prometheus.yml`: nginx, php-fpm, PostgreSQL, приложение, Alertmanager и black-box exporter. Black-box exporter проверяет HTTP-доступность приложения и nginx. Опционально можно включить сбор метрик контейнеров через cAdvisor.

Приложение уже отдавало HTTP-метрики `http_requests_total` и `http_request_duration_seconds`, а также бизнес-метрику `app_notes_created_total`. Исходные алерты обнаруживали недоступность компонентов и health-check, высокий процент HTTP-ошибок и задержки, насыщение пула php-fpm, недоступность PostgreSQL и нехватку его соединений.

**Пробел:** в исходном стенде не было механизма запуска фоновых задач и метрик, по которым можно понять, запускались ли они и завершились ли успешно. Поэтому остановившаяся или регулярно падающая cron-задача осталась бы незаметной.

## Что добавлено и где

### Метрики и мониторинг фоновых задач

- `app/src/CronTask.php` — общий интерфейс фоновой задачи.
- `app/src/CleanupSessionsTask.php` — задача, удаляющая из PostgreSQL истёкшие сессии.
- `app/src/CronRunner.php` — запуск задачи, блокировка параллельных запусков через PostgreSQL advisory lock и запись результата выполнения.
- `app/bin/cron.php` — точка входа для запуска задачи.
- `docker/php/cron.d/app-cron` — расписание: запуск задачи раз в минуту.
- `docker/php/cron-entrypoint.sh` — подготовка окружения cron-контейнера и запуск демона.
- `docker/php/Dockerfile` — установка cron и добавление его конфигурации в PHP-образ.
- `compose.yaml` — сервис `scheduler`, который запускает cron и подключается к PostgreSQL.
- `docker/postgres/initdb/03-app-features.sql` — таблицы для сессий и состояния cron-задач. Скрипт идемпотентный и подходит для повторного применения.

Состояние задачи хранится в `app_cron_status` в PostgreSQL, поэтому не теряется при перезапуске scheduler. В `app/src/Metrics.php` добавлена публикация метрик:

- `app_cron_last_success_timestamp_seconds` — время последнего успешного запуска;
- `app_cron_runs` — накопленные запуски с разбивкой по результату;
- `app_cron_last_duration_seconds` — длительность последнего запуска;
- `app_cron_consecutive_failures` — число последовательных ошибок.

В `app/public/metrics.php` эти метрики включаются в ответ эндпоинта приложения. В `docker/prometheus/rules/alerts.yml` добавлены алерты `CronJobNeverRun`, `CronJobStale` и `CronJobFailures`. Их инструкции по диагностике находятся в `docs/runbooks/CronJobStale.md`.

В `docker/grafana/dashboards/service/service-overview.json` добавлены панели для heartbeat, результатов запусков, длительности и последовательных ошибок. Источник данных уже настроен через Grafana provisioning, поэтому вручную добавлять datasource не нужно.

### PostgreSQL-сессии

- `app/src/PostgresSessionHandler.php` — собственная реализация PHP session handler на PostgreSQL, вместо файлового хранилища.
- `app/src/Session.php` — регистрация обработчика и параметры cookie: HttpOnly и SameSite=Lax.
- `app/src/SessionController.php` — маршрут для проверки сохранения сессии между запросами.
- `app/src/Metrics.php` — счётчик `session_store_operations_total` с метками операции и результата.
- `docker/postgres/initdb/03-app-features.sql` — таблица `app_sessions` и индекс для поиска истёкших записей.
- `SESSION_TTL` в `.env.example` — настройка времени жизни сессии в секундах.

Очистка истёкших записей выполняется фоновой задачей `cleanup_sessions`, а не только случайным запуском PHP garbage collector.

### Маршрутизация и контроллеры

- `app/public/index.php` теперь является front controller: он создаёт запрос, настраивает маршруты и передаёт обработку контроллеру.
- `app/src/Request.php` и `app/src/Response.php` — простые объекты запроса и ответа.
- `app/src/Router.php` — сопоставление HTTP-метода и пути с контроллером. Неизвестный путь возвращает 404, неверный метод для известного пути — 405.
- `app/src/AbstractController.php` — базовый абстрактный контроллер.
- `app/src/HomeController.php` и `app/src/NoteController.php` — главная страница и создание заметки.
- `app/src/SessionController.php` — демонстрация работы PostgreSQL-сессии.

В `app/src/Router.php` и `app/instrumentation/prepend.php` также согласованы имена маршрутов в HTTP-метриках: для объявленных маршрутов используется статический путь, а неизвестные URL объединяются в `other`. Это сохраняет разбивку метрик по маршрутам и ограничивает кардинальность Prometheus.

### Запуск и проверка схемы

В `Makefile` добавлена команда `make migrate`. Она применяет `03-app-features.sql` к уже существующей базе, где initdb-скрипты автоматически повторно не запускаются. Для нового тома схема создаётся при инициализации PostgreSQL.

## Как проверить

Полный запуск:

```bash
make init
make up
make composer
make migrate
make mon-up
