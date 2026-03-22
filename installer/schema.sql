CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(191) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS content_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section VARCHAR(120) NOT NULL,
    title VARCHAR(255) NOT NULL,
    subtitle VARCHAR(255) NULL,
    body TEXT NULL,
    url VARCHAR(500) NULL,
    image_path VARCHAR(500) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_section (section),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS personnel (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    position_name VARCHAR(255) NULL,
    department_name VARCHAR(255) NULL,
    unit_name VARCHAR(255) NULL,
    profile_image VARCHAR(500) NULL,
    phone VARCHAR(50) NULL,
    internal_phone VARCHAR(50) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    x_position INT NULL,
    y_position INT NULL,
    parent_id INT UNSIGNED NULL,
    status TINYINT(1) NOT NULL DEFAULT 1,
    note TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_sort_order (sort_order),
    INDEX idx_department_name (department_name),
    INDEX idx_position_name (position_name),
    INDEX idx_parent_id (parent_id),
    CONSTRAINT fk_personnel_parent FOREIGN KEY (parent_id)
        REFERENCES personnel(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS personnel_connections (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    source_personnel_id INT UNSIGNED NOT NULL,
    target_personnel_id INT UNSIGNED NOT NULL,
    relation_type VARCHAR(50) NOT NULL DEFAULT 'direct',
    line_style VARCHAR(20) NOT NULL DEFAULT 'solid',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_source_personnel (source_personnel_id),
    INDEX idx_target_personnel (target_personnel_id),
    UNIQUE KEY uq_source_target (source_personnel_id, target_personnel_id),
    CONSTRAINT fk_conn_source_personnel FOREIGN KEY (source_personnel_id)
        REFERENCES personnel(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_conn_target_personnel FOREIGN KEY (target_personnel_id)
        REFERENCES personnel(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
