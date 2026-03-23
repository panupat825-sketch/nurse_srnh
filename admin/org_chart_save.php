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

function post_str($key, $maxLen = null, $default = '')
{
    $value = isset($_POST[$key]) ? trim((string)$_POST[$key]) : $default;
    if ($maxLen !== null && function_exists('mb_substr')) {
        $value = mb_substr($value, 0, (int)$maxLen, 'UTF-8');
    }
    return $value;
}

function post_int($key, $default = 0)
{
    if (!isset($_POST[$key]) || $_POST[$key] === '') {
        return (int)$default;
    }
    return (int)$_POST[$key];
}

function normalize_status($value)
{
    return ((int)$value === 0) ? 0 : 1;
}

function normalize_personnel_type($value)
{
    $allowed = array('executive', 'department_head', 'staff', 'assistant');
    $v = trim((string)$value);
    return in_array($v, $allowed, true) ? $v : 'staff';
}

function normalize_staff_profile_image($value)
{
    $path = trim((string)$value);
    if ($path === '') {
        return '';
    }

    $path = str_replace('\\', '/', $path);
    $path = preg_replace('#^https?://[^/]+/nurse_srnh/#i', '', $path);
    $path = preg_replace('#^/nurse_srnh/#i', '', $path);
    $path = ltrim($path, '/');
    $path = preg_replace('/\.\.+/', '', $path);
    if (strpos($path, 'uploads/personnel/') !== 0) {
        return '';
    }

    return $path;
}
function get_chart_by_id($db, $chartId)
{
    $stmt = $db->prepare('SELECT * FROM org_charts WHERE id = :id LIMIT 1');
    $stmt->execute(array('id' => (int)$chartId));
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function get_node_by_id($db, $nodeId)
{
    $stmt = $db->prepare('SELECT * FROM org_chart_nodes WHERE id = :id LIMIT 1');
    $stmt->execute(array('id' => (int)$nodeId));
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function make_upload_dir()
{
    $relativeDir = 'uploads/personnel';
    $absoluteDir = __DIR__ . '/../' . $relativeDir;

    if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0755, true)) {
        return array(null, null, 'Cannot create upload directory');
    }

    return array($relativeDir, $absoluteDir, null);
}

function handle_personnel_upload(&$error)
{
    $error = null;

    if (!isset($_FILES['profile_image']) || !isset($_FILES['profile_image']['error'])) {
        return null;
    }

    if ((int)$_FILES['profile_image']['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ((int)$_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Image upload failed';
        return null;
    }

    $tmpPath = $_FILES['profile_image']['tmp_name'];
    $size = isset($_FILES['profile_image']['size']) ? (int)$_FILES['profile_image']['size'] : 0;
    $originalName = isset($_FILES['profile_image']['name']) ? (string)$_FILES['profile_image']['name'] : '';

    if (!is_uploaded_file($tmpPath)) {
        $error = 'Invalid uploaded file';
        return null;
    }

    if ($size <= 0 || $size > (5 * 1024 * 1024)) {
        $error = 'Image size must be <= 5MB';
        return null;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string)$finfo->file($tmpPath);

    $mimeMap = array(
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    );

    if (!isset($mimeMap[$mime])) {
        $error = 'Allowed image types: jpg, jpeg, png, gif, webp';
        return null;
    }

    $extFromName = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowedExt = array('jpg', 'jpeg', 'png', 'gif', 'webp');
    if ($extFromName !== '' && !in_array($extFromName, $allowedExt, true)) {
        $error = 'Invalid image extension';
        return null;
    }

    list($relativeDir, $absoluteDir, $dirErr) = make_upload_dir();
    if ($dirErr !== null) {
        $error = $dirErr;
        return null;
    }

    if (function_exists('random_bytes')) {
        try {
            $rand = bin2hex(random_bytes(16));
        } catch (Exception $e) {
            $rand = md5(uniqid('', true));
        }
    } elseif (function_exists('openssl_random_pseudo_bytes')) {
        $bytes = openssl_random_pseudo_bytes(16);
        if ($bytes !== false) {
            $rand = bin2hex($bytes);
        } else {
            $rand = md5(uniqid('', true));
        }
    } else {
        $rand = md5(uniqid('', true));
    }

    $filename = date('Ymd_His') . '_' . $rand . '.' . $mimeMap[$mime];
    $target = $absoluteDir . '/' . $filename;

    if (!move_uploaded_file($tmpPath, $target)) {
        $error = 'Cannot save uploaded image';
        return null;
    }

    return $relativeDir . '/' . $filename;
}

function delete_image_file($relativePath)
{
    $relativePath = trim((string)$relativePath);
    if ($relativePath === '') {
        return;
    }

    if (strpos($relativePath, 'uploads/personnel/') !== 0) {
        return;
    }

    $full = __DIR__ . '/../' . $relativePath;
    if (is_file($full)) {
        @unlink($full);
    }
}

function fetch_descendant_ids($db, $nodeId)
{
    $result = array();
    $queue = array((int)$nodeId);
    $stmt = $db->prepare('SELECT id FROM org_chart_nodes WHERE parent_node_id = :parent_node_id');

    while (!empty($queue)) {
        $current = array_shift($queue);
        $result[] = $current;

        $stmt->execute(array('parent_node_id' => $current));
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            $cid = (int)$r['id'];
            if (!in_array($cid, $result, true) && !in_array($cid, $queue, true)) {
                $queue[] = $cid;
            }
        }
    }

    return $result;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Method not allowed', array(), 405);
}

$action = post_str('action', 64, '');
if ($action === '') {
    json_response(false, 'action is required', array(), 422);
}

if ($action === 'create_main_chart') {
    $chartName = post_str('chart_name', 191, '');
    $fullName = post_str('full_name', 255, '');
    $positionName = post_str('position_name', 255, '');
    $departmentName = post_str('department_name', 191, '');
    $unitName = post_str('unit_name', 191, '');
    $phone = post_str('phone', 50, '');
    $internalPhone = post_str('internal_phone', 50, '');
    $note = post_str('note', 4000, '');
    $status = normalize_status(post_int('status', 1));

    if ($chartName === '' || $fullName === '') {
        json_response(false, 'chart_name and full_name are required');
    }

    $uploadErr = null;
    $imagePath = handle_personnel_upload($uploadErr);
    if ($uploadErr !== null) {
        json_response(false, $uploadErr);
    }
    $staffImagePath = normalize_staff_profile_image(post_str('staff_profile_image', 500, ''));
    if ($imagePath === null && $staffImagePath !== '') {
        $imagePath = $staffImagePath;
    }

    try {
        $stmtExists = $db->prepare('SELECT id, chart_name FROM org_charts WHERE chart_type = :chart_type AND status = 1 LIMIT 1');
        $stmtExists->execute(array('chart_type' => 'main'));
        $existingMain = $stmtExists->fetch(PDO::FETCH_ASSOC);
        if ($existingMain) {
            json_response(true, 'Main chart already exists', array(
                'already_exists' => true,
                'chart_id' => (int)$existingMain['id'],
                'chart_name' => (string)$existingMain['chart_name'],
            ));
        }

        $db->beginTransaction();

        $stmtChart = $db->prepare('INSERT INTO org_charts (
            chart_name, chart_type, department_name, parent_chart_id, main_source_node_id, root_node_id,
            sort_order, status, created_at, updated_at
        ) VALUES (
            :chart_name, :chart_type, NULL, NULL, NULL, NULL,
            0, :status, NOW(), NOW()
        )');
        $stmtChart->execute(array(
            'chart_name' => $chartName,
            'chart_type' => 'main',
            'status' => $status,
        ));
        $chartId = (int)$db->lastInsertId();

        $stmtNode = $db->prepare('INSERT INTO org_chart_nodes (
            chart_id, parent_node_id, personnel_type, full_name, position_name, department_name, unit_name,
            profile_image, phone, internal_phone, note,
            x_position, y_position, sort_order, level_no, status, created_at, updated_at
        ) VALUES (
            :chart_id, NULL, :personnel_type, :full_name, :position_name, :department_name, :unit_name,
            :profile_image, :phone, :internal_phone, :note,
            :x_position, :y_position, 0, 0, :status, NOW(), NOW()
        )');
        $stmtNode->execute(array(
            'chart_id' => $chartId,
            'personnel_type' => 'executive',
            'full_name' => $fullName,
            'position_name' => $positionName,
            'department_name' => $departmentName,
            'unit_name' => $unitName,
            'profile_image' => $imagePath ? $imagePath : '',
            'phone' => $phone,
            'internal_phone' => $internalPhone,
            'note' => $note,
            'x_position' => 520,
            'y_position' => 60,
            'status' => $status,
        ));
        $nodeId = (int)$db->lastInsertId();

        $stmtUpdateChart = $db->prepare('UPDATE org_charts SET root_node_id = :root_node_id, updated_at = NOW() WHERE id = :id LIMIT 1');
        $stmtUpdateChart->execute(array('root_node_id' => $nodeId, 'id' => $chartId));

        $db->commit();

        json_response(true, 'Main chart created', array('chart_id' => $chartId, 'node_id' => $nodeId));
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        if ($imagePath) {
            delete_image_file($imagePath);
        }
        json_response(false, 'Unable to create main chart: ' . $e->getMessage(), array(), 500);
    }
}

if ($action === 'create_node') {
    $chartId = post_int('chart_id', 0);
    $parentNodeIdRaw = post_str('parent_node_id', 20, '');
    $parentNodeId = ($parentNodeIdRaw === '') ? null : (int)$parentNodeIdRaw;

    $personnelType = normalize_personnel_type(post_str('personnel_type', 50, 'staff'));
    $fullName = post_str('full_name', 255, '');
    $positionName = post_str('position_name', 255, '');
    $departmentName = post_str('department_name', 191, '');
    $unitName = post_str('unit_name', 191, '');
    $phone = post_str('phone', 50, '');
    $internalPhone = post_str('internal_phone', 50, '');
    $note = post_str('note', 4000, '');
    $status = normalize_status(post_int('status', 1));
    $sortOrder = post_int('sort_order', 0);
    $x = isset($_POST['x_position']) && $_POST['x_position'] !== '' ? (int)$_POST['x_position'] : null;
    $y = isset($_POST['y_position']) && $_POST['y_position'] !== '' ? (int)$_POST['y_position'] : null;

    if ($chartId <= 0 || $fullName === '') {
        json_response(false, 'chart_id and full_name are required');
    }

    $chart = get_chart_by_id($db, $chartId);
    if (!$chart) {
        json_response(false, 'Chart not found');
    }

    $stmtDup = $db->prepare("SELECT id FROM org_chart_nodes WHERE chart_id = :chart_id AND LOWER(TRIM(full_name)) = :full_name_norm AND LOWER(TRIM(COALESCE(position_name, ''))) = :position_name_norm LIMIT 1");
    $stmtDup->execute(array(
        'chart_id' => $chartId,
        'full_name_norm' => function_exists('mb_strtolower') ? mb_strtolower(trim($fullName), 'UTF-8') : strtolower(trim($fullName)),
        'position_name_norm' => function_exists('mb_strtolower') ? mb_strtolower(trim($positionName), 'UTF-8') : strtolower(trim($positionName)),
    ));
    if ($stmtDup->fetch(PDO::FETCH_ASSOC)) {
        json_response(false, 'Duplicate personnel (same full name and position) in this chart');
    }

    $levelNo = 0;
    if ($parentNodeId !== null) {
        if ($parentNodeId <= 0) {
            json_response(false, 'Invalid parent_node_id');
        }

        $parentNode = get_node_by_id($db, $parentNodeId);
        if (!$parentNode || (int)$parentNode['chart_id'] !== $chartId) {
            json_response(false, 'Parent node not found in this chart');
        }

        $levelNo = (int)$parentNode['level_no'] + 1;
    }

    if ($x !== null && $x < 0) {
        $x = 0;
    }
    if ($y !== null && $y < 0) {
        $y = 0;
    }
    if ($sortOrder < 0) {
        $sortOrder = 0;
    }

    $uploadErr = null;
    $imagePath = handle_personnel_upload($uploadErr);
    if ($uploadErr !== null) {
        json_response(false, $uploadErr);
    }
    $staffImagePath = normalize_staff_profile_image(post_str('staff_profile_image', 500, ''));
    if ($imagePath === null && $staffImagePath !== '') {
        $imagePath = $staffImagePath;
    }

    try {
        $db->beginTransaction();

        $stmt = $db->prepare('INSERT INTO org_chart_nodes (
            chart_id, parent_node_id, personnel_type, full_name, position_name, department_name, unit_name,
            profile_image, phone, internal_phone, note,
            x_position, y_position, sort_order, level_no, status, created_at, updated_at
        ) VALUES (
            :chart_id, :parent_node_id, :personnel_type, :full_name, :position_name, :department_name, :unit_name,
            :profile_image, :phone, :internal_phone, :note,
            :x_position, :y_position, :sort_order, :level_no, :status, NOW(), NOW()
        )');

        $stmt->execute(array(
            'chart_id' => $chartId,
            'parent_node_id' => $parentNodeId,
            'personnel_type' => $personnelType,
            'full_name' => $fullName,
            'position_name' => $positionName,
            'department_name' => $departmentName,
            'unit_name' => $unitName,
            'profile_image' => $imagePath ? $imagePath : '',
            'phone' => $phone,
            'internal_phone' => $internalPhone,
            'note' => $note,
            'x_position' => $x,
            'y_position' => $y,
            'sort_order' => $sortOrder,
            'level_no' => $levelNo,
            'status' => $status,
        ));

        $newNodeId = (int)$db->lastInsertId();

        if ($parentNodeId !== null) {
            $stmtChk = $db->prepare('SELECT id FROM org_chart_connections WHERE chart_id = :chart_id AND source_node_id = :source_node_id AND target_node_id = :target_node_id LIMIT 1');
            $stmtChk->execute(array(
                'chart_id' => $chartId,
                'source_node_id' => $parentNodeId,
                'target_node_id' => $newNodeId,
            ));

            if (!$stmtChk->fetch(PDO::FETCH_ASSOC)) {
                $stmtConn = $db->prepare('INSERT INTO org_chart_connections (
                    chart_id, source_node_id, target_node_id, relation_type, line_style, created_at
                ) VALUES (
                    :chart_id, :source_node_id, :target_node_id, :relation_type, :line_style, NOW()
                )');
                $stmtConn->execute(array(
                    'chart_id' => $chartId,
                    'source_node_id' => $parentNodeId,
                    'target_node_id' => $newNodeId,
                    'relation_type' => 'direct',
                    'line_style' => 'solid',
                ));
            }
        }

        $db->commit();

        $freshNode = get_node_by_id($db, $newNodeId);
        json_response(true, 'Node created', array('node' => $freshNode));
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        if ($imagePath) {
            delete_image_file($imagePath);
        }
        json_response(false, 'Unable to create node', array(), 500);
    }
}

if ($action === 'update_node') {
    $nodeId = post_int('node_id', 0);
    if ($nodeId <= 0) {
        json_response(false, 'Invalid node_id');
    }

    $node = get_node_by_id($db, $nodeId);
    if (!$node) {
        json_response(false, 'Node not found');
    }

    $fullName = post_str('full_name', 255, '');
    $positionName = post_str('position_name', 255, '');
    $departmentName = post_str('department_name', 191, '');
    $unitName = post_str('unit_name', 191, '');
    $phone = post_str('phone', 50, '');
    $internalPhone = post_str('internal_phone', 50, '');
    $note = post_str('note', 4000, '');
    $status = normalize_status(post_int('status', (int)$node['status']));
    $personnelType = normalize_personnel_type(post_str('personnel_type', 50, $node['personnel_type']));

    if ($fullName === '') {
        json_response(false, 'full_name is required');
    }

    $stmtDup = $db->prepare("SELECT id FROM org_chart_nodes WHERE chart_id = :chart_id AND LOWER(TRIM(full_name)) = :full_name_norm AND LOWER(TRIM(COALESCE(position_name, ''))) = :position_name_norm AND id <> :id LIMIT 1");
    $stmtDup->execute(array(
        'chart_id' => (int)$node['chart_id'],
        'full_name_norm' => function_exists('mb_strtolower') ? mb_strtolower(trim($fullName), 'UTF-8') : strtolower(trim($fullName)),
        'position_name_norm' => function_exists('mb_strtolower') ? mb_strtolower(trim($positionName), 'UTF-8') : strtolower(trim($positionName)),
        'id' => $nodeId,
    ));
    if ($stmtDup->fetch(PDO::FETCH_ASSOC)) {
        json_response(false, 'Duplicate personnel (same full name and position) in this chart');
    }

    $uploadErr = null;
    $newImagePath = handle_personnel_upload($uploadErr);
    if ($uploadErr !== null) {
        json_response(false, $uploadErr);
    }

        $staffImagePath = normalize_staff_profile_image(post_str('staff_profile_image', 500, ''));

    $finalImage = (string)$node['profile_image'];
    if ($newImagePath !== null) {
        $finalImage = $newImagePath;
    } elseif ($staffImagePath !== '') {
        $finalImage = $staffImagePath;
    }

    try {
        $stmt = $db->prepare('UPDATE org_chart_nodes SET
            personnel_type = :personnel_type,
            full_name = :full_name,
            position_name = :position_name,
            department_name = :department_name,
            unit_name = :unit_name,
            profile_image = :profile_image,
            phone = :phone,
            internal_phone = :internal_phone,
            note = :note,
            status = :status,
            updated_at = NOW()
            WHERE id = :id LIMIT 1');

        $stmt->execute(array(
            'personnel_type' => $personnelType,
            'full_name' => $fullName,
            'position_name' => $positionName,
            'department_name' => $departmentName,
            'unit_name' => $unitName,
            'profile_image' => $finalImage,
            'phone' => $phone,
            'internal_phone' => $internalPhone,
            'note' => $note,
            'status' => $status,
            'id' => $nodeId,
        ));

        if ($newImagePath !== null && trim((string)$node['profile_image']) !== '' && (string)$node['profile_image'] !== $newImagePath) {
            delete_image_file($node['profile_image']);
        }

        $fresh = get_node_by_id($db, $nodeId);
        json_response(true, 'Node updated', array('node' => $fresh));
    } catch (Exception $e) {
        if ($newImagePath !== null) {
            delete_image_file($newImagePath);
        }
        json_response(false, 'Unable to update node', array(), 500);
    }
}

if ($action === 'delete_node') {
    $nodeId = post_int('node_id', 0);
    $deleteMode = post_str('delete_mode', 20, 'subtree');

    if ($nodeId <= 0) {
        json_response(false, 'Invalid node_id');
    }
    if ($deleteMode !== 'subtree' && $deleteMode !== 'reparent') {
        json_response(false, 'delete_mode must be subtree or reparent');
    }

    $node = get_node_by_id($db, $nodeId);
    if (!$node) {
        json_response(false, 'Node not found');
    }

    $chartId = (int)$node['chart_id'];
    $parentId = ($node['parent_node_id'] === null) ? null : (int)$node['parent_node_id'];

    try {
        $db->beginTransaction();

        $deletedNodes = 0;
        $deletedConnections = 0;
        $reparentedChildren = 0;

        if ($deleteMode === 'subtree') {
            $ids = fetch_descendant_ids($db, $nodeId);
            if (empty($ids)) {
                $ids = array($nodeId);
            }

            $in = implode(',', array_fill(0, count($ids), '?'));

            $stmtImg = $db->prepare('SELECT profile_image FROM org_chart_nodes WHERE id IN (' . $in . ')');
            $stmtImg->execute($ids);
            $imgs = $stmtImg->fetchAll(PDO::FETCH_ASSOC);

            $paramsConn = $ids;
            foreach ($ids as $id2) {
                $paramsConn[] = $id2;
            }
            $stmtDelConn = $db->prepare('DELETE FROM org_chart_connections WHERE source_node_id IN (' . $in . ') OR target_node_id IN (' . $in . ')');
            $stmtDelConn->execute($paramsConn);
            $deletedConnections = (int)$stmtDelConn->rowCount();

            $stmtDelNodes = $db->prepare('DELETE FROM org_chart_nodes WHERE id IN (' . $in . ')');
            $stmtDelNodes->execute($ids);
            $deletedNodes = (int)$stmtDelNodes->rowCount();

            foreach ($imgs as $imgRow) {
                if (!empty($imgRow['profile_image'])) {
                    delete_image_file($imgRow['profile_image']);
                }
            }

            $stmtRootFix = $db->prepare('UPDATE org_charts SET root_node_id = NULL, updated_at = NOW() WHERE id = ? AND root_node_id IN (' . $in . ')');
            $paramsRoot = array_merge(array($chartId), $ids);
            $stmtRootFix->execute($paramsRoot);
        } else {
            $stmtChildren = $db->prepare('SELECT id FROM org_chart_nodes WHERE parent_node_id = :parent_node_id');
            $stmtChildren->execute(array('parent_node_id' => $nodeId));
            $children = $stmtChildren->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($children)) {
                $stmtReparent = $db->prepare('UPDATE org_chart_nodes SET parent_node_id = :new_parent_id, level_no = CASE WHEN level_no > 0 THEN level_no - 1 ELSE 0 END, updated_at = NOW() WHERE parent_node_id = :old_parent_id');
                $stmtReparent->execute(array('new_parent_id' => $parentId, 'old_parent_id' => $nodeId));
                $reparentedChildren = (int)$stmtReparent->rowCount();
            }

            $stmtDelConn = $db->prepare('DELETE FROM org_chart_connections WHERE source_node_id = :node_id OR target_node_id = :node_id');
            $stmtDelConn->execute(array('node_id' => $nodeId));
            $deletedConnections = (int)$stmtDelConn->rowCount();

            if ($parentId !== null && !empty($children)) {
                $stmtChkConn = $db->prepare('SELECT id FROM org_chart_connections WHERE chart_id = :chart_id AND source_node_id = :source_node_id AND target_node_id = :target_node_id LIMIT 1');
                $stmtAddConn = $db->prepare('INSERT INTO org_chart_connections (chart_id, source_node_id, target_node_id, relation_type, line_style, created_at) VALUES (:chart_id, :source_node_id, :target_node_id, :relation_type, :line_style, NOW())');

                foreach ($children as $child) {
                    $cid = (int)$child['id'];
                    $stmtChkConn->execute(array('chart_id' => $chartId, 'source_node_id' => $parentId, 'target_node_id' => $cid));
                    if (!$stmtChkConn->fetch(PDO::FETCH_ASSOC)) {
                        $stmtAddConn->execute(array(
                            'chart_id' => $chartId,
                            'source_node_id' => $parentId,
                            'target_node_id' => $cid,
                            'relation_type' => 'direct',
                            'line_style' => 'solid',
                        ));
                    }
                }
            }

            $stmtDelNode = $db->prepare('DELETE FROM org_chart_nodes WHERE id = :id LIMIT 1');
            $stmtDelNode->execute(array('id' => $nodeId));
            $deletedNodes = (int)$stmtDelNode->rowCount();

            if (!empty($node['profile_image'])) {
                delete_image_file($node['profile_image']);
            }
        }

        $db->commit();

        json_response(true, 'Node deleted', array(
            'mode' => $deleteMode,
            'deleted_nodes' => $deletedNodes,
            'deleted_connections' => $deletedConnections,
            'reparented_children' => $reparentedChildren,
        ));
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        json_response(false, 'Unable to delete node', array(), 500);
    }
}

if ($action === 'create_department_chart') {
    $mainChartId = post_int('main_chart_id', 0);
    $mainNodeId = post_int('main_node_id', 0);

    if ($mainChartId <= 0 || $mainNodeId <= 0) {
        json_response(false, 'main_chart_id and main_node_id are required');
    }

    $mainChart = get_chart_by_id($db, $mainChartId);
    if (!$mainChart || (string)$mainChart['chart_type'] !== 'main') {
        json_response(false, 'Main chart not found');
    }

    $mainNode = get_node_by_id($db, $mainNodeId);
    if (!$mainNode || (int)$mainNode['chart_id'] !== $mainChartId) {
        json_response(false, 'Main node not found in main chart');
    }

    if ((string)$mainNode['personnel_type'] !== 'department_head') {
        json_response(false, 'main_node_id must be department_head');
    }

    try {
        $stmtLink = $db->prepare('SELECT * FROM org_chart_department_links WHERE main_chart_id = :main_chart_id AND main_node_id = :main_node_id LIMIT 1');
        $stmtLink->execute(array('main_chart_id' => $mainChartId, 'main_node_id' => $mainNodeId));
        $existing = $stmtLink->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $depChart = get_chart_by_id($db, (int)$existing['department_chart_id']);
            $rootNode = $depChart && !empty($depChart['root_node_id']) ? get_node_by_id($db, (int)$depChart['root_node_id']) : null;
            json_response(true, 'Department chart already exists', array(
                'already_exists' => true,
                'department_chart' => $depChart,
                'root_node' => $rootNode,
            ));
        }

        $db->beginTransaction();

        $chartName = 'Department: ' . ($mainNode['department_name'] !== '' ? $mainNode['department_name'] : $mainNode['full_name']);

        $stmtChart = $db->prepare('INSERT INTO org_charts (
            chart_name, chart_type, department_name, parent_chart_id, main_source_node_id, root_node_id,
            sort_order, status, created_at, updated_at
        ) VALUES (
            :chart_name, :chart_type, :department_name, :parent_chart_id, :main_source_node_id, NULL,
            0, 1, NOW(), NOW()
        )');
        $stmtChart->execute(array(
            'chart_name' => $chartName,
            'chart_type' => 'department',
            'department_name' => $mainNode['department_name'],
            'parent_chart_id' => $mainChartId,
            'main_source_node_id' => $mainNodeId,
        ));
        $departmentChartId = (int)$db->lastInsertId();

        $stmtRoot = $db->prepare('INSERT INTO org_chart_nodes (
            chart_id, parent_node_id, personnel_type, full_name, position_name, department_name, unit_name,
            profile_image, phone, internal_phone, note,
            x_position, y_position, sort_order, level_no, status, created_at, updated_at
        ) VALUES (
            :chart_id, NULL, :personnel_type, :full_name, :position_name, :department_name, :unit_name,
            :profile_image, :phone, :internal_phone, :note,
            :x_position, :y_position, 0, 0, :status, NOW(), NOW()
        )');
        $stmtRoot->execute(array(
            'chart_id' => $departmentChartId,
            'personnel_type' => 'department_head',
            'full_name' => $mainNode['full_name'],
            'position_name' => $mainNode['position_name'],
            'department_name' => $mainNode['department_name'],
            'unit_name' => $mainNode['unit_name'],
            'profile_image' => $mainNode['profile_image'],
            'phone' => $mainNode['phone'],
            'internal_phone' => $mainNode['internal_phone'],
            'note' => $mainNode['note'],
            'x_position' => 420,
            'y_position' => 40,
            'status' => normalize_status($mainNode['status']),
        ));
        $rootNodeId = (int)$db->lastInsertId();

        $stmtUpdate = $db->prepare('UPDATE org_charts SET root_node_id = :root_node_id, updated_at = NOW() WHERE id = :id LIMIT 1');
        $stmtUpdate->execute(array('root_node_id' => $rootNodeId, 'id' => $departmentChartId));

        $stmtLinkIns = $db->prepare('INSERT INTO org_chart_department_links (main_chart_id, main_node_id, department_chart_id, created_at) VALUES (:main_chart_id, :main_node_id, :department_chart_id, NOW())');
        $stmtLinkIns->execute(array(
            'main_chart_id' => $mainChartId,
            'main_node_id' => $mainNodeId,
            'department_chart_id' => $departmentChartId,
        ));

        $db->commit();

        $depChart = get_chart_by_id($db, $departmentChartId);
        $rootNode = get_node_by_id($db, $rootNodeId);

        json_response(true, 'Department chart created', array(
            'already_exists' => false,
            'department_chart' => $depChart,
            'root_node' => $rootNode,
        ));
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        json_response(false, 'Unable to create department chart', array(), 500);
    }
}

if ($action === 'delete_chart') {
    $chartId = post_int('chart_id', 0);
    $deleteMode = post_str('delete_mode', 20, 'block');

    if ($chartId <= 0) {
        json_response(false, 'Invalid chart_id');
    }

    $chart = get_chart_by_id($db, $chartId);
    if (!$chart) {
        json_response(false, 'Chart not found');
    }

    try {
        $db->beginTransaction();

        $chartsToDelete = array($chartId);

        if ((string)$chart['chart_type'] === 'main') {
            $stmtChildren = $db->prepare('SELECT id FROM org_charts WHERE parent_chart_id = :parent_chart_id');
            $stmtChildren->execute(array('parent_chart_id' => $chartId));
            $children = $stmtChildren->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($children) && $deleteMode !== 'cascade') {
                $db->rollBack();
                json_response(false, 'Main chart has department charts. Use delete_mode=cascade to remove all.');
            }

            foreach ($children as $c) {
                $chartsToDelete[] = (int)$c['id'];
            }
        }

        $stmtImages = $db->prepare('SELECT profile_image FROM org_chart_nodes WHERE chart_id = :chart_id');
        $stmtCountNodes = $db->prepare('SELECT COUNT(*) c FROM org_chart_nodes WHERE chart_id = :chart_id');
        $stmtDeleteChart = $db->prepare('DELETE FROM org_charts WHERE id = :id LIMIT 1');

        $deletedCharts = 0;
        $deletedNodes = 0;

        foreach ($chartsToDelete as $cid) {
            $stmtImages->execute(array('chart_id' => $cid));
            $imgRows = $stmtImages->fetchAll(PDO::FETCH_ASSOC);

            $stmtCountNodes->execute(array('chart_id' => $cid));
            $cnt = $stmtCountNodes->fetch(PDO::FETCH_ASSOC);
            $deletedNodes += isset($cnt['c']) ? (int)$cnt['c'] : 0;

            $stmtDeleteChart->execute(array('id' => $cid));
            $deletedCharts += (int)$stmtDeleteChart->rowCount();

            foreach ($imgRows as $img) {
                if (!empty($img['profile_image'])) {
                    delete_image_file($img['profile_image']);
                }
            }
        }

        $db->commit();

        json_response(true, 'Chart deleted', array(
            'deleted_charts' => $deletedCharts,
            'deleted_nodes' => $deletedNodes,
        ));
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        json_response(false, 'Unable to delete chart', array(), 500);
    }
}

json_response(false, 'Unsupported action', array(), 422);









