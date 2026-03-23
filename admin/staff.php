<?php
require_once __DIR__ . '/../bootstrap.php';

require_admin_login();

function input_str($key, $maxLen = null)
{
    $value = isset($_POST[$key]) ? trim((string)$_POST[$key]) : '';
    if ($maxLen !== null && function_exists('mb_substr')) {
        $value = mb_substr($value, 0, (int)$maxLen, 'UTF-8');
    }
    return $value;
}

function input_int($key, $default = 0)
{
    if (!isset($_POST[$key]) || $_POST[$key] === '') {
        return (int)$default;
    }
    return (int)$_POST[$key];
}

function ensure_personnel_extra_columns($db)
{
    try {
        $stmt = $db->query("SHOW COLUMNS FROM personnel LIKE 'position_level_id'");
        if (!$stmt || !$stmt->fetch(PDO::FETCH_ASSOC)) {
            $db->exec("ALTER TABLE personnel ADD COLUMN position_level_id INT UNSIGNED NULL AFTER position_name");
        }
    } catch (Exception $e) {
    }

    try {
        $stmt = $db->query("SHOW COLUMNS FROM personnel LIKE 'workgroup_id'");
        if (!$stmt || !$stmt->fetch(PDO::FETCH_ASSOC)) {
            $db->exec("ALTER TABLE personnel ADD COLUMN workgroup_id INT UNSIGNED NULL AFTER department_name");
        }
    } catch (Exception $e) {
    }
}

function handle_staff_upload($fieldName, &$error)
{
    $error = null;

    if (!isset($_FILES[$fieldName]) || !isset($_FILES[$fieldName]['error'])) {
        return null;
    }

    if ((int)$_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ((int)$_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        $error = 'อัปโหลดรูปไม่สำเร็จ';
        return null;
    }

    $tmpPath = $_FILES[$fieldName]['tmp_name'];
    $size = isset($_FILES[$fieldName]['size']) ? (int)$_FILES[$fieldName]['size'] : 0;
    $originalName = isset($_FILES[$fieldName]['name']) ? (string)$_FILES[$fieldName]['name'] : '';

    if (!is_uploaded_file($tmpPath)) {
        $error = 'ไฟล์อัปโหลดไม่ถูกต้อง';
        return null;
    }

    if ($size <= 0 || $size > (5 * 1024 * 1024)) {
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
        $error = 'รองรับไฟล์ jpg, jpeg, png, gif, webp เท่านั้น';
        return null;
    }

    $extFromName = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowedExt = array('jpg', 'jpeg', 'png', 'gif', 'webp');
    if ($extFromName !== '' && !in_array($extFromName, $allowedExt, true)) {
        $error = 'นามสกุลไฟล์ไม่ถูกต้อง';
        return null;
    }

    $uploadDirRel = 'uploads/personnel';
    $uploadDirAbs = __DIR__ . '/../' . $uploadDirRel;
    if (!is_dir($uploadDirAbs) && !mkdir($uploadDirAbs, 0755, true)) {
        $error = 'ไม่สามารถสร้างโฟลเดอร์อัปโหลดได้';
        return null;
    }

    if (function_exists('random_bytes')) {
        try {
            $random = bin2hex(random_bytes(12));
        } catch (Exception $e) {
            $random = substr(md5(uniqid('', true)), 0, 24);
        }
    } elseif (function_exists('openssl_random_pseudo_bytes')) {
        $bytes = openssl_random_pseudo_bytes(12);
        if ($bytes !== false) {
            $random = bin2hex($bytes);
        } else {
            $random = substr(md5(uniqid('', true)), 0, 24);
        }
    } else {
        $random = substr(md5(uniqid('', true)), 0, 24);
    }

    $filename = date('Ymd_His') . '_' . $random . '.' . $mimeMap[$mime];
    $target = $uploadDirAbs . '/' . $filename;

    if (!move_uploaded_file($tmpPath, $target)) {
        $error = 'ไม่สามารถบันทึกรูปได้';
        return null;
    }

    return $uploadDirRel . '/' . $filename;
}

function remove_staff_image($path)
{
    $path = trim((string)$path);
    if ($path === '' || strpos($path, 'uploads/personnel/') !== 0) {
        return;
    }

    $fullPath = __DIR__ . '/../' . $path;
    if (is_file($fullPath)) {
        @unlink($fullPath);
    }
}

ensure_personnel_extra_columns($db);

$error = null;
$editItem = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? trim((string)$_POST['action']) : '';

    if ($action === 'save') {
        $id = input_int('id', 0);
        $fullName = input_str('full_name', 255);
        $positionName = input_str('position_name', 255);
        $positionLevelId = input_int('position_level_id', 0);
        $departmentName = input_str('department_name', 255);
        $workgroupId = input_int('workgroup_id', 0);
        $phone = input_str('phone', 50);
        $internalPhone = input_str('internal_phone', 50);
        $status = input_int('status', 1) === 1 ? 1 : 0;
        $note = input_str('note', 4000);
        $existingImage = input_str('existing_profile_image', 500);
        $removeImage = isset($_POST['remove_profile_image']) ? 1 : 0;

        if ($fullName === '') {
            $error = 'กรุณากรอกชื่อเจ้าหน้าที่';
        } else {
            $uploadError = null;
            $newImage = handle_staff_upload('profile_image_file', $uploadError);
            if ($uploadError !== null) {
                $error = $uploadError;
            } else {
                $finalImage = $existingImage;
                if ($removeImage === 1) {
                    $finalImage = '';
                }
                if ($newImage !== null) {
                    $finalImage = $newImage;
                }

                if ($error === null) {
                    if ($id > 0) {
                        $stmt = $db->prepare('UPDATE personnel SET
                            full_name = :full_name,
                            position_name = :position_name,
                            position_level_id = :position_level_id,
                            department_name = :department_name,
                            workgroup_id = :workgroup_id,
                            profile_image = :profile_image,
                            phone = :phone,
                            internal_phone = :internal_phone,
                            status = :status,
                            note = :note,
                            updated_at = NOW()
                            WHERE id = :id LIMIT 1');

                        $stmt->execute(array(
                            'full_name' => $fullName,
                            'position_name' => $positionName,
                            'position_level_id' => $positionLevelId > 0 ? $positionLevelId : null,
                            'department_name' => $departmentName,
                            'workgroup_id' => $workgroupId > 0 ? $workgroupId : null,
                            'profile_image' => $finalImage,
                            'phone' => $phone,
                            'internal_phone' => $internalPhone,
                            'status' => $status,
                            'note' => $note,
                            'id' => $id,
                        ));

                        if ($newImage !== null && $existingImage !== '' && $existingImage !== $newImage) {
                            remove_staff_image($existingImage);
                        } elseif ($removeImage === 1 && $existingImage !== '') {
                            remove_staff_image($existingImage);
                        }

                        flash('success', 'แก้ไขข้อมูลเจ้าหน้าที่เรียบร้อยแล้ว');
                    } else {
                        $stmt = $db->prepare('INSERT INTO personnel (
                            full_name, position_name, position_level_id, department_name, workgroup_id,
                            profile_image, phone, internal_phone,
                            status, note, created_at, updated_at
                        ) VALUES (
                            :full_name, :position_name, :position_level_id, :department_name, :workgroup_id,
                            :profile_image, :phone, :internal_phone,
                            :status, :note, NOW(), NOW()
                        )');

                        $stmt->execute(array(
                            'full_name' => $fullName,
                            'position_name' => $positionName,
                            'position_level_id' => $positionLevelId > 0 ? $positionLevelId : null,
                            'department_name' => $departmentName,
                            'workgroup_id' => $workgroupId > 0 ? $workgroupId : null,
                            'profile_image' => $finalImage,
                            'phone' => $phone,
                            'internal_phone' => $internalPhone,
                            'status' => $status,
                            'note' => $note,
                        ));

                        flash('success', 'เพิ่มเจ้าหน้าที่เรียบร้อยแล้ว');
                    }

                    redirect('/nurse_srnh/admin/staff.php');
                }
            }
        }

        if ($id > 0) {
            $stmt = $db->prepare('SELECT * FROM personnel WHERE id = :id LIMIT 1');
            $stmt->execute(array('id' => $id));
            $editItem = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }

    if ($action === 'delete') {
        $id = input_int('id', 0);
        if ($id > 0) {
            $stmtFind = $db->prepare('SELECT profile_image FROM personnel WHERE id = :id LIMIT 1');
            $stmtFind->execute(array('id' => $id));
            $found = $stmtFind->fetch(PDO::FETCH_ASSOC);

            $stmtDelete = $db->prepare('DELETE FROM personnel WHERE id = :id LIMIT 1');
            $stmtDelete->execute(array('id' => $id));

            if ($found && !empty($found['profile_image'])) {
                remove_staff_image($found['profile_image']);
            }

            flash('success', 'ลบข้อมูลเจ้าหน้าที่เรียบร้อยแล้ว');
        }

        redirect('/nurse_srnh/admin/staff.php');
    }
}

if ($editItem === null && isset($_GET['edit']) && (int)$_GET['edit'] > 0) {
    $editId = (int)$_GET['edit'];
    $stmt = $db->prepare('SELECT * FROM personnel WHERE id = :id LIMIT 1');
    $stmt->execute(array('id' => $editId));
    $editItem = $stmt->fetch(PDO::FETCH_ASSOC);
}

$keyword = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$statusFilter = isset($_GET['status']) ? trim((string)$_GET['status']) : 'all';

$sql = 'SELECT p.*, pl.level_name, pl.rank_no, wg.group_name
        FROM personnel p
        LEFT JOIN position_levels pl ON pl.id = p.position_level_id
        LEFT JOIN workgroups wg ON wg.id = p.workgroup_id
        WHERE 1=1';
$params = array();

if ($keyword !== '') {
    $sql .= ' AND (p.full_name LIKE :q OR p.position_name LIKE :q OR p.department_name LIKE :q OR pl.level_name LIKE :q OR wg.group_name LIKE :q)';
    $params['q'] = '%' . $keyword . '%';
}

if ($statusFilter === 'active') {
    $sql .= ' AND p.status = 1';
} elseif ($statusFilter === 'inactive') {
    $sql .= ' AND p.status = 0';
}

$sql .= ' ORDER BY p.id DESC';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$positionOptions = array();
try {
    $stmtPos = $db->query("SELECT position_name FROM positions WHERE is_active = 1 ORDER BY sort_order ASC, position_name ASC");
    $positionOptions = $stmtPos->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $positionOptions = array();
}
$positionOptions = array_values(array_unique(array_filter(array_map('trim', $positionOptions), function ($v) {
    return $v !== '';
})));

$departmentOptions = array();
try {
    $stmtDept = $db->query("SELECT department_name FROM departments WHERE is_active = 1 ORDER BY sort_order ASC, department_name ASC");
    $departmentOptions = $stmtDept->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $departmentOptions = array();
}
if (count($departmentOptions) === 0) {
    try {
        $stmtSubDept = $db->query("SELECT subdepartment_name FROM subdepartments WHERE is_active = 1 ORDER BY sort_order ASC, subdepartment_name ASC");
        $departmentOptions = $stmtSubDept->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        $departmentOptions = array();
    }
}
$departmentOptions = array_values(array_unique(array_filter(array_map('trim', $departmentOptions), function ($v) {
    return $v !== '';
})));

$levelOptions = array();
try {
    $stmtLevel = $db->query("SELECT id, level_name, rank_no FROM position_levels WHERE is_active = 1 ORDER BY rank_no ASC, sort_order ASC");
    $levelOptions = $stmtLevel->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $levelOptions = array();
}

$workgroupOptions = array();
try {
    $stmtWg = $db->query("SELECT id, group_name FROM workgroups WHERE is_active = 1 ORDER BY sort_order ASC, group_name ASC");
    $workgroupOptions = $stmtWg->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $workgroupOptions = array();
}

$title = 'จัดการเจ้าหน้าที่';
$success = flash('success');

include __DIR__ . '/_header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-end mb-3 gap-2">
    <div>
        <h1 class="page-title h3 mb-1">จัดการเจ้าหน้าที่</h1>
        <p class="text-soft mb-0">สร้างข้อมูลเจ้าหน้าที่: ชื่อ-สกุล, ตำแหน่ง, ระดับตำแหน่ง, แผนก, กลุ่มงาน</p>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success glass-card border-0"><?= h($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger glass-card border-0"><?= h($error) ?></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-xl-5">
        <div class="glass-card p-4">
            <h2 class="h5 mb-3"><?= $editItem ? 'แก้ไขเจ้าหน้าที่ #' . (int)$editItem['id'] : 'เพิ่มเจ้าหน้าที่ใหม่' ?></h2>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= h(isset($editItem['id']) ? $editItem['id'] : '') ?>">
                <input type="hidden" name="existing_profile_image" value="<?= h(isset($editItem['profile_image']) ? $editItem['profile_image'] : '') ?>">

                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">ชื่อ-นามสกุล <span class="text-danger">*</span></label>
                        <input class="form-control" name="full_name" maxlength="255" required value="<?= h(isset($editItem['full_name']) ? $editItem['full_name'] : '') ?>">
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label mb-0">ตำแหน่ง</label>
                            <a href="/nurse_srnh/admin/positions.php" class="small text-decoration-none">จัดการตำแหน่ง</a>
                        </div>
                        <?php $selectedPosition = trim((string)(isset($editItem['position_name']) ? $editItem['position_name'] : '')); ?>
                        <select class="form-select" name="position_name">
                            <option value="">-- ไม่ระบุ --</option>
                            <?php foreach ($positionOptions as $posName): ?>
                                <?php $posName = (string)$posName; ?>
                                <option value="<?= h($posName) ?>" <?= $selectedPosition === $posName ? 'selected' : '' ?>><?= h($posName) ?></option>
                            <?php endforeach; ?>
                            <?php if ($selectedPosition !== '' && !in_array($selectedPosition, $positionOptions, true)): ?>
                                <option value="<?= h($selectedPosition) ?>" selected><?= h($selectedPosition) ?></option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label mb-0">ระดับตำแหน่ง</label>
                            <a href="/nurse_srnh/admin/position_levels.php" class="small text-decoration-none">จัดการระดับ</a>
                        </div>
                        <select class="form-select" name="position_level_id">
                            <option value="0">-- ไม่ระบุ --</option>
                            <?php foreach ($levelOptions as $lv): ?>
                                <option value="<?= (int)$lv['id'] ?>" <?= (isset($editItem['position_level_id']) && (int)$editItem['position_level_id'] === (int)$lv['id']) ? 'selected' : '' ?>>
                                    <?= h((string)$lv['level_name']) ?> (<?= (int)$lv['rank_no'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">แผนก</label>
                        <?php $selectedDepartment = trim((string)(isset($editItem['department_name']) ? $editItem['department_name'] : '')); ?>
                        <select class="form-select" name="department_name">
                            <option value="">-- ไม่ระบุ --</option>
                            <?php foreach ($departmentOptions as $deptName): ?>
                                <?php $deptName = (string)$deptName; ?>
                                <option value="<?= h($deptName) ?>" <?= $selectedDepartment === $deptName ? 'selected' : '' ?>><?= h($deptName) ?></option>
                            <?php endforeach; ?>
                            <?php if ($selectedDepartment !== '' && !in_array($selectedDepartment, $departmentOptions, true)): ?>
                                <option value="<?= h($selectedDepartment) ?>" selected><?= h($selectedDepartment) ?></option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label mb-0">กลุ่มงาน</label>
                            <a href="/nurse_srnh/admin/workgroups.php" class="small text-decoration-none">จัดการกลุ่มงาน</a>
                        </div>
                        <select class="form-select" name="workgroup_id">
                            <option value="0">-- ไม่ระบุ --</option>
                            <?php foreach ($workgroupOptions as $wg): ?>
                                <option value="<?= (int)$wg['id'] ?>" <?= (isset($editItem['workgroup_id']) && (int)$editItem['workgroup_id'] === (int)$wg['id']) ? 'selected' : '' ?>><?= h((string)$wg['group_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">สถานะ</label>
                        <select class="form-select" name="status">
                            <option value="1" <?= (!isset($editItem['status']) || (int)$editItem['status'] === 1) ? 'selected' : '' ?>>Active</option>
                            <option value="0" <?= (isset($editItem['status']) && (int)$editItem['status'] === 0) ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">โทรศัพท์</label>
                        <input class="form-control" name="phone" maxlength="50" value="<?= h(isset($editItem['phone']) ? $editItem['phone'] : '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">เบอร์ภายใน</label>
                        <input class="form-control" name="internal_phone" maxlength="50" value="<?= h(isset($editItem['internal_phone']) ? $editItem['internal_phone'] : '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">หมายเหตุ</label>
                        <textarea class="form-control" name="note" rows="3" maxlength="4000"><?= h(isset($editItem['note']) ? $editItem['note'] : '') ?></textarea>
                    </div>
                    <div class="col-12">
                        <div class="p-3 rounded" style="background:#f7fbfb; border:1px dashed #bdd9d6;">
                            <label class="form-label mb-2">รูปภาพ (jpg, jpeg, png, gif, webp, max 5MB)</label>
                            <input class="form-control" type="file" name="profile_image_file" accept=".jpg,.jpeg,.png,.gif,.webp">
                            <?php if (isset($editItem['profile_image']) && trim($editItem['profile_image']) !== ''): ?>
                                <div class="mt-2">
                                    <img src="/nurse_srnh/<?= h($editItem['profile_image']) ?>" alt="preview" style="max-width:120px; border-radius:10px; border:1px solid #dce7e6;">
                                </div>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="remove_profile_image" id="remove_profile_image">
                                    <label class="form-check-label" for="remove_profile_image">ลบรูปปัจจุบัน</label>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-3">
                    <button class="btn btn-brand" type="submit">บันทึกข้อมูล</button>
                    <a href="/nurse_srnh/admin/staff.php" class="btn btn-outline-secondary">ล้างฟอร์ม</a>
                </div>
            </form>
        </div>
    </div>

    <div class="col-xl-7">
        <div class="glass-card p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h2 class="h5 mb-0">รายการเจ้าหน้าที่</h2>
                <form method="get" class="d-flex gap-2">
                    <input class="form-control form-control-sm" name="q" placeholder="ค้นหาชื่อ/ตำแหน่ง/ระดับ/แผนก/กลุ่มงาน" value="<?= h($keyword) ?>">
                    <select class="form-select form-select-sm" name="status">
                        <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>ทั้งหมด</option>
                        <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                    <button class="btn btn-sm btn-outline-primary" type="submit">กรอง</button>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                    <tr>
                        <th style="width:70px;">ID</th>
                        <th style="width:90px;">รูป</th>
                        <th>ข้อมูล</th>
                        <th style="width:130px;">สถานะ</th>
                        <th style="width:160px;"></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td>#<?= (int)$item['id'] ?></td>
                            <td>
                                <?php if (trim((string)$item['profile_image']) !== ''): ?>
                                    <img src="/nurse_srnh/<?= h($item['profile_image']) ?>" alt="img" style="width:64px; height:64px; object-fit:cover; border-radius:10px; border:1px solid #d7e4e3;">
                                <?php else: ?>
                                    <span class="text-muted small">ไม่มีรูป</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-semibold"><?= h($item['full_name']) ?></div>
                                <div class="small text-soft"><?= h((string)$item['position_name']) ?></div>
                                <div class="small text-soft">ระดับ: <?= h((string)$item['level_name']) ?><?= isset($item['rank_no']) && $item['rank_no'] !== null ? ' (' . (int)$item['rank_no'] . ')' : '' ?></div>
                                <div class="small text-soft">แผนก: <?= h((string)$item['department_name']) ?> | กลุ่มงาน: <?= h((string)$item['group_name']) ?></div>
                            </td>
                            <td>
                                <?php if ((int)$item['status'] === 1): ?>
                                    <span class="badge text-bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge text-bg-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="/nurse_srnh/admin/staff.php?edit=<?= (int)$item['id'] ?>">แก้ไข</a>
                                <form method="post" class="d-inline" onsubmit="return confirm('ยืนยันการลบเจ้าหน้าที่นี้?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit">ลบ</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (count($items) === 0): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">ยังไม่มีข้อมูลเจ้าหน้าที่</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/_footer.php'; ?>



