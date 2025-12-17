CREATE TABLE IF NOT EXISTS clients (
    id SERIAL PRIMARY KEY,
    domain VARCHAR(255) NOT NULL,
    code VARCHAR(255) NOT NULL,
    title VARCHAR(255) NOT NULL,
    app_sid VARCHAR(255) NOT NULL,
    active VARCHAR(1) NOT NULL DEFAULT 'Y',
    created_at TIMESTAMP DEFAULT NOW(),

    CONSTRAINT clients_code_unique UNIQUE (code)
);