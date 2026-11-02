#!/bin/sh
set -eu
php -r '
foreach (["DB_HOST", "DB_PORT", "DB_NAME", "DB_USER", "DB_PASSWORD", "SESSION_TTL"] as $key) {
    $value = getenv($key);
    if ($value !== false) {
        printf("export %s=%s\n", $key, escapeshellarg($value));
    }
}
' > /run/app-env
chmod 0644 /run/app-env
exec cron -f -L 15
