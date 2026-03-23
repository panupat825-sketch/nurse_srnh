<?php
require_once __DIR__ . '/../bootstrap.php';
require_admin_login();

function in_str($key, $max = null) {
    $v = isset($_POST[$key]) ? trim((string)$_POST[$key]) : '';
    if ($max !== null && function_exists('mb_substr')) {
        $v = mb_substr($v, 0, (int)$max, 'UTF-8');
    }
    return $v;
}
function in_int($key, $default = 0) {
    if (!isset($_POST[$key]) || $_POST[$key] === '') return (int)$default;
    return (int)$_POST[$key];
}
function ensure_position_levels_table($db) {
    $db->exec("CREATE TABLE IF NOT EXISTS position_levels (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        level_code VARCHAR(50) NULL,
        level_name VARCHAR(191) NOT NULL,
        rank_no INT NOT NULL DEFAULT 0,
        sort_order INT NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        note TEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_level_code (level_code),
        UNIQUE KEY uq_level_name (level_name),
        INDEX idx_rank_no (rank_no),
        INDEX idx_sort_order (sort_order),
        INDEX idx_is_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

$error = null;
$editItem = null;
try { ensure_position_levels_table($db); } catch (Exception $e) { $error = 'ไม่สามารถเตรียมตารางระดับตำแหน่งได้'; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === null) {
    $action = isset($_POST['action']) ? trim((string)$_POST['action']) : '';
    if ($action === 'save') {
        $id = in_int('id', 0);
        $levelCode = in_str('level_code', 50);
        $levelName = in_str('level_name', 191);
        $rankNo = in_int('rank_no', 0);
        $sortOrder = in_int('sort_order', 0);
        $isActive = in_int('is_active', 1) === 1 ? 1 : 0;
        $note = in_str('note', 4000);

        if ($levelName === '') {
            $error = 'กรุณากรอกชื่อระดับตำแหน่ง';
        } else {
            try {
                if ($id > 0) {
                    $stmt = $db->prepare("UPDATE position_levels SET
                        level_code = :level_code,
                        level_name = :level_name,
                        rank_no = :rank_no,
                        sort_order = :sort_order,
                        is_active = :is_active,
                        note = :note,
                        updated_at = NOW()
                        WHERE id = :id LIMIT 1");
                    $stmt->execute(array(
                        'level_code' => $levelCode !== '' ? $levelCode : null,
                        'level_name' => $levelName,
                        'rank_no' => $rankNo,
                        'sort_order' => $sortOrder,
                        'is_active' => $isActive,
                        'note' => $note !== '' ? $note : null,
                        'id' => $id,
                    ));
                    flash('success', 'แก้ไขระดับตำแหน่งเรียบร้อยแล้ว');
                } else {
                    $stmt = $db->prepare("INSERT INTO position_levels
                        (level_code, level_name, rank_no, sort_order, is_active, note, created_at, updated_at)
                        VALUES (:level_code, :level_name, :rank_no, :sort_order, :is_active, :note, NOW(), NOW())");
                    $stmt->execute(array(
                        'level_code' => $levelCode !== '' ? $levelCode : null,
                        'level_name' => $levelName,
                        'rank_no' => $rankNo,
                        'sort_order' => $sortOrder,
                        'is_active' => $isActive,
                        'note' => $note !== '' ? $note : null,
                    ));
                    flash('success', 'เพิ่มระดับตำแหน่งเรียบร้อยแล้ว');
                }
                redirect('/nurse_srnh/admin/position_levels.php');
            } catch (PDOException $e) {
                $error = ((int)$e->getCode() === 23000) ? 'รหัสหรือชื่อระดับตำแหน่งซ้ำ' : 'บันทึกข้อมูลไม่สำเร็จ';
            }
        }
    }

    if ($action === 'delete') {
        $id = in_int('id', 0);
        if ($id > 0) {
            $stmt = $db->prepare('DELETE FROM position_levels WHERE id = :id LIMIT 1');
            $stmt->execute(array('id' => $id));
            flash('success', 'ลบระดับตำแหน่งเรียบร้อยแล้ว');
        }
        redirect('/nurse_srnh/admin/position_levels.php');
    }
}

if (isset($_GET['edit']) && (int)$_GET['edit'] > 0) {
    $stmt = $db->prepare('SELECT * FROM position_levels WHERE id = :id LIMIT 1');
    $stmt->execute(array('id' => (int)$_GET['edit']));
    $editItem = $stmt->fetch(PDO::FETCH_ASSOC);
}

$q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$status = isset($_GET['status']) ? trim((string)$_GET['status']) : 'all';
$sql = 'SELECT * FROM position_levels WHERE 1=1';
$params = array();
if ($q !== '') {
    $sql .= ' AND (level_name LIKE :q OR level_code LIKE :q OR note LIKE :q)';
    $params['q'] = '%' . $q . '%';
}
if ($status === 'active') $sql .= ' AND is_active = 1';
if ($status === 'inactive') $sql .= ' AND is_active = 0';
$sql .= ' ORDER BY rank_no ASC, sort_order ASC, id DESC';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$title = 'จัดการระดับตำแหน่ง';
$success = flash('success');
include __DIR__ . '/_header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-end mb-3 gap-2">
    <div>
        <h1 class="page-title h3 mb-1">จัดการระดับตำแหน่ง</h1>
        <p class="text-soft mb-0">กำหนดระดับสายบังคับบัญชา เช่น ระดับบริหาร, หัวหน้า, ปฏิบัติการ</p>
    </div>
</div>
<?php if ($success): ?><div class="alert alert-success glass-card border-0"><?= h($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger glass-card border-0"><?= h($error) ?></div><?php endif; ?>

<div class="row g-4">
    <div class="col-xl-5">
        <div class="glass-card p-4">
            <h2 class="h5 mb-3"><?= $editItem ? 'แก้ไขระดับตำแหน่ง #' . (int)$editItem['id'] : 'เพิ่มระดับตำแหน่งใหม่' ?></h2>
            <form method="post">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= h(isset($editItem['id']) ? $editItem['id'] : '') ?>">
                <div class="row g-3">
                    <div class="col-md-5"><label class="form-label">รหัส</label><input class="form-control" name="level_code" maxlength="50" value="<?= h(isset($editItem['level_code']) ? $editItem['level_code'] : '') ?>"></div>
                    <div class="col-md-7"><label class="form-label">ชื่อระดับ <span class="text-danger">*</span></label><input class="form-control" name="level_name" maxlength="191" required value="<?= h(isset($editItem['level_name']) ? $editItem['level_name'] : '') ?>"></div>
                    <div class="col-md-6"><label class="form-label">Rank</label><input class="form-control" type="number" name="rank_no" value="<?= h((string)(isset($editItem['rank_no']) ? $editItem['rank_no'] : 0)) ?>"></div>
                    <div class="col-md-6"><label class="form-label">Sort</label><input class="form-control" type="number" name="sort_order" value="<?= h((string)(isset($editItem['sort_order']) ? $editItem['sort_order'] : 0)) ?>"></div>
                    <div class="col-md-6"><label class="form-label">สถานะ</label><select class="form-select" name="is_active"><option value="1" <?= (!isset($editItem['is_active']) || (int)$editItem['is_active'] === 1) ? 'selected' : '' ?>>Active</option><option value="0" <?= (isset($editItem['is_active']) && (int)$editItem['is_active'] === 0) ? 'selected' : '' ?>>Inactive</option></select></div>
                    <div class="col-12"><label class="form-label">หมายเหตุ</label><textarea class="form-control" name="note" rows="3"><?= h(isset($editItem['note']) ? $editItem['note'] : '') ?></textarea></div>
                </div>
                <div class="d-flex gap-2 mt-3"><button class="btn btn-brand" type="submit">บันทึก</button><a class="btn btn-outline-secondary" href="/nurse_srnh/admin/position_levels.php">ล้างฟอร์ม</a></div>
            </form>
        </div>
    </div>
    <div class="col-xl-7">
        <div class="glass-card p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h2 class="h5 mb-0">รายการระดับตำแหน่ง</h2>
                <form method="get" class="d-flex gap-2">
                    <input class="form-control form-control-sm" name="q" placeholder="ค้นหา" value="<?= h($q) ?>">
                    <select class="form-select form-select-sm" name="status"><option value="all" <?= $status === 'all' ? 'selected' : '' ?>>ทั้งหมด</option><option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option><option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option></select>
                    <button class="btn btn-sm btn-outline-primary" type="submit">กรอง</button>
                </form>
            </div>
            <div class="table-responsive"><table class="table align-middle"><thead><tr><th style="width:70px;">ID</th><th>ระดับ</th><th style="width:80px;">Rank</th><th style="width:80px;">Sort</th><th style="width:110px;">สถานะ</th><th style="width:140px;"></th></tr></thead><tbody>
            <?php foreach ($items as $item): ?>
                <tr><td>#<?= (int)$item['id'] ?></td><td><div class="fw-semibold"><?= h($item['level_name']) ?></div><div class="small text-soft"><?= h((string)$item['level_code']) ?></div></td><td><?= (int)$item['rank_no'] ?></td><td><?= (int)$item['sort_order'] ?></td><td><?= (int)$item['is_active'] === 1 ? '<span class="badge text-bg-success">Active</span>' : '<span class="badge text-bg-secondary">Inactive</span>' ?></td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="/nurse_srnh/admin/position_levels.php?edit=<?= (int)$item['id'] ?>">แก้ไข</a><form method="post" class="d-inline" onsubmit="return confirm('ยืนยันการลบ?');"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$item['id'] ?>"><button class="btn btn-sm btn-outline-danger" type="submit">ลบ</button></form></td></tr>
            <?php endforeach; ?>
            <?php if (count($items) === 0): ?><tr><td colspan="6" class="text-center text-muted py-4">ยังไม่มีข้อมูลระดับตำแหน่ง</td></tr><?php endif; ?>
            </tbody></table></div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/_footer.php'; ?>
