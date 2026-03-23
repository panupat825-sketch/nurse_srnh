<?php

ob_start();

require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json; charset=UTF-8');

function ajax_json_response($payload, $statusCode)
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    http_response_code((int)$statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!is_admin_logged_in()) {
    ajax_json_response(array('success' => false, 'message' => 'Unauthorized'), 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ajax_json_response(array('success' => false, 'message' => 'Method not allowed'), 405);
}

$payload = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($payload)) {
    $payload = $_POST;
}

$id = isset($payload['id']) ? (int)$payload['id'] : 0;
if ($id <= 0) {
    ajax_json_response(array('success' => false, 'message' => 'Invalid connection id'), 400);
}

$stmt = $db->prepare('DELETE FROM personnel_connections WHERE id = :id LIMIT 1');
$stmt->execute(array('id' => $id));

ajax_json_response(array(
    'success' => true,
    'deleted' => $stmt->rowCount() > 0,
), 200);
