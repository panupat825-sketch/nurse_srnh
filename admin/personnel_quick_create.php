<?php
ob_start();

require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json; charset=UTF-8');

function quick_json($success, $message, $extra = array(), $status = 200)
{
    if (!headers_sent()) {
        http_response_code((int)$status);
    }

    $payload = array_merge(array(
        'success' => (bool)$success,
        'message' => (string)$message,
    ), $extra);

    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    quick_json(false, 'Method not allowed', array(), 405);
}

if (!is_admin_logged_in()) {
    quick_json(false, 'Please login to admin first.', array(), 401);
}

$fullName = trim(isset($_POST['full_name']) ? (string)$_POST['full_name'] : '');
$positionName = trim(isset($_POST['position_name']) ? (string)$_POST['position_name'] : '');
$departmentName = trim(isset($_POST['department_name']) ? (string)$_POST['department_name'] : '');
$unitName = trim(isset($_POST['unit_name']) ? (string)$_POST['unit_name'] : '');
$note = trim(isset($_POST['note']) ? (string)$_POST['note'] : '');
$sortOrder = isset($_POST['sort_order']) ? (int)$_POST['sort_order'] : 0;
$parentIdRaw = isset($_POST['parent_id']) ? trim((string)$_POST['parent_id']) : '';
$parentId = $parentIdRaw === '' ? null : (int)$parentIdRaw;

if ($fullName === '') {
    quick_json(false, 'Full name is required.');
}
if (strlen($fullName) > 255 || strlen($positionName) > 255 || strlen($departmentName) > 255 || strlen($unitName) > 255) {
    quick_json(false, 'Name/position/department/unit is too long.');
}
if (strlen($note) > 4000) {
    quick_json(false, 'Note is too long.');
}
if ($parentId !== null && $parentId <= 0) {
    $parentId = null;
}

if ($parentId !== null) {
    $stmt = $db->prepare('SELECT id FROM personnel WHERE id = :id LIMIT 1');
    $stmt->execute(array('id' => $parentId));
    if (!$stmt->fetch()) {
        quick_json(false, 'Selected parent was not found.');
    }
}

try {
    $stmt = $db->prepare('INSERT INTO personnel (
        full_name, position_name, department_name, unit_name, profile_image,
        phone, internal_phone, sort_order, x_position, y_position, parent_id, status, note,
        created_at, updated_at
    ) VALUES (
        :full_name, :position_name, :department_name, :unit_name, :profile_image,
        :phone, :internal_phone, :sort_order, NULL, NULL, :parent_id, :status, :note,
        NOW(), NOW()
    )');

    $stmt->execute(array(
        'full_name' => $fullName,
        'position_name' => $positionName,
        'department_name' => $departmentName,
        'unit_name' => $unitName,
        'profile_image' => '',
        'phone' => '',
        'internal_phone' => '',
        'sort_order' => $sortOrder,
        'parent_id' => $parentId,
        'status' => 1,
        'note' => $note,
    ));

    quick_json(true, 'Saved successfully.', array(
        'id' => (int)$db->lastInsertId(),
    ));
} catch (Exception $e) {
    quick_json(false, 'Unable to save data.');
}
