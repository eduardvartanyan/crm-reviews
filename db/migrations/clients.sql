CREATE TABLE IF NOT EXISTS clients (
    id SERIAL PRIMARY KEY,
    domain VARCHAR(255) NOT NULL,
    code VARCHAR(255) NOT NULL,
    title VARCHAR(255) NOT NULL,
    app_sid VARCHAR(255) NOT NULL,
    active VARCHAR(1) NOT NULL DEFAULT 'Y',
    created_at TIMESTAMP DEFAULT NOW(),
    web_hook VARCHAR(255) NOT NULL DEFAULT '-',
    notify VARCAR(1) NOT NULL DEFAULT 'Y',
    no_repeat VARCHAR(1) NOT NULL DEFAULT 'Y',

    CONSTRAINT clients_code_unique UNIQUE (code)
);