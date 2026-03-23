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


CREATE TABLE IF NOT EXISTS departments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    department_code VARCHAR(50) NULL,
    department_name VARCHAR(191) NOT NULL,
    department_desc TEXT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_department_code (department_code),
    UNIQUE KEY uq_department_name (department_name),
    INDEX idx_active (is_active),
    INDEX idx_sort_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS positions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    position_code VARCHAR(50) NULL,
    position_name VARCHAR(191) NOT NULL,
    department_name VARCHAR(191) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    note TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_position_code (position_code),
    UNIQUE KEY uq_position_name (position_name),
    INDEX idx_active (is_active),
    INDEX idx_sort_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS personnel (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    position_name VARCHAR(255) NULL,
    position_level_id INT UNSIGNED NULL,
    department_name VARCHAR(255) NULL,
    workgroup_id INT UNSIGNED NULL,
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
    INDEX idx_department_name (department_name(191)),
    INDEX idx_position_name (position_name(191)),
    INDEX idx_position_level_id (position_level_id),
    INDEX idx_workgroup_id (workgroup_id),
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



CREATE TABLE IF NOT EXISTS org_charts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    chart_name VARCHAR(191) NOT NULL,
    chart_type ENUM('main','department') NOT NULL DEFAULT 'main',
    department_name VARCHAR(191) NULL,
    parent_chart_id INT UNSIGNED NULL,
    main_source_node_id INT UNSIGNED NULL,
    root_node_id INT UNSIGNED NULL,
    sort_order INT NOT NULL DEFAULT 0,
    status TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_chart_type (chart_type),
    INDEX idx_parent_chart (parent_chart_id),
    INDEX idx_main_source_node (main_source_node_id),
    INDEX idx_root_node (root_node_id),
    INDEX idx_status (status),
    INDEX idx_sort_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS org_chart_nodes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    chart_id INT UNSIGNED NOT NULL,
    parent_node_id INT UNSIGNED NULL,
    personnel_type VARCHAR(50) NOT NULL DEFAULT 'staff',
    full_name VARCHAR(255) NOT NULL,
    position_name VARCHAR(255) NULL,
    position_level_id INT UNSIGNED NULL,
    department_name VARCHAR(191) NULL,
    unit_name VARCHAR(191) NULL,
    profile_image VARCHAR(500) NULL,
    phone VARCHAR(50) NULL,
    internal_phone VARCHAR(50) NULL,
    note TEXT NULL,
    x_position INT NULL,
    y_position INT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    level_no INT NOT NULL DEFAULT 0,
    status TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_chart_id (chart_id),
    INDEX idx_parent_node_id (parent_node_id),
    INDEX idx_personnel_type (personnel_type),
    INDEX idx_status (status),
    INDEX idx_sort_order (sort_order),
    INDEX idx_level_no (level_no),
    CONSTRAINT fk_org_nodes_chart FOREIGN KEY (chart_id)
        REFERENCES org_charts(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_org_nodes_parent FOREIGN KEY (parent_node_id)
        REFERENCES org_chart_nodes(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS org_chart_connections (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    chart_id INT UNSIGNED NOT NULL,
    source_node_id INT UNSIGNED NOT NULL,
    target_node_id INT UNSIGNED NOT NULL,
    relation_type VARCHAR(50) NOT NULL DEFAULT 'direct',
    line_style VARCHAR(20) NOT NULL DEFAULT 'solid',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_chart_id (chart_id),
    INDEX idx_source_node_id (source_node_id),
    INDEX idx_target_node_id (target_node_id),
    UNIQUE KEY uq_chart_source_target (chart_id, source_node_id, target_node_id),
    CONSTRAINT fk_org_conn_chart FOREIGN KEY (chart_id)
        REFERENCES org_charts(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_org_conn_source FOREIGN KEY (source_node_id)
        REFERENCES org_chart_nodes(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_org_conn_target FOREIGN KEY (target_node_id)
        REFERENCES org_chart_nodes(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS org_chart_department_links (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    main_chart_id INT UNSIGNED NOT NULL,
    main_node_id INT UNSIGNED NOT NULL,
    department_chart_id INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_main_node_department_chart (main_chart_id, main_node_id, department_chart_id),
    INDEX idx_main_chart_id (main_chart_id),
    INDEX idx_main_node_id (main_node_id),
    INDEX idx_department_chart_id (department_chart_id),
    CONSTRAINT fk_org_link_main_chart FOREIGN KEY (main_chart_id)
        REFERENCES org_charts(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_org_link_main_node FOREIGN KEY (main_node_id)
        REFERENCES org_chart_nodes(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_org_link_department_chart FOREIGN KEY (department_chart_id)
        REFERENCES org_charts(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS position_levels (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    level_code VARCHAR(50) NULL,
    level_name VARCHAR(191) NOT NULL,
    rank_no INT NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    note TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_level_code (level_code),
    UNIQUE KEY uq_level_name (level_name),
    INDEX idx_rank_no (rank_no),
    INDEX idx_sort_order (sort_order),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS workgroups (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    group_code VARCHAR(50) NULL,
    group_name VARCHAR(191) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    note TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_group_code (group_code),
    UNIQUE KEY uq_group_name (group_name),
    INDEX idx_sort_order (sort_order),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS subdepartments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workgroup_id INT UNSIGNED NOT NULL,
    subdepartment_code VARCHAR(50) NULL,
    subdepartment_name VARCHAR(191) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    note TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_subdepartment_code (subdepartment_code),
    UNIQUE KEY uq_workgroup_subname (workgroup_id, subdepartment_name),
    INDEX idx_workgroup_id (workgroup_id),
    INDEX idx_sort_order (sort_order),
    INDEX idx_is_active (is_active),
    CONSTRAINT fk_subdepartments_workgroup FOREIGN KEY (workgroup_id)
        REFERENCES workgroups(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS org_containers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    container_name VARCHAR(191) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_sort_order (sort_order),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS org_container_members (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    container_id INT UNSIGNED NOT NULL,
    personnel_id INT UNSIGNED NOT NULL,
    level_no INT NOT NULL DEFAULT 99,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_container_personnel (container_id, personnel_id),
    INDEX idx_container_level_sort (container_id, level_no, sort_order),
    CONSTRAINT fk_ocm_container FOREIGN KEY (container_id)
        REFERENCES org_containers(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_ocm_personnel FOREIGN KEY (personnel_id)
        REFERENCES personnel(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
