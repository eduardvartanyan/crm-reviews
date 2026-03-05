CREATE TABLE IF NOT EXISTS clients (
    id SERIAL PRIMARY KEY,
    domain VARCHAR(255) NOT NULL,
    code VARCHAR(255) NOT NULL,
    title VARCHAR(255) NOT NULL,
    app_sid VARCHAR(255) NOT NULL,
    active VARCHAR(1) NOT NULL DEFAULT 'Y',
    created_at TIMESTAMP DEFAULT NOW(),
    notify VARCHAR(1) NOT NULL DEFAULT 'Y',
    no_repeat VARCHAR(1) NOT NULL DEFAULT 'Y',
    member_id VARCHAR(255),
    access_token TEXT,
    refresh_token TEXT,
    token_expires_at TIMESTAMP,
    client_endpoint VARCHAR(255),
    application_token VARCHAR(255),

    CONSTRAINT clients_code_unique UNIQUE (code)
);