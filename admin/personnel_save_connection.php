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

$sourceId = isset($payload['source_personnel_id']) ? (int)$payload['source_personnel_id'] : 0;
$targetId = isset($payload['target_personnel_id']) ? (int)$payload['target_personnel_id'] : 0;
$relationType = trim(isset($payload['relation_type']) ? (string)$payload['relation_type'] : 'direct');
$lineStyle = trim(isset($payload['line_style']) ? (string)$payload['line_style'] : 'solid');

if ($sourceId <= 0 || $targetId <= 0) {
    echo json_encode(array('success' => false, 'message' => 'ข้อมูลต้นทาง/ปลายทางไม่ถูกต้อง'), JSON_UNESCAPED_UNICODE);
    exit;
}
if ($sourceId === $targetId) {
    echo json_encode(array('success' => false, 'message' => 'ไม่สามารถเชื่อมรายการเดียวกันได้'), JSON_UNESCAPED_UNICODE);
    exit;
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
    echo json_encode(array('success' => false, 'message' => 'ไม่พบบุคลากรที่ต้องการเชื่อมบางรายการ'), JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $db->prepare('SELECT id FROM personnel_connections
                      WHERE source_personnel_id = :source_id
                      AND target_personnel_id = :target_id
                      LIMIT 1');
$stmt->execute(array(
    'source_id' => $sourceId,
    'target_id' => $targetId,
));
$duplicate = $stmt->fetch();

if ($duplicate) {
    echo json_encode(array(
        'success' => true,
        'message' => 'มีเส้นเชื่อมนี้อยู่แล้ว',
        'connection_id' => (int)$duplicate['id'],
    ), JSON_UNESCAPED_UNICODE);
    exit;
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

echo json_encode(array(
    'success' => true,
    'message' => 'บันทึกเส้นเชื่อมเรียบร้อยแล้ว',
    'connection_id' => (int)$db->lastInsertId(),
), JSON_UNESCAPED_UNICODE);
