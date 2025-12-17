CREATE TABLE IF NOT EXISTS reviews (
    id SERIAL PRIMARY KEY,
    client_id INTEGER NOT NULL,
    contact_id INTEGER CHECK (contact_id BETWEEN 1 AND 9999999),
    deal_id INTEGER CHECK (deal_id BETWEEN 1 AND 9999999),
    rating SMALLINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT NOW(),

    CONSTRAINT reviews_client_fk
        FOREIGN KEY (client_id)
        REFERENCES clients (id)
        ON DELETE CASCADE
);