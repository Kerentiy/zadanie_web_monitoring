-- User accounts for the application-level register/login/logout flow.
-- The email is normalized to lowercase by AuthService before insertion.
CREATE TABLE IF NOT EXISTS app_users (
    id bigserial PRIMARY KEY,
    email varchar(254) NOT NULL UNIQUE,
    password_hash text NOT NULL,
    created_at timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT app_users_email_nonempty CHECK (length(email) > 0)
);
