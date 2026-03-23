<?php
require_once __DIR__ . '/bootstrap.php';

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function normalize_asset_url($path)
{
    $path = trim((string)$path);
    if ($path === '') {
        return '';
    }

    if (preg_match('/^https?:\/\//i', $path)) {
        return $path;
    }

    return '/nurse_srnh/' . ltrim($path, '/');
}

function normalize_member_row_sort($sortOrder)
{
    $sort = (int)$sortOrder;
    if ($sort < 1) {
        return 4;
    }
    if ($sort > 4) {
        return 4;
    }
    return $sort;
}

function ensure_org_container_tables($db)
{
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS org_containers (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            container_name VARCHAR(191) NOT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_sort_order (sort_order),
            INDEX idx_is_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Exception $e) {
    }

    try {
        $db->exec("CREATE TABLE IF NOT EXISTS org_container_members (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            container_id INT UNSIGNED NOT NULL,
            personnel_id INT UNSIGNED NOT NULL,
            level_no INT NOT NULL DEFAULT 99,
            sort_order INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_container_personnel (container_id, personnel_id),
            INDEX idx_container_level_sort (container_id, level_no, sort_order),
            CONSTRAINT fk_ocm_container FOREIGN KEY (container_id)
                REFERENCES org_containers(id)
                ON DELETE CASCADE
                ON UPDATE CASCADE,
            CONSTRAINT fk_ocm_personnel FOREIGN KEY (personnel_id)
                REFERENCES personnel(id)
                ON DELETE CASCADE
                ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Exception $e) {
    }
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

ensure_personnel_extra_columns($db);
ensure_org_container_tables($db);

$isAdminEditor = is_admin_logged_in();
$orgError = null;

if ($isAdminEditor && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? trim((string)$_POST['action']) : '';

    if ($action === 'create_container') {
        $containerName = trim(isset($_POST['container_name']) ? (string)$_POST['container_name'] : '');
        if ($containerName === '') {
            $containerName = 'New Container';
        }

        try {
            $maxSort = (int)$db->query("SELECT COALESCE(MAX(sort_order), 0) FROM org_containers")->fetchColumn();
            $stmt = $db->prepare("INSERT INTO org_containers (container_name, sort_order, is_active, created_at, updated_at)
                                  VALUES (:container_name, :sort_order, 1, NOW(), NOW())");
            $stmt->execute(array(
                'container_name' => $containerName,
                'sort_order' => $maxSort + 1,
            ));
            flash('success', 'Completed successfully');
            redirect('/nurse_srnh/org_ch.php');
        } catch (Exception $e) {
            $orgError = 'Unable to create container. Please try again.';
        }
    }

    if ($action === 'update_container') {
        $containerId = isset($_POST['container_id']) ? (int)$_POST['container_id'] : 0;
        $containerName = trim(isset($_POST['container_name']) ? (string)$_POST['container_name'] : '');

        if ($containerId <= 0 || $containerName === '') {
            $orgError = 'Unable to reorder container. Please try again.';
        } else {
            try {
                $stmt = $db->prepare("UPDATE org_containers SET container_name = :container_name, updated_at = NOW() WHERE id = :id LIMIT 1");
                $stmt->execute(array(
                    'container_name' => $containerName,
                    'id' => $containerId,
                ));
            flash('success', 'Completed successfully');
                redirect('/nurse_srnh/org_ch.php');
            } catch (Exception $e) {
                $orgError = 'Unable to rename container. Please try again.';
            }
        }
    }

    if ($action === 'delete_container') {
        $containerId = isset($_POST['container_id']) ? (int)$_POST['container_id'] : 0;
        if ($containerId > 0) {
            try {
                $stmt = $db->prepare("DELETE FROM org_containers WHERE id = :id LIMIT 1");
                $stmt->execute(array('id' => $containerId));
            flash('success', 'Completed successfully');
                redirect('/nurse_srnh/org_ch.php');
            } catch (Exception $e) {
                $orgError = 'Unable to delete container. Please try again.';
            }
        }
    }

    if ($action === 'move_container_up' || $action === 'move_container_down') {
        $containerId = isset($_POST['container_id']) ? (int)$_POST['container_id'] : 0;
        if ($containerId > 0) {
            try {
                $db->beginTransaction();

                $stmt = $db->prepare("SELECT id, sort_order FROM org_containers WHERE id = :id LIMIT 1");
                $stmt->execute(array('id' => $containerId));
                $current = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($current) {
                    $currentSort = (int)$current['sort_order'];
                    if ($action === 'move_container_up') {
                        $stmtNeighbor = $db->prepare("SELECT id, sort_order
                                                      FROM org_containers
                                                      WHERE sort_order < :sort_order
                                                      ORDER BY sort_order DESC, id DESC
                                                      LIMIT 1");
                    } else {
                        $stmtNeighbor = $db->prepare("SELECT id, sort_order
                                                      FROM org_containers
                                                      WHERE sort_order > :sort_order
                                                      ORDER BY sort_order ASC, id ASC
                                                      LIMIT 1");
                    }
                    $stmtNeighbor->execute(array('sort_order' => $currentSort));
                    $neighbor = $stmtNeighbor->fetch(PDO::FETCH_ASSOC);

                    if ($neighbor) {
                        $neighborId = (int)$neighbor['id'];
                        $neighborSort = (int)$neighbor['sort_order'];

                        $stmtA = $db->prepare("UPDATE org_containers SET sort_order = :sort_order WHERE id = :id LIMIT 1");
                        $stmtA->execute(array('sort_order' => $neighborSort, 'id' => $containerId));
                        $stmtA->execute(array('sort_order' => $currentSort, 'id' => $neighborId));
                    }
                }

                $db->commit();
            flash('success', 'Completed successfully');
                redirect('/nurse_srnh/org_ch.php');
            } catch (Exception $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                $orgError = 'Unable to add staff to container. Please try again.';
            }
        }
    }

    if ($action === 'add_member') {
        $containerId = isset($_POST['container_id']) ? (int)$_POST['container_id'] : 0;
        $personnelId = isset($_POST['personnel_id']) ? (int)$_POST['personnel_id'] : 0;
        $rowSort = isset($_POST['row_sort']) ? (int)$_POST['row_sort'] : 1;
        $sortOrder = isset($_POST['sort_order']) ? (int)$_POST['sort_order'] : 0;

        if ($containerId <= 0 || $personnelId <= 0) {
            $orgError = 'Please select a staff member and a target container.';
        } else {
            try {
                $stmt = $db->prepare("SELECT p.id, p.status
                                      FROM personnel p
                                      WHERE p.id = :id LIMIT 1");
                $stmt->execute(array('id' => $personnelId));
                $person = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$person) {
                    $orgError = 'Unable to remove staff from container. Please try again.';
                } else {
                    $levelNo = normalize_member_row_sort($rowSort);
                    if ($sortOrder <= 0) {
                        $stmtMaxSort = $db->prepare("SELECT COALESCE(MAX(sort_order), 0)
                                                     FROM org_container_members
                                                     WHERE container_id = :container_id AND level_no = :level_no");
                        $stmtMaxSort->execute(array(
                            'container_id' => $containerId,
                            'level_no' => $levelNo,
                        ));
                        $sortOrder = ((int)$stmtMaxSort->fetchColumn()) + 1;
                    }

                    $stmt = $db->prepare("INSERT INTO org_container_members (container_id, personnel_id, level_no, sort_order, created_at)
                                          VALUES (:container_id, :personnel_id, :level_no, :sort_order, NOW())
                                          ON DUPLICATE KEY UPDATE
                                              level_no = VALUES(level_no),
                                              sort_order = VALUES(sort_order)");
                    $stmt->execute(array(
                        'container_id' => $containerId,
                        'personnel_id' => $personnelId,
                        'level_no' => $levelNo,
                        'sort_order' => $sortOrder,
                    ));
            flash('success', 'Completed successfully');
                    redirect('/nurse_srnh/org_ch.php');
                }
            } catch (Exception $e) {
                $orgError = 'Unable to move staff card. Please try again.';
            }
        }
    }

    if ($action === 'remove_member') {
        $memberId = isset($_POST['member_id']) ? (int)$_POST['member_id'] : 0;
        if ($memberId > 0) {
            try {
                $stmt = $db->prepare("DELETE FROM org_container_members WHERE id = :id LIMIT 1");
                $stmt->execute(array('id' => $memberId));
            flash('success', 'Completed successfully');
                redirect('/nurse_srnh/org_ch.php');
            } catch (Exception $e) {
                $orgError = 'Unable to update card order. Please try again.';
            }
        }
    }
}

$success = flash('success');

$availablePersonnel = array();
try {
    $stmt = $db->query("SELECT p.id, p.full_name, p.position_name, p.department_name, p.profile_image, p.sort_order,
                               pl.level_name, pl.rank_no, wg.group_name
                        FROM personnel p
                        LEFT JOIN position_levels pl ON pl.id = p.position_level_id
                        LEFT JOIN workgroups wg ON wg.id = p.workgroup_id
                        WHERE p.status = 1
                        ORDER BY p.sort_order ASC, p.id ASC");
    $availablePersonnel = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $availablePersonnel = array();
}

$containers = array();
try {
    $stmt = $db->query("SELECT * FROM org_containers WHERE is_active = 1 ORDER BY sort_order ASC, id ASC");
    $containers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $containers = array();
}

$membersByContainer = array();
if (count($containers) > 0) {
    $ids = array();
    foreach ($containers as $c) {
        $ids[] = (int)$c['id'];
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    try {
        $sql = "SELECT m.id AS member_id, m.container_id, m.level_no, m.sort_order,
                       p.id AS personnel_id, p.full_name, p.position_name, p.department_name, p.profile_image,
                       p.unit_name, p.phone, p.internal_phone,
                       pl.level_name, pl.rank_no, wg.group_name
                FROM org_container_members m
                INNER JOIN personnel p ON p.id = m.personnel_id
                LEFT JOIN position_levels pl ON pl.id = p.position_level_id
                LEFT JOIN workgroups wg ON wg.id = p.workgroup_id
                WHERE m.container_id IN ($placeholders)
                ORDER BY m.container_id ASC, m.level_no ASC, m.sort_order ASC, p.sort_order ASC, p.id ASC";
        $stmt = $db->prepare($sql);
        foreach ($ids as $k => $v) {
            $stmt->bindValue($k + 1, $v, PDO::PARAM_INT);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $cid = (int)$row['container_id'];
            $lv = normalize_member_row_sort((int)$row['level_no']);
            if (!isset($membersByContainer[$cid])) {
                $membersByContainer[$cid] = array();
            }
            if (!isset($membersByContainer[$cid][$lv])) {
                $membersByContainer[$cid][$lv] = array();
            }
            $membersByContainer[$cid][$lv][] = $row;
        }
    } catch (Exception $e) {
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organization Containers | Nurse SRNH</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; background: #eef5f9; color: #173646; }
        .navbar-custom { background: linear-gradient(120deg,#0b6b7f,#158aa3); }
        .navbar-custom .nav-link, .navbar-custom .navbar-brand { color:#fff !important; }
        .shell { max-width: 1320px; }
        .panel { background:#fff; border:1px solid #dce9f0; border-radius:18px; box-shadow:0 10px 25px rgba(11,55,75,.08); }
        .person-card { position:relative; border:1px solid #d4e6ef; border-radius:14px; background:#fafdff; padding:.75rem; }
        .person-inner { display:flex; gap:.7rem; }
        .person-avatar { width:58px; height:58px; border-radius:12px; border:1px solid #d5e4ec; overflow:hidden; background:#edf5f9; flex-shrink:0; }
        .person-avatar img { width:100%; height:100%; object-fit:cover; display:block; }
        .person-avatar-empty { width:100%; height:100%; display:flex; align-items:center; justify-content:center; color:#0b6b7f; font-weight:700; }
        .person-name { font-weight:700; line-height:1.2; }
        .person-meta { color:#5b7380; font-size:.9rem; }
        .container-block { border:1px solid #cfe2ed; border-radius:16px; background:#f8fcff; padding:1rem; }
        .level-row { border-top:1px dashed #cfe0ea; padding-top:.8rem; margin-top:.8rem; }
        .level-title { font-weight:700; color:#0e5d75; margin-bottom:.5rem; }
        .person-grid { display:flex; flex-wrap:wrap; justify-content:center; gap:.75rem; }
        .person-grid .person-card { width:100%; max-width:520px; }
        .person-row-empty { text-align:center; color:#6b7f8b; padding:.8rem .5rem; border:1px dashed #d5e5ee; border-radius:12px; background:#ffffff; }
        .btn-add-container { font-weight:700; border-radius:999px; padding:.45rem 1rem; }
        .btn-import-member { font-weight:600; }
        .person-remove-btn {
            position:absolute;
            top:8px;
            right:8px;
            width:28px;
            height:28px;
            border-radius:50%;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            font-size:1rem;
            line-height:1;
            padding:0;
        }
        .person-body { padding-right:34px; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-custom mb-4">
    <div class="container shell">
        <a class="navbar-brand fw-bold" href="/nurse_srnh/index.php">Nurse SRNH</a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="nav">
            <div class="navbar-nav ms-auto">
                <a class="nav-link active" href="/nurse_srnh/org_ch.php">Organization</a>
                <a class="nav-link" href="/nurse_srnh/admin/login.php">Admin</a>
            </div>
        </div>
    </div>
</nav>

<div class="container shell pb-5">
    <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
    <?php if ($orgError): ?><div class="alert alert-danger"><?= e($orgError) ?></div><?php endif; ?>

    <section class="panel p-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h2 class="h5 mb-0">Department Containers (Manual Sort)</h2>
            <?php if ($isAdminEditor): ?>
                <button class="btn btn-primary btn-add-container" data-bs-toggle="modal" data-bs-target="#createContainerModal">+ Add Container</button>
            <?php endif; ?>
        </div>

        <?php if (count($containers) === 0): ?>
            <div class="alert alert-info mt-3 mb-0">No containers yet. Click + Add Container to create a new one.</div>
        <?php else: ?>
            <div class="row g-3 mt-1">
                <?php foreach ($containers as $container): ?>
                    <?php $cid = (int)$container['id']; ?>
                    <div class="col-12">
                        <div class="container-block">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <h3 class="h6 mb-0"><?= e((string)$container['container_name']) ?></h3>
                                <?php if ($isAdminEditor): ?>
                                    <div class="d-flex flex-wrap gap-2">
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="action" value="move_container_up">
                                            <input type="hidden" name="container_id" value="<?= $cid ?>">
                                            <button class="btn btn-sm btn-outline-dark" type="submit" title="Move up">Up</button>
                                        </form>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="action" value="move_container_down">
                                            <input type="hidden" name="container_id" value="<?= $cid ?>">
                                            <button class="btn btn-sm btn-outline-dark" type="submit" title="Move down">Down</button>
                                        </form>
                                        <button class="btn btn-sm btn-outline-primary btn-import-member js-open-add-member" data-container-id="<?= $cid ?>" data-container-name="<?= e((string)$container['container_name']) ?>">Import Staff to Department</button>
                                        <button class="btn btn-sm btn-outline-secondary js-open-edit-container" data-container-id="<?= $cid ?>" data-container-name="<?= e((string)$container['container_name']) ?>">Rename</button>
                                        <form method="post" class="d-inline" onsubmit="return confirm('Delete this container?');">
                                            <input type="hidden" name="action" value="delete_container">
                                            <input type="hidden" name="container_id" value="<?= $cid ?>">
                                            <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php for ($rowSort = 1; $rowSort <= 4; $rowSort++): ?>
                                <?php $memberRows = isset($membersByContainer[$cid][$rowSort]) ? $membersByContainer[$cid][$rowSort] : array(); ?>
                                <div class="level-row">
                                    <div class="level-title">Sort <?= (int)$rowSort ?></div>
                                    <div class="person-grid">
                                        <?php if (count($memberRows) === 0): ?>
                                            <div class="person-row-empty">No staff in Sort <?= (int)$rowSort ?></div>
                                        <?php else: ?>
                                            <?php foreach ($memberRows as $m): ?>
                                                <article class="person-card">
                                                    <?php if ($isAdminEditor): ?>
                                                        <form method="post" class="d-inline" onsubmit="return confirm('Remove this staff from container?');">
                                                            <input type="hidden" name="action" value="remove_member">
                                                            <input type="hidden" name="member_id" value="<?= (int)$m['member_id'] ?>">
                                                            <button class="btn btn-sm btn-outline-danger person-remove-btn" type="submit" title="Remove">&times;</button>
                                                        </form>
                                                    <?php endif; ?>
                                                    <div class="person-inner person-body">
                                                        <div class="person-avatar">
                                                            <?php if (trim((string)$m['profile_image']) !== ''): ?>
                                                                <img src="<?= e(normalize_asset_url($m['profile_image'])) ?>" alt="<?= e((string)$m['full_name']) ?>">
                                                            <?php else: ?>
                                                                <div class="person-avatar-empty">?</div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="flex-grow-1">
                                                            <div class="person-name"><?= e((string)$m['full_name']) ?></div>
                                                            <div class="person-meta"><?= e((string)$m['position_name']) ?></div>
                                                            <div class="person-meta">Sort: <?= (int)$rowSort ?></div>
                                                            <div class="person-meta">Department: <?= e((string)$m['department_name']) ?> | Workgroup: <?= e((string)$m['group_name']) ?></div>
                                                        </div>
                                                    </div>
                                                </article>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php if ($isAdminEditor): ?>
<div class="modal fade" id="createContainerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Add Container</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_container">
                    <label class="form-label">Container Name</label>
                    <input class="form-control" name="container_name" maxlength="191" placeholder="e.g. ER Container">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="addMemberModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Import Staff to Container</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_member">
                    <input type="hidden" name="container_id" id="memberContainerId" value="0">

                    <div class="small text-muted mb-2">Container: <span id="memberContainerName">-</span></div>

                    <div class="mb-2">
                        <label class="form-label">Select Staff</label>
                        <select class="form-select" name="personnel_id" id="memberPersonnelSelect" required>
                            <option value="">-- Select staff --</option>
                            <?php foreach ($availablePersonnel as $p): ?>
                                <option value="<?= (int)$p['id'] ?>">
                                    <?= e((string)$p['full_name']) ?> | <?= e((string)$p['position_name']) ?> | <?= e((string)$p['department_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Row Sort (1-4)</label>
                        <input class="form-control" type="number" min="1" max="4" name="row_sort" value="1" required>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Order in row (0 = auto append)</label>
                        <input class="form-control" type="number" name="sort_order" value="0">
                    </div>

                    <div class="alert alert-light border small mb-0">Manual sort mode: no main/secondary container logic.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editContainerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Rename Container</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_container">
                    <input type="hidden" name="container_id" id="editContainerId" value="0">
                    <label class="form-label">Container Name</label>
                    <input class="form-control" name="container_name" id="editContainerName" maxlength="191" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    var addMemberModalEl = document.getElementById('addMemberModal');
    var editContainerModalEl = document.getElementById('editContainerModal');
    if (!addMemberModalEl && !editContainerModalEl) return;

    var modal = addMemberModalEl ? bootstrap.Modal.getOrCreateInstance(addMemberModalEl) : null;
    var editModal = editContainerModalEl ? bootstrap.Modal.getOrCreateInstance(editContainerModalEl) : null;
    var inputId = document.getElementById('memberContainerId');
    var textName = document.getElementById('memberContainerName');
    var editContainerId = document.getElementById('editContainerId');
    var editContainerName = document.getElementById('editContainerName');

    document.querySelectorAll('.js-open-add-member').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var cid = btn.getAttribute('data-container-id') || '0';
            var cname = btn.getAttribute('data-container-name') || '-';
            if (inputId) inputId.value = cid;
            if (textName) textName.textContent = cname;
            if (modal) modal.show();
        });
    });

    document.querySelectorAll('.js-open-edit-container').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var cid = btn.getAttribute('data-container-id') || '0';
            var cname = btn.getAttribute('data-container-name') || '';
            if (editContainerId) editContainerId.value = cid;
            if (editContainerName) editContainerName.value = cname;
            if (editModal) editModal.show();
        });
    });
})();
</script>
</body>
</html>
