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

$sourceId = isset($payload['source_personnel_id']) ? (int)$payload['source_personnel_id'] : 0;
$targetId = isset($payload['target_personnel_id']) ? (int)$payload['target_personnel_id'] : 0;
$relationType = trim(isset($payload['relation_type']) ? (string)$payload['relation_type'] : 'direct');
$lineStyle = trim(isset($payload['line_style']) ? (string)$payload['line_style'] : 'solid');

if ($sourceId <= 0 || $targetId <= 0) {
    ajax_json_response(array('success' => false, 'message' => 'Invalid source/target'), 400);
}
if ($sourceId === $targetId) {
    ajax_json_response(array('success' => false, 'message' => 'Source and target cannot be same'), 400);
}
if ($relationType === '') {
    $relationType = 'direct';
}
if (strlen($relationType) > 50) {
    $relationType = substr($relationType, 0, 50);
}

$allowedLineStyle = array('solid', 'dashed', 'dotted');
if (!in_array($lineStyle, $allowedLineStyle, true)) {
    $lineStyle = 'solid';
}

$stmt = $db->prepare('SELECT COUNT(*) FROM personnel WHERE id IN (:source_id, :target_id)');
$stmt->execute(array(
    'source_id' => $sourceId,
    'target_id' => $targetId,
));
$existsCount = (int)$stmt->fetchColumn();
if ($existsCount < 2) {
    ajax_json_response(array('success' => false, 'message' => 'Personnel not found'), 400);
}

$stmt = $db->prepare('SELECT id FROM personnel_connections
                      WHERE source_personnel_id = :source_id
                      AND target_personnel_id = :target_id
                      LIMIT 1');
$stmt->execute(array(
    'source_id' => $sourceId,
    'target_id' => $targetId,
));
$duplicate = $stmt->fetch(PDO::FETCH_ASSOC);

if ($duplicate) {
    ajax_json_response(array(
        'success' => true,
        'message' => 'Connection already exists',
        'connection_id' => (int)$duplicate['id'],
    ), 200);
}

$stmt = $db->prepare('INSERT INTO personnel_connections (
    source_personnel_id,
    target_personnel_id,
    relation_type,
    line_style,
    created_at
) VALUES (
    :source_id,
    :target_id,
    :relation_type,
    :line_style,
    NOW()
)');

$stmt->execute(array(
    'source_id' => $sourceId,
    'target_id' => $targetId,
    'relation_type' => $relationType,
    'line_style' => $lineStyle,
));

ajax_json_response(array(
    'success' => true,
    'message' => 'Connection saved',
    'connection_id' => (int)$db->lastInsertId(),
), 200);
