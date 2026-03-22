<?php

require_once __DIR__ . '/../bootstrap.php';

require_admin_login();

header('Content-Type: application/json; charset=UTF-8');

$search = trim(isset($_GET['search']) ? (string)$_GET['search'] : '');
$status = trim(isset($_GET['status']) ? (string)$_GET['status'] : 'all');
$department = trim(isset($_GET['department']) ? (string)$_GET['department'] : '');
$position = trim(isset($_GET['position']) ? (string)$_GET['position'] : '');

$sql = 'SELECT
    id, full_name, position_name, department_name, unit_name, profile_image,
    phone, internal_phone, sort_order, x_position, y_position, parent_id,
    status, note, created_at, updated_at
    FROM personnel
    WHERE 1=1';
$params = array();

if ($search !== '') {
    $sql .= ' AND (
        full_name LIKE :search
        OR department_name LIKE :search
        OR position_name LIKE :search
        OR unit_name LIKE :search
    )';
    $params['search'] = '%' . $search . '%';
}

if ($status === 'active') {
    $sql .= ' AND status = 1';
} elseif ($status === 'inactive') {
    $sql .= ' AND status = 0';
}

if ($department !== '') {
    $sql .= ' AND department_name LIKE :department';
    $params['department'] = '%' . $department . '%';
}

if ($position !== '') {
    $sql .= ' AND position_name LIKE :position';
    $params['position'] = '%' . $position . '%';
}

$sql .= ' ORDER BY sort_order ASC, id ASC';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$personnel = $stmt->fetchAll();

for ($i = 0; $i < count($personnel); $i++) {
    $personnel[$i]['id'] = (int)$personnel[$i]['id'];
    $personnel[$i]['sort_order'] = (int)$personnel[$i]['sort_order'];
    $personnel[$i]['x_position'] = ($personnel[$i]['x_position'] !== null) ? (int)$personnel[$i]['x_position'] : null;
    $personnel[$i]['y_position'] = ($personnel[$i]['y_position'] !== null) ? (int)$personnel[$i]['y_position'] : null;
    $personnel[$i]['parent_id'] = ($personnel[$i]['parent_id'] !== null) ? (int)$personnel[$i]['parent_id'] : null;
    $personnel[$i]['status'] = (int)$personnel[$i]['status'];
}

$stmt = $db->query('SELECT id, source_personnel_id, target_personnel_id, relation_type, line_style, created_at
                    FROM personnel_connections
                    ORDER BY id ASC');
$connections = $stmt->fetchAll();

for ($i = 0; $i < count($connections); $i++) {
    $connections[$i]['id'] = (int)$connections[$i]['id'];
    $connections[$i]['source_personnel_id'] = (int)$connections[$i]['source_personnel_id'];
    $connections[$i]['target_personnel_id'] = (int)$connections[$i]['target_personnel_id'];
}

$departments = $db->query("SELECT DISTINCT department_name FROM personnel WHERE department_name <> '' ORDER BY department_name ASC")->fetchAll(PDO::FETCH_COLUMN);
$positions = $db->query("SELECT DISTINCT position_name FROM personnel WHERE position_name <> '' ORDER BY position_name ASC")->fetchAll(PDO::FETCH_COLUMN);

echo json_encode(array(
    'success' => true,
    'personnel' => $personnel,
    'connections' => $connections,
    'filter_options' => array(
        'departments' => $departments,
        'positions' => $positions,
    ),
), JSON_UNESCAPED_UNICODE);
