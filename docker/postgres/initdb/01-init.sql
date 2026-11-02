-- Выполняется автоматически один-единственный раз — при инициализации
-- пустого тома pgdata. На уже существующем томе скрипт не запустится повторно.

CREATE TABLE demo (
    id serial PRIMARY KEY,
    created_at timestamptz NOT NULL DEFAULT now(),
    note text
);

INSERT INTO demo (note) VALUES ('Привет из initdb!');
