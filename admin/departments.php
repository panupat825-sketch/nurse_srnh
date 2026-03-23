<?php

require_once __DIR__ . '/../bootstrap.php';

require_admin_login();

function ensure_departments_table($db)
{
    $sql = "CREATE TABLE IF NOT EXISTS departments (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        department_code VARCHAR(50) NULL,
        department_name VARCHAR(191) NOT NULL,
        department_desc TEXT NULL,
        sort_order INT NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_department_code (department_code),
        UNIQUE KEY uq_department_name (department_name),
        INDEX idx_active (is_active),
        INDEX idx_sort_order (sort_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    $db->exec($sql);
}

function normalize_department_code($code)
{
    $code = strtoupper(trim((string)$code));
    if ($code === '') {
        return null;
    }

    return $code;
}

ensure_departments_table($db);

$action = isset($_GET['action']) ? trim((string)$_GET['action']) : 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$search = trim(isset($_GET['search']) ? (string)$_GET['search'] : '');
$statusFilter = trim(isset($_GET['status']) ? (string)$_GET['status'] : 'all');
$error = null;
$success = flash('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save'])) {
        $editId = (isset($_POST['id']) && trim((string)$_POST['id']) !== '') ? (int)$_POST['id'] : 0;
        $departmentCode = normalize_department_code(isset($_POST['department_code']) ? $_POST['department_code'] : '');
        $departmentName = trim(isset($_POST['department_name']) ? (string)$_POST['department_name'] : '');
        $departmentDesc = trim(isset($_POST['department_desc']) ? (string)$_POST['department_desc'] : '');
        $sortOrder = isset($_POST['sort_order']) ? (int)$_POST['sort_order'] : 0;
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($departmentName === '') {
            $error = 'Please enter department name.';
        } elseif (strlen((string)$departmentCode) > 50 || strlen($departmentName) > 191) {
            $error = 'Input length is too long.';
        }

        if ($error === null) {
            try {
                if ($editId > 0) {
                    $stmt = $db->prepare('UPDATE departments SET
                        department_code = :department_code,
                        department_name = :department_name,
                        department_desc = :department_desc,
                        sort_order = :sort_order,
                        is_active = :is_active,
                        updated_at = NOW()
                        WHERE id = :id
                        LIMIT 1');

                    $stmt->execute(array(
                        'department_code' => $departmentCode,
                        'department_name' => $departmentName,
                        'department_desc' => $departmentDesc,
                        'sort_order' => $sortOrder,
                        'is_active' => $isActive,
                        'id' => $editId,
                    ));

                    flash('success', 'Department updated successfully.');
                } else {
                    $stmt = $db->prepare('INSERT INTO departments (
                        department_code, department_name, department_desc, sort_order, is_active, created_at, updated_at
                    ) VALUES (
                        :department_code, :department_name, :department_desc, :sort_order, :is_active, NOW(), NOW()
                    )');

                    $stmt->execute(array(
                        'department_code' => $departmentCode,
                        'department_name' => $departmentName,
                        'department_desc' => $departmentDesc,
                        'sort_order' => $sortOrder,
                        'is_active' => $isActive,
                    ));

                    flash('success', 'Department created successfully.');
                }

                redirect('/nurse_srnh/admin/departments.php');
            } catch (PDOException $e) {
                if ((int)$e->getCode() === 23000) {
                    $error = 'Department code or name already exists.';
                } else {
                    $error = 'Unable to save department.';
                }
            }
        }
    }

    if (isset($_POST['delete']) && isset($_POST['id'])) {
        $deleteId = (int)$_POST['id'];

        if ($deleteId > 0) {
            $stmt = $db->prepare('SELECT department_name FROM departments WHERE id = :id LIMIT 1');
            $stmt->execute(array('id' => $deleteId));
            $department = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($department) {
                $deptName = trim((string)$department['department_name']);

                if ($deptName !== '') {
                    $usageStmt = $db->prepare('SELECT COUNT(*) FROM personnel WHERE department_name = :department_name');
                    $usageStmt->execute(array('department_name' => $deptName));
                    $usageCount = (int)$usageStmt->fetchColumn();

                    if ($usageCount > 0) {
                        flash('error', 'Cannot delete this department. It is still used by personnel (' . $usageCount . ' records).');
                        redirect('/nurse_srnh/admin/departments.php');
                    }
                }

                $deleteStmt = $db->prepare('DELETE FROM departments WHERE id = :id LIMIT 1');
                $deleteStmt->execute(array('id' => $deleteId));
                flash('success', 'Department deleted successfully.');
                redirect('/nurse_srnh/admin/departments.php');
            }
        }
    }
}

$editItem = null;
if ($action === 'edit' && $id > 0) {
    $stmt = $db->prepare('SELECT * FROM departments WHERE id = :id LIMIT 1');
    $stmt->execute(array('id' => $id));
    $editItem = $stmt->fetch(PDO::FETCH_ASSOC);
}

$where = array('1=1');
$params = array();
if ($search !== '') {
    $where[] = '(department_name LIKE :search OR department_code LIKE :search OR department_desc LIKE :search)';
    $params['search'] = '%' . $search . '%';
}
if ($statusFilter === 'active') {
    $where[] = 'is_active = 1';
} elseif ($statusFilter === 'inactive') {
    $where[] = 'is_active = 0';
}

$listSql = 'SELECT
    d.*,
    (SELECT COUNT(*) FROM personnel p WHERE p.department_name = d.department_name) AS personnel_count
    FROM departments d
    WHERE ' . implode(' AND ', $where) . '
    ORDER BY d.sort_order ASC, d.department_name ASC, d.id ASC';

$listStmt = $db->prepare($listSql);
$listStmt->execute($params);
$items = $listStmt->fetchAll(PDO::FETCH_ASSOC);

$title = 'Department Management';
$flashError = flash('error');

include __DIR__ . '/_header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-end mb-3 gap-2">
    <div>
        <h1 class="page-title h3 mb-1">Department Management</h1>
        <p class="text-soft mb-0">Create and manage department master data for personnel records.</p>
    </div>
    <a href="/nurse_srnh/admin/personnel.php" class="btn btn-outline-primary">Back To Personnel</a>
</div>

<?php if ($success): ?>
    <div class="alert alert-success glass-card border-0"><?= h($success) ?></div>
<?php endif; ?>
<?php if ($flashError): ?>
    <div class="alert alert-danger glass-card border-0"><?= h($flashError) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger glass-card border-0"><?= h($error) ?></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-xl-5">
        <div class="glass-card p-4">
            <h2 class="h5 mb-3"><?= $editItem ? 'Edit Department #' . (int)$editItem['id'] : 'Create Department' ?></h2>
            <form method="post">
                <input type="hidden" name="id" value="<?= h(isset($editItem['id']) ? $editItem['id'] : '') ?>">

                <div class="mb-3">
                    <label class="form-label">Department Code</label>
                    <input class="form-control" name="department_code" maxlength="50" value="<?= h(isset($editItem['department_code']) ? $editItem['department_code'] : '') ?>" placeholder="e.g. ER, ICU, OPD">
                </div>

                <div class="mb-3">
                    <label class="form-label">Department Name <span class="text-danger">*</span></label>
                    <input class="form-control" name="department_name" maxlength="191" value="<?= h(isset($editItem['department_name']) ? $editItem['department_name'] : '') ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="department_desc" rows="3" placeholder="Optional description"><?= h(isset($editItem['department_desc']) ? $editItem['department_desc'] : '') ?></textarea>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Sort Order</label>
                        <input class="form-control" type="number" name="sort_order" value="<?= h((string)(isset($editItem['sort_order']) ? $editItem['sort_order'] : 0)) ?>">
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" <?= (!isset($editItem['is_active']) || (int)$editItem['is_active'] === 1) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button class="btn btn-brand" type="submit" name="save">Save Department</button>
                    <a href="/nurse_srnh/admin/departments.php" class="btn btn-outline-secondary">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <div class="col-xl-7">
        <div class="glass-card p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h2 class="h5 mb-0">Department List</h2>
                <form method="get" class="d-flex gap-2">
                    <input class="form-control form-control-sm" name="search" placeholder="Search" value="<?= h($search) ?>">
                    <select class="form-select form-select-sm" name="status">
                        <option value="all" <?= ($statusFilter === 'all') ? 'selected' : '' ?>>All</option>
                        <option value="active" <?= ($statusFilter === 'active') ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= ($statusFilter === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                    </select>
                    <button class="btn btn-sm btn-outline-primary" type="submit">Filter</button>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                    <tr>
                        <th style="width:70px;">ID</th>
                        <th style="width:110px;">Code</th>
                        <th>Name</th>
                        <th style="width:90px;">Sort</th>
                        <th style="width:120px;">Personnel</th>
                        <th style="width:110px;">Status</th>
                        <th style="width:140px;"></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td>#<?= (int)$item['id'] ?></td>
                            <td><code><?= h((string)$item['department_code']) ?></code></td>
                            <td>
                                <div class="fw-semibold"><?= h((string)$item['department_name']) ?></div>
                                <?php if (trim((string)$item['department_desc']) !== ''): ?>
                                    <div class="small text-soft"><?= h((string)$item['department_desc']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= (int)$item['sort_order'] ?></td>
                            <td><span class="badge text-bg-info"><?= (int)$item['personnel_count'] ?> persons</span></td>
                            <td>
                                <?php if ((int)$item['is_active'] === 1): ?>
                                    <span class="badge text-bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge text-bg-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="/nurse_srnh/admin/departments.php?action=edit&id=<?= (int)$item['id'] ?>">Edit</a>
                                <form method="post" class="d-inline" onsubmit="return confirm('Delete this department?');">
                                    <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit" name="delete">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (count($items) === 0): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No departments found.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/_footer.php'; ?>


