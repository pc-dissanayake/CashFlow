CREATE TABLE IF NOT EXISTS company_profiles (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    tagline VARCHAR(255) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    phone VARCHAR(50) DEFAULT NULL,
    email VARCHAR(255) DEFAULT NULL,
    website VARCHAR(255) DEFAULT NULL,
    logo_path VARCHAR(500) DEFAULT NULL,
    tax_id VARCHAR(100) DEFAULT NULL,
    registration_no VARCHAR(100) DEFAULT NULL,
    bank_name VARCHAR(255) DEFAULT NULL,
    bank_account VARCHAR(100) DEFAULT NULL,
    bank_branch VARCHAR(255) DEFAULT NULL,
    signature_path VARCHAR(500) DEFAULT NULL,
    is_default BOOLEAN DEFAULT FALSE,
    user_id INTEGER NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS client_profiles (
    id SERIAL PRIMARY KEY,
    company_name VARCHAR(255) NOT NULL,
    contact_person VARCHAR(255) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    phone VARCHAR(50) DEFAULT NULL,
    email VARCHAR(255) DEFAULT NULL,
    tax_id VARCHAR(100) DEFAULT NULL,
    user_id INTEGER NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS invoices (
    id SERIAL PRIMARY KEY,
    uuid VARCHAR(64) NOT NULL UNIQUE,
    document_type VARCHAR(20) NOT NULL DEFAULT 'invoice',
    document_number VARCHAR(50) NOT NULL,
    company_profile_id INTEGER NOT NULL REFERENCES company_profiles(id),
    client_profile_id INTEGER NOT NULL REFERENCES client_profiles(id),
    project_title VARCHAR(500) DEFAULT NULL,
    location VARCHAR(500) DEFAULT NULL,
    requested_by VARCHAR(255) DEFAULT NULL,
    client_email VARCHAR(255) DEFAULT NULL,
    currency_code VARCHAR(10) NOT NULL DEFAULT 'LKR',
    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    tax_label VARCHAR(50) DEFAULT 'VAT',
    tax_rate DECIMAL(5,2) DEFAULT 0.00,
    tax_amount DECIMAL(15,2) DEFAULT 0.00,
    total DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    status VARCHAR(20) NOT NULL DEFAULT 'draft',
    is_paid BOOLEAN DEFAULT FALSE,
    paid_date DATE DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    issue_date DATE NOT NULL,
    due_date DATE DEFAULT NULL,
    user_id INTEGER NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS invoice_items (
    id SERIAL PRIMARY KEY,
    invoice_id INTEGER NOT NULL REFERENCES invoices(id) ON DELETE CASCADE,
    item_number INTEGER NOT NULL DEFAULT 1,
    description TEXT NOT NULL,
    quantity DECIMAL(10,2) NOT NULL DEFAULT 1,
    unit_price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_invoices_user_id ON invoices(user_id);
CREATE INDEX IF NOT EXISTS idx_invoices_uuid ON invoices(uuid);
CREATE INDEX IF NOT EXISTS idx_invoices_document_type ON invoices(document_type);
CREATE INDEX IF NOT EXISTS idx_invoice_items_invoice_id ON invoice_items(invoice_id);
CREATE INDEX IF NOT EXISTS idx_company_profiles_user_id ON company_profiles(user_id);
CREATE INDEX IF NOT EXISTS idx_client_profiles_user_id ON client_profiles(user_id);
