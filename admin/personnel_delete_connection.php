<?php

require_once __DIR__ . '/../bootstrap.php';

require_admin_login();

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array('success' => false, 'message' => 'Method not allowed'));
    exit;
}

$payload = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($payload)) {
    $payload = $_POST;
}

$id = isset($payload['id']) ? (int)$payload['id'] : 0;
if ($id <= 0) {
    echo json_encode(array('success' => false, 'message' => 'ไม่พบรหัสเส้นเชื่อมที่ต้องการลบ'), JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $db->prepare('DELETE FROM personnel_connections WHERE id = :id LIMIT 1');
$stmt->execute(array('id' => $id));

echo json_encode(array(
    'success' => true,
    'deleted' => $stmt->rowCount() > 0,
), JSON_UNESCAPED_UNICODE);
