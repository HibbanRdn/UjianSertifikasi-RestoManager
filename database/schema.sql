CREATE DATABASE IF NOT EXISTS restomanager
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE restomanager;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(20) NULL,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(16) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_users_email UNIQUE (email),
    CONSTRAINT chk_users_role CHECK (role IN ('customer', 'staff', 'admin')),
    CONSTRAINT chk_users_active CHECK (is_active IN (0, 1))
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_categories_name UNIQUE (name),
    CONSTRAINT chk_categories_active CHECK (is_active IN (0, 1))
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS menu_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    price_idr BIGINT UNSIGNED NOT NULL,
    image_path VARCHAR(255) NULL,
    is_visible TINYINT(1) NOT NULL DEFAULT 1,
    is_available TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_menu_items_category_name UNIQUE (category_id, name),
    CONSTRAINT fk_menu_items_category FOREIGN KEY (category_id) REFERENCES categories (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_menu_items_visible CHECK (is_visible IN (0, 1)),
    CONSTRAINT chk_menu_items_available CHECK (is_available IN (0, 1)),
    INDEX idx_menu_items_catalog (category_id, is_visible, is_available)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS dining_tables (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    table_code VARCHAR(20) NOT NULL,
    status VARCHAR(12) NOT NULL DEFAULT 'available',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_dining_tables_code UNIQUE (table_code),
    CONSTRAINT chk_dining_tables_status CHECK (status IN ('available', 'occupied')),
    CONSTRAINT chk_dining_tables_active CHECK (is_active IN (0, 1)),
    INDEX idx_dining_tables_selectable (is_active, status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS visits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    visit_code VARCHAR(40) NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    table_id BIGINT UNSIGNED NOT NULL,
    customer_name_snapshot VARCHAR(100) NOT NULL,
    status VARCHAR(12) NOT NULL DEFAULT 'active',
    started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    closed_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    active_user_id BIGINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN status = 'active' THEN user_id ELSE NULL END) STORED,
    active_table_id BIGINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN status = 'active' THEN table_id ELSE NULL END) STORED,
    CONSTRAINT uq_visits_code UNIQUE (visit_code),
    CONSTRAINT uq_visits_active_user UNIQUE (active_user_id),
    CONSTRAINT uq_visits_active_table UNIQUE (active_table_id),
    CONSTRAINT fk_visits_user FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_visits_table FOREIGN KEY (table_id) REFERENCES dining_tables (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_visits_status CHECK (status IN ('active', 'closed')),
    CONSTRAINT chk_visits_closed_at CHECK ((status = 'active' AND closed_at IS NULL) OR (status = 'closed' AND closed_at IS NOT NULL)),
    INDEX idx_visits_user_status (user_id, status),
    INDEX idx_visits_table_status (table_id, status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_code VARCHAR(40) NOT NULL,
    visit_id BIGINT UNSIGNED NOT NULL,
    idempotency_key VARCHAR(80) NOT NULL,
    status VARCHAR(12) NOT NULL DEFAULT 'received',
    total_idr BIGINT UNSIGNED NOT NULL,
    estimated_minutes_min SMALLINT UNSIGNED NOT NULL DEFAULT 15,
    estimated_minutes_max SMALLINT UNSIGNED NOT NULL DEFAULT 25,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    served_at DATETIME NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_orders_code UNIQUE (order_code),
    CONSTRAINT uq_orders_idempotency UNIQUE (idempotency_key),
    CONSTRAINT fk_orders_visit FOREIGN KEY (visit_id) REFERENCES visits (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_orders_status CHECK (status IN ('received', 'served')),
    CONSTRAINT chk_orders_served_at CHECK ((status = 'received' AND served_at IS NULL) OR (status = 'served' AND served_at IS NOT NULL)),
    CONSTRAINT chk_orders_estimate CHECK (estimated_minutes_min <= estimated_minutes_max),
    INDEX idx_orders_visit_status (visit_id, status),
    INDEX idx_orders_created_at (created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS order_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    menu_item_id BIGINT UNSIGNED NOT NULL,
    menu_name_snapshot VARCHAR(150) NOT NULL,
    price_idr_snapshot BIGINT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    line_total_idr BIGINT UNSIGNED NOT NULL,
    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_order_items_menu FOREIGN KEY (menu_item_id) REFERENCES menu_items (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_order_items_quantity CHECK (quantity BETWEEN 1 AND 99),
    CONSTRAINT chk_order_items_line_total CHECK (line_total_idr = quantity * price_idr_snapshot),
    INDEX idx_order_items_order (order_id),
    INDEX idx_order_items_menu (menu_item_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    method VARCHAR(16) NOT NULL,
    status VARCHAR(24) NOT NULL DEFAULT 'paid_simulated',
    amount_idr BIGINT UNSIGNED NOT NULL,
    paid_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_payments_order UNIQUE (order_id),
    CONSTRAINT fk_payments_order FOREIGN KEY (order_id) REFERENCES orders (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_payments_method CHECK (method IN ('qris_sim', 'cash_sim')),
    CONSTRAINT chk_payments_status CHECK (status = 'paid_simulated'),
    INDEX idx_payments_method_paid_at (method, paid_at)
) ENGINE=InnoDB;
