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

$positions = isset($payload['positions']) && is_array($payload['positions']) ? $payload['positions'] : array();

if (count($positions) === 0) {
    echo json_encode(array('success' => false, 'message' => 'ไม่พบข้อมูลตำแหน่งที่ต้องบันทึก'), JSON_UNESCAPED_UNICODE);
    exit;
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
    echo json_encode(array('success' => false, 'message' => 'ไม่สามารถบันทึก layout ได้'), JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(array(
    'success' => true,
    'saved_count' => $savedCount,
), JSON_UNESCAPED_UNICODE);
