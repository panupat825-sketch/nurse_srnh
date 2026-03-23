<?php
ob_start();

require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json; charset=UTF-8');

if (!is_admin_logged_in()) {
    http_response_code(401);
    echo json_encode(array('success' => false, 'message' => 'Unauthorized'), JSON_UNESCAPED_UNICODE);
    exit;
}

require_admin_login();

function json_response($success, $message, $extra = array(), $status = 200)
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
    json_response(false, 'Method not allowed', array(), 405);
}

$raw = file_get_contents('php://input');
if ($raw === false) {
    json_response(false, 'Invalid request body');
}

$raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);
$payload = json_decode($raw, true);
if (!is_array($payload)) {
    json_response(false, 'Invalid JSON');
}

$items = isset($payload['items']) && is_array($payload['items']) ? $payload['items'] : array();
if (empty($items)) {
    json_response(false, 'items is required');
}

try {
    $db->beginTransaction();

    $stmtExists = $db->prepare('SELECT id FROM org_chart_nodes WHERE id = :id LIMIT 1');
    $stmtUpdate = $db->prepare('UPDATE org_chart_nodes SET x_position = :x_position, y_position = :y_position, updated_at = NOW() WHERE id = :id LIMIT 1');

    $updated = 0;
    $skipped = 0;

    foreach ($items as $item) {
        if (!is_array($item)) {
            $skipped++;
            continue;
        }

        $nodeId = isset($item['node_id']) ? (int)$item['node_id'] : 0;
        $x = isset($item['x_position']) ? (int)$item['x_position'] : null;
        $y = isset($item['y_position']) ? (int)$item['y_position'] : null;

        if ($nodeId <= 0 || $x === null || $y === null) {
            $skipped++;
            continue;
        }

        if ($x < 0) {
            $x = 0;
        }
        if ($y < 0) {
            $y = 0;
        }
        if ($x > 50000) {
            $x = 50000;
        }
        if ($y > 50000) {
            $y = 50000;
        }

        $stmtExists->execute(array('id' => $nodeId));
        if (!$stmtExists->fetch(PDO::FETCH_ASSOC)) {
            $skipped++;
            continue;
        }

        $stmtUpdate->execute(array(
            'id' => $nodeId,
            'x_position' => $x,
            'y_position' => $y,
        ));

        if ($stmtUpdate->rowCount() > 0) {
            $updated++;
        }
    }

    $db->commit();

    json_response(true, 'Layout saved', array(
        'updated_count' => $updated,
        'skipped_count' => $skipped,
    ));
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    json_response(false, 'Unable to save layout', array(), 500);
}
