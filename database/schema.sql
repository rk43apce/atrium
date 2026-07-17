PRAGMA foreign_keys = ON;
PRAGMA journal_mode = WAL;

CREATE TABLE IF NOT EXISTS imports (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    filename VARCHAR(255) NOT NULL,
    checksum CHAR(64) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'processing',
    imported_count INTEGER NOT NULL DEFAULT 0,
    rejected_count INTEGER NOT NULL DEFAULT 0,
    duplicate_count INTEGER NOT NULL DEFAULT 0,
    skipped_count INTEGER NOT NULL DEFAULT 0,
    duration_ms INTEGER NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME,
    CONSTRAINT imports_checksum_unique UNIQUE (checksum)
);

CREATE TABLE IF NOT EXISTS transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    import_id INTEGER NOT NULL,
    transaction_id VARCHAR(64) NOT NULL,
    occurred_at DATETIME NOT NULL,
    terminal_id VARCHAR(64) NOT NULL,
    card_number VARCHAR(64) NOT NULL,
    account VARCHAR(64) NOT NULL,
    amount_cents INTEGER NOT NULL,
    transaction_type VARCHAR(20) NOT NULL,
    status VARCHAR(20) NOT NULL,
    merchant_id VARCHAR(64) NOT NULL,
    merchant_name VARCHAR(160) NOT NULL,
    currency CHAR(3) NOT NULL,
    external_reference VARCHAR(100) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT transactions_transaction_id_unique UNIQUE (transaction_id),
    CONSTRAINT transactions_import_fk FOREIGN KEY (import_id) REFERENCES imports(id)
);

CREATE INDEX IF NOT EXISTS transactions_occurred_at_idx ON transactions (occurred_at);
CREATE INDEX IF NOT EXISTS transactions_merchant_idx ON transactions (merchant_name);
CREATE INDEX IF NOT EXISTS transactions_currency_status_idx ON transactions (currency, status);
CREATE INDEX IF NOT EXISTS transactions_type_idx ON transactions (transaction_type);

CREATE TABLE IF NOT EXISTS rejected_rows (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    import_id INTEGER NOT NULL,
    row_number INTEGER NOT NULL,
    original_row TEXT NOT NULL,
    validation_errors TEXT NOT NULL,
    rejection_reason VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT rejected_rows_import_fk FOREIGN KEY (import_id) REFERENCES imports(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS rejected_rows_import_idx ON rejected_rows (import_id, row_number);
