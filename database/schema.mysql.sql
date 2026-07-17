CREATE TABLE IF NOT EXISTS imports (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    filename VARCHAR(255) NOT NULL,
    checksum CHAR(64) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'processing',
    imported_count INT UNSIGNED NOT NULL DEFAULT 0,
    rejected_count INT UNSIGNED NOT NULL DEFAULT 0,
    duplicate_count INT UNSIGNED NOT NULL DEFAULT 0,
    skipped_count INT UNSIGNED NOT NULL DEFAULT 0,
    duration_ms INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    CONSTRAINT imports_checksum_unique UNIQUE (checksum),
    CONSTRAINT imports_status_check CHECK (
        status IN ('processing', 'completed', 'failed')
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS transactions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    import_id BIGINT UNSIGNED NOT NULL,
    transaction_id VARCHAR(64) NOT NULL,
    occurred_at DATETIME NOT NULL,
    terminal_id VARCHAR(64) NOT NULL,
    card_number VARCHAR(64) NOT NULL,
    account VARCHAR(64) NOT NULL,
    amount_cents BIGINT UNSIGNED NOT NULL,
    transaction_type VARCHAR(20) NOT NULL,
    status VARCHAR(20) NOT NULL,
    merchant_id VARCHAR(64) NOT NULL,
    merchant_name VARCHAR(160) NOT NULL,
    currency CHAR(3) NOT NULL,
    external_reference TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT transactions_transaction_id_unique UNIQUE (transaction_id),
    CONSTRAINT transactions_import_fk FOREIGN KEY (import_id) REFERENCES imports (id),
    CONSTRAINT transactions_amount_check CHECK (amount_cents > 0),
    CONSTRAINT transactions_type_check CHECK (
        transaction_type IN ('debit', 'credit', 'reversal', 'adjustment')
    ),
    CONSTRAINT transactions_status_check CHECK (
        status IN ('approved', 'declined', 'reversed', 'pending')
    ),
    INDEX transactions_occurred_at_idx (occurred_at),
    INDEX transactions_merchant_idx (merchant_name),
    INDEX transactions_currency_status_idx (currency, status),
    INDEX transactions_type_idx (transaction_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rejected_rows (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    import_id BIGINT UNSIGNED NOT NULL,
    csv_row_number INT UNSIGNED NOT NULL,
    original_row JSON NOT NULL,
    validation_errors JSON NOT NULL,
    rejection_reason VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT rejected_rows_import_fk FOREIGN KEY (import_id)
        REFERENCES imports (id) ON DELETE CASCADE,
    INDEX rejected_rows_import_idx (import_id, csv_row_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
