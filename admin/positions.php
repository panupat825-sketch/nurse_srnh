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

function ensure_positions_table($db)
{
    $sql = "CREATE TABLE IF NOT EXISTS positions (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        position_code VARCHAR(50) NULL,
        position_name VARCHAR(191) NOT NULL,
        department_name VARCHAR(191) NULL,
        sort_order INT NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        note TEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_position_code (position_code),
        UNIQUE KEY uq_position_name (position_name),
        INDEX idx_active (is_active),
        INDEX idx_sort_order (sort_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $db->exec($sql);
}

function seed_positions_from_personnel($db)
{
    $count = (int)$db->query("SELECT COUNT(*) FROM positions")->fetchColumn();
    if ($count > 0) {
        return;
    }

    $stmt = $db->query("SELECT DISTINCT TRIM(position_name) AS position_name, TRIM(department_name) AS department_name
                        FROM personnel
                        WHERE position_name IS NOT NULL AND TRIM(position_name) <> ''
                        ORDER BY position_name ASC");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) {
        return;
    }

    $ins = $db->prepare("INSERT INTO positions (position_name, department_name, sort_order, is_active, created_at, updated_at)
                         VALUES (:position_name, :department_name, :sort_order, 1, NOW(), NOW())");
    $sort = 1;
    foreach ($rows as $row) {
        $ins->execute(array(
            'position_name' => (string)$row['position_name'],
            'department_name' => (string)$row['department_name'],
            'sort_order' => $sort++,
        ));
    }
}

$error = null;
$editItem = null;

try {
    ensure_positions_table($db);
    seed_positions_from_personnel($db);
} catch (Exception $e) {
    $error = 'ไม่สามารถเตรียมตารางตำแหน่งได้';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === null) {
    $action = isset($_POST['action']) ? trim((string)$_POST['action']) : '';

    if ($action === 'save') {
        $id = input_int('id', 0);
        $positionCode = input_str('position_code', 50);
        $positionName = input_str('position_name', 191);
        $departmentName = input_str('department_name', 191);
        $sortOrder = input_int('sort_order', 0);
        $isActive = input_int('is_active', 1) === 1 ? 1 : 0;
        $note = input_str('note', 4000);

        if ($sortOrder < 0) {
            $sortOrder = 0;
        }

        if ($positionName === '') {
            $error = 'กรุณากรอกชื่อตำแหน่ง';
        } else {
            try {
                if ($id > 0) {
                    $stmt = $db->prepare("UPDATE positions
                                          SET position_code = :position_code,
                                              position_name = :position_name,
                                              department_name = :department_name,
                                              sort_order = :sort_order,
                                              is_active = :is_active,
                                              note = :note,
                                              updated_at = NOW()
                                          WHERE id = :id
                                          LIMIT 1");
                    $stmt->execute(array(
                        'position_code' => $positionCode !== '' ? $positionCode : null,
                        'position_name' => $positionName,
                        'department_name' => $departmentName !== '' ? $departmentName : null,
                        'sort_order' => $sortOrder,
                        'is_active' => $isActive,
                        'note' => $note !== '' ? $note : null,
                        'id' => $id,
                    ));
                    flash('success', 'แก้ไขตำแหน่งเรียบร้อยแล้ว');
                } else {
                    $stmt = $db->prepare("INSERT INTO positions
                                          (position_code, position_name, department_name, sort_order, is_active, note, created_at, updated_at)
                                          VALUES
                                          (:position_code, :position_name, :department_name, :sort_order, :is_active, :note, NOW(), NOW())");
                    $stmt->execute(array(
                        'position_code' => $positionCode !== '' ? $positionCode : null,
                        'position_name' => $positionName,
                        'department_name' => $departmentName !== '' ? $departmentName : null,
                        'sort_order' => $sortOrder,
                        'is_active' => $isActive,
                        'note' => $note !== '' ? $note : null,
                    ));
                    flash('success', 'เพิ่มตำแหน่งเรียบร้อยแล้ว');
                }
                redirect('/nurse_srnh/admin/positions.php');
            } catch (PDOException $e) {
                if ((int)$e->getCode() === 23000) {
                    $error = 'รหัสตำแหน่งหรือชื่อตำแหน่งซ้ำกับข้อมูลเดิม';
                } else {
                    $error = 'บันทึกข้อมูลไม่สำเร็จ';
                }
            }
        }

        if ($id > 0) {
            $stmt = $db->prepare("SELECT * FROM positions WHERE id = :id LIMIT 1");
            $stmt->execute(array('id' => $id));
            $editItem = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }

    if ($action === 'delete') {
        $id = input_int('id', 0);
        if ($id > 0) {
            $stmt = $db->prepare("DELETE FROM positions WHERE id = :id LIMIT 1");
            $stmt->execute(array('id' => $id));
            flash('success', 'ลบตำแหน่งเรียบร้อยแล้ว');
        }
        redirect('/nurse_srnh/admin/positions.php');
    }
}

if ($editItem === null && isset($_GET['edit']) && (int)$_GET['edit'] > 0) {
    $editId = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM positions WHERE id = :id LIMIT 1");
    $stmt->execute(array('id' => $editId));
    $editItem = $stmt->fetch(PDO::FETCH_ASSOC);
}

$keyword = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$statusFilter = isset($_GET['status']) ? trim((string)$_GET['status']) : 'all';

$sql = "SELECT * FROM positions WHERE 1=1";
$params = array();
if ($keyword !== '') {
    $sql .= " AND (position_name LIKE :q OR department_name LIKE :q OR position_code LIKE :q)";
    $params['q'] = '%' . $keyword . '%';
}
if ($statusFilter === 'active') {
    $sql .= " AND is_active = 1";
} elseif ($statusFilter === 'inactive') {
    $sql .= " AND is_active = 0";
}
$sql .= " ORDER BY sort_order ASC, id DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$title = 'จัดการตำแหน่ง';
$success = flash('success');
include __DIR__ . '/_header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-end mb-3 gap-2">
    <div>
        <h1 class="page-title h3 mb-1">จัดการตำแหน่ง</h1>
        <p class="text-soft mb-0">เพิ่ม/แก้ไขตำแหน่ง เพื่อใช้ในหน้าจัดการเจ้าหน้าที่</p>
    </div>
    <a href="/nurse_srnh/admin/staff.php" class="chip">กลับไปหน้าจัดการเจ้าหน้าที่</a>
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
            <h2 class="h5 mb-3"><?= $editItem ? 'แก้ไขตำแหน่ง #' . (int)$editItem['id'] : 'เพิ่มตำแหน่งใหม่' ?></h2>
            <form method="post">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= h(isset($editItem['id']) ? $editItem['id'] : '') ?>">

                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label">รหัสตำแหน่ง</label>
                        <input class="form-control" name="position_code" maxlength="50" value="<?= h(isset($editItem['position_code']) ? $editItem['position_code'] : '') ?>">
                    </div>
                    <div class="col-md-7">
                        <label class="form-label">ชื่อตำแหน่ง <span class="text-danger">*</span></label>
                        <input class="form-control" name="position_name" maxlength="191" required value="<?= h(isset($editItem['position_name']) ? $editItem['position_name'] : '') ?>">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">แผนก (ถ้ามี)</label>
                        <input class="form-control" name="department_name" maxlength="191" value="<?= h(isset($editItem['department_name']) ? $editItem['department_name'] : '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Sort</label>
                        <input class="form-control" type="number" name="sort_order" value="<?= h((string)(isset($editItem['sort_order']) ? $editItem['sort_order'] : 0)) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">สถานะ</label>
                        <select class="form-select" name="is_active">
                            <option value="1" <?= (!isset($editItem['is_active']) || (int)$editItem['is_active'] === 1) ? 'selected' : '' ?>>Active</option>
                            <option value="0" <?= (isset($editItem['is_active']) && (int)$editItem['is_active'] === 0) ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">หมายเหตุ</label>
                        <textarea class="form-control" name="note" rows="3" maxlength="4000"><?= h(isset($editItem['note']) ? $editItem['note'] : '') ?></textarea>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-3">
                    <button class="btn btn-brand" type="submit">บันทึกข้อมูล</button>
                    <a href="/nurse_srnh/admin/positions.php" class="btn btn-outline-secondary">ล้างฟอร์ม</a>
                </div>
            </form>
        </div>
    </div>

    <div class="col-xl-7">
        <div class="glass-card p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h2 class="h5 mb-0">รายการตำแหน่ง</h2>
                <form method="get" class="d-flex gap-2">
                    <input class="form-control form-control-sm" name="q" placeholder="ค้นหาตำแหน่ง/แผนก/รหัส" value="<?= h($keyword) ?>">
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
                        <th>ตำแหน่ง</th>
                        <th style="width:90px;">Sort</th>
                        <th style="width:120px;">สถานะ</th>
                        <th style="width:160px;"></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td>#<?= (int)$item['id'] ?></td>
                            <td>
                                <div class="fw-semibold"><?= h($item['position_name']) ?></div>
                                <div class="small text-soft">
                                    <?= h((string)$item['department_name']) ?>
                                    <?php if (trim((string)$item['position_code']) !== ''): ?>
                                        <span class="ms-2">[<?= h($item['position_code']) ?>]</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><?= (int)$item['sort_order'] ?></td>
                            <td>
                                <?php if ((int)$item['is_active'] === 1): ?>
                                    <span class="badge text-bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge text-bg-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="/nurse_srnh/admin/positions.php?edit=<?= (int)$item['id'] ?>">แก้ไข</a>
                                <form method="post" class="d-inline" onsubmit="return confirm('ยืนยันการลบตำแหน่งนี้?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit">ลบ</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (count($items) === 0): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">ยังไม่มีข้อมูลตำแหน่ง</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/_footer.php'; ?>
