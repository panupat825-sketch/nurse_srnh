<?php

require_once __DIR__ . '/../bootstrap.php';

require_admin_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/nurse_srnh/admin/personnel.php');
}

function personnel_error_and_redirect($message)
{
    flash('error', $message);
    redirect('/nurse_srnh/admin/personnel.php');
}

function remove_personnel_image_file($relativePath)
{
    $relativePath = trim((string)$relativePath);
    if ($relativePath === '' || strpos($relativePath, 'uploads/personnel/') !== 0) {
        return;
    }

    $absolutePath = __DIR__ . '/../' . $relativePath;
    if (is_file($absolutePath)) {
        @unlink($absolutePath);
    }
}

function personnel_upload_image(&$error)
{
    $error = null;

    if (!isset($_FILES['profile_image_file']) || !isset($_FILES['profile_image_file']['error'])) {
        return null;
    }

    if ((int)$_FILES['profile_image_file']['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ((int)$_FILES['profile_image_file']['error'] !== UPLOAD_ERR_OK) {
        $error = 'เกิดข้อผิดพลาดระหว่างอัปโหลดรูปภาพ';
        return null;
    }

    $tmpPath = $_FILES['profile_image_file']['tmp_name'];
    $fileSize = isset($_FILES['profile_image_file']['size']) ? (int)$_FILES['profile_image_file']['size'] : 0;
    $originalName = isset($_FILES['profile_image_file']['name']) ? (string)$_FILES['profile_image_file']['name'] : '';

    if (!is_uploaded_file($tmpPath)) {
        $error = 'ไม่พบไฟล์อัปโหลดที่ถูกต้อง';
        return null;
    }

    if ($fileSize <= 0 || $fileSize > (5 * 1024 * 1024)) {
        $error = 'ขนาดไฟล์ต้องไม่เกิน 5MB';
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
        $error = 'รองรับเฉพาะไฟล์ jpg, jpeg, png, gif, webp เท่านั้น';
        return null;
    }

    $extFromName = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowedExt = array('jpg', 'jpeg', 'png', 'gif', 'webp');
    if ($extFromName !== '' && !in_array($extFromName, $allowedExt, true)) {
        $error = 'นามสกุลไฟล์ไม่ถูกต้อง';
        return null;
    }

    $monthDir = date('Ym');
    $relativeDir = 'uploads/personnel/' . $monthDir;
    $absoluteDir = __DIR__ . '/../' . $relativeDir;

    if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0755, true)) {
        $error = 'ไม่สามารถสร้างโฟลเดอร์อัปโหลดได้';
        return null;
    }

    $filename = date('Ymd_His') . '_' . substr(md5(uniqid('', true)), 0, 12) . '.' . $mimeMap[$mime];
    $targetPath = $absoluteDir . '/' . $filename;

    if (!move_uploaded_file($tmpPath, $targetPath)) {
        $error = 'ไม่สามารถบันทึกไฟล์รูปได้';
        return null;
    }

    return $relativeDir . '/' . $filename;
}

$id = (isset($_POST['id']) && trim((string)$_POST['id']) !== '') ? (int)$_POST['id'] : null;
$fullName = trim(isset($_POST['full_name']) ? (string)$_POST['full_name'] : '');
$positionName = trim(isset($_POST['position_name']) ? (string)$_POST['position_name'] : '');
$departmentName = trim(isset($_POST['department_name']) ? (string)$_POST['department_name'] : '');
$unitName = trim(isset($_POST['unit_name']) ? (string)$_POST['unit_name'] : '');
$phone = trim(isset($_POST['phone']) ? (string)$_POST['phone'] : '');
$internalPhone = trim(isset($_POST['internal_phone']) ? (string)$_POST['internal_phone'] : '');
$sortOrder = isset($_POST['sort_order']) ? (int)$_POST['sort_order'] : 0;
$status = isset($_POST['status']) ? (int)$_POST['status'] : 1;
$note = trim(isset($_POST['note']) ? (string)$_POST['note'] : '');
$parentIdRaw = isset($_POST['parent_id']) ? trim((string)$_POST['parent_id']) : '';
$parentId = $parentIdRaw === '' ? null : (int)$parentIdRaw;

if ($fullName === '') {
    personnel_error_and_redirect('กรุณาระบุชื่อ-นามสกุล');
}
if (strlen($fullName) > 255 || strlen($positionName) > 255 || strlen($departmentName) > 255 || strlen($unitName) > 255) {
    personnel_error_and_redirect('ข้อมูลชื่อ/ตำแหน่ง/แผนก/หน่วยงานยาวเกินกำหนด');
}
if (strlen($phone) > 50 || strlen($internalPhone) > 50) {
    personnel_error_and_redirect('ข้อมูลเบอร์โทรยาวเกินกำหนด');
}
if (strlen($note) > 4000) {
    personnel_error_and_redirect('หมายเหตุยาวเกินกำหนด');
}
if ($status !== 1 && $status !== 0) {
    $status = 1;
}
if ($parentId !== null && $parentId <= 0) {
    $parentId = null;
}
if ($id !== null && $parentId !== null && $id === $parentId) {
    personnel_error_and_redirect('ไม่สามารถเลือก parent เป็นรายการเดียวกันได้');
}

$existingImage = trim(isset($_POST['existing_profile_image']) ? (string)$_POST['existing_profile_image'] : '');

if ($id !== null) {
    $stmt = $db->prepare('SELECT id, profile_image FROM personnel WHERE id = :id LIMIT 1');
    $stmt->execute(array('id' => $id));
    $existingRow = $stmt->fetch();
    if (!$existingRow) {
        personnel_error_and_redirect('ไม่พบข้อมูลบุคลากรที่ต้องการแก้ไข');
    }
    if ($existingImage === '') {
        $existingImage = trim((string)$existingRow['profile_image']);
    }
}

$uploadError = null;
$newUploadedImage = personnel_upload_image($uploadError);
if ($uploadError !== null) {
    personnel_error_and_redirect($uploadError);
}

$removeImage = isset($_POST['remove_profile_image']);
$profileImage = $existingImage;

if ($removeImage) {
    $profileImage = '';
}
if ($newUploadedImage !== null) {
    $profileImage = $newUploadedImage;
}

if ($parentId !== null) {
    $stmt = $db->prepare('SELECT id FROM personnel WHERE id = :id LIMIT 1');
    $stmt->execute(array('id' => $parentId));
    if (!$stmt->fetch()) {
        personnel_error_and_redirect('ไม่พบข้อมูล parent ที่เลือก');
    }
}

try {
    if ($id === null) {
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
            'profile_image' => $profileImage,
            'phone' => $phone,
            'internal_phone' => $internalPhone,
            'sort_order' => $sortOrder,
            'parent_id' => $parentId,
            'status' => $status,
            'note' => $note,
        ));

        flash('success', 'เพิ่มข้อมูลบุคลากรเรียบร้อยแล้ว');
    } else {
        $stmt = $db->prepare('UPDATE personnel SET
            full_name = :full_name,
            position_name = :position_name,
            department_name = :department_name,
            unit_name = :unit_name,
            profile_image = :profile_image,
            phone = :phone,
            internal_phone = :internal_phone,
            sort_order = :sort_order,
            parent_id = :parent_id,
            status = :status,
            note = :note,
            updated_at = NOW()
            WHERE id = :id
            LIMIT 1');

        $stmt->execute(array(
            'full_name' => $fullName,
            'position_name' => $positionName,
            'department_name' => $departmentName,
            'unit_name' => $unitName,
            'profile_image' => $profileImage,
            'phone' => $phone,
            'internal_phone' => $internalPhone,
            'sort_order' => $sortOrder,
            'parent_id' => $parentId,
            'status' => $status,
            'note' => $note,
            'id' => $id,
        ));

        flash('success', 'แก้ไขข้อมูลบุคลากรเรียบร้อยแล้ว');
    }
} catch (Exception $e) {
    if ($newUploadedImage !== null) {
        remove_personnel_image_file($newUploadedImage);
    }
    personnel_error_and_redirect('ไม่สามารถบันทึกข้อมูลได้');
}

if ($newUploadedImage !== null && $existingImage !== '' && $existingImage !== $newUploadedImage) {
    remove_personnel_image_file($existingImage);
}
if ($removeImage && $existingImage !== '' && $newUploadedImage === null) {
    remove_personnel_image_file($existingImage);
}

redirect('/nurse_srnh/admin/personnel.php');
