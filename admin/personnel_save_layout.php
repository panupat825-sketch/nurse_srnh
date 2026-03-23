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

$positions = isset($payload['positions']) && is_array($payload['positions']) ? $payload['positions'] : array();

if (count($positions) === 0) {
    ajax_json_response(array('success' => false, 'message' => 'No positions provided'), 400);
}

$stmt = $db->prepare('UPDATE personnel SET x_position = :x_position, y_position = :y_position, updated_at = NOW() WHERE id = :id');
$savedCount = 0;

$db->beginTransaction();

try {
    foreach ($positions as $item) {
        if (!is_array($item)) {
            continue;
        }

        $id = isset($item['id']) ? (int)$item['id'] : 0;
        $x = isset($item['x']) ? (int)$item['x'] : 0;
        $y = isset($item['y']) ? (int)$item['y'] : 0;

        if ($id <= 0) {
            continue;
        }

        if ($x < 0) {
            $x = 0;
        }
        if ($y < 0) {
            $y = 0;
        }

        $stmt->execute(array(
            'x_position' => $x,
            'y_position' => $y,
            'id' => $id,
        ));

        $savedCount++;
    }

    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    ajax_json_response(array('success' => false, 'message' => 'Unable to save layout'), 500);
}

ajax_json_response(array(
    'success' => true,
    'saved_count' => $savedCount,
), 200);
