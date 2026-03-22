<?php

require_once __DIR__ . '/../bootstrap.php';

require_admin_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/nurse_srnh/admin/personnel.php');
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    flash('error', 'ไม่พบรหัสบุคลากรที่ต้องการลบ');
    redirect('/nurse_srnh/admin/personnel.php');
}

$stmt = $db->prepare('SELECT id, profile_image FROM personnel WHERE id = :id LIMIT 1');
$stmt->execute(array('id' => $id));
$row = $stmt->fetch();

if (!$row) {
    flash('error', 'ไม่พบข้อมูลบุคลากรที่ต้องการลบ');
    redirect('/nurse_srnh/admin/personnel.php');
}

$imagePath = trim((string)$row['profile_image']);

$db->beginTransaction();

try {
    $stmt = $db->prepare('DELETE FROM personnel_connections WHERE source_personnel_id = :id OR target_personnel_id = :id');
    $stmt->execute(array('id' => $id));

    $stmt = $db->prepare('UPDATE personnel SET parent_id = NULL WHERE parent_id = :id');
    $stmt->execute(array('id' => $id));

    $stmt = $db->prepare('DELETE FROM personnel WHERE id = :id LIMIT 1');
    $stmt->execute(array('id' => $id));

    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    flash('error', 'ไม่สามารถลบข้อมูลได้');
    redirect('/nurse_srnh/admin/personnel.php');
}

if ($imagePath !== '' && strpos($imagePath, 'uploads/personnel/') === 0) {
    $absolute = __DIR__ . '/../' . $imagePath;
    if (is_file($absolute)) {
        @unlink($absolute);
    }
}

flash('success', 'ลบข้อมูลบุคลากรเรียบร้อยแล้ว');
redirect('/nurse_srnh/admin/personnel.php');
