-- App-specific persistence: PostgreSQL-backed sessions and durable scheduler status.
-- Safe to run both during fresh initdb and via `make migrate` on an existing volume.
CREATE TABLE IF NOT EXISTS app_sessions (
    session_id varchar(128) PRIMARY KEY,
    payload text NOT NULL,
    expires_at timestamptz NOT NULL
);
CREATE INDEX IF NOT EXISTS app_sessions_expires_at_idx ON app_sessions (expires_at);

CREATE TABLE IF NOT EXISTS app_cron_status (
    job_name text PRIMARY KEY,
    last_started_at timestamptz,
    last_success_at timestamptz,
    last_duration_seconds double precision NOT NULL DEFAULT 0,
    consecutive_failures bigint NOT NULL DEFAULT 0,
    runs_success_total bigint NOT NULL DEFAULT 0,
    runs_failed_total bigint NOT NULL DEFAULT 0,
    last_error text
);
INSERT INTO app_cron_status (job_name) VALUES ('cleanup_sessions') ON CONFLICT (job_name) DO NOTHING;
