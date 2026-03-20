<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../lib/content_repository.php';

require_admin_login();

$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$sectionFilter = trim((string)($_GET['section'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save'])) {
        $editId = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : null;
        save_content($db, $_POST, $editId);
        flash('success', $editId ? 'แก้ไขข้อมูลเรียบร้อยแล้ว' : 'เพิ่มข้อมูลเรียบร้อยแล้ว');
        redirect('/nurse_srnh/admin/content.php');
    }

    if (isset($_POST['delete']) && isset($_POST['id'])) {
        delete_content($db, (int)$_POST['id']);
        flash('success', 'ลบข้อมูลเรียบร้อยแล้ว');
        redirect('/nurse_srnh/admin/content.php');
    }
}

$editItem = null;
if ($action === 'edit' && $id) {
    $editItem = get_content($db, $id);
}

$items = get_contents($db, $sectionFilter !== '' ? $sectionFilter : null);
$sections = get_sections($db);
$success = flash('success');

$title = 'Content CRUD';
include __DIR__ . '/_header.php';
?>
<?php if ($success): ?>
    <div class="alert alert-success"><?= h($success) ?></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h1 class="h5 mb-3"><?= $editItem ? 'แก้ไขข้อมูล' : 'เพิ่มข้อมูลใหม่' ?></h1>
                <form method="post">
                    <input type="hidden" name="id" value="<?= h($editItem['id'] ?? '') ?>">
                    <div class="mb-2">
                        <label class="form-label">Section (มิติข้อมูล)</label>
                        <input class="form-control" name="section" value="<?= h($editItem['section'] ?? '') ?>" placeholder="example: menu, activity, service" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Title</label>
                        <input class="form-control" name="title" value="<?= h($editItem['title'] ?? '') ?>" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Subtitle</label>
                        <input class="form-control" name="subtitle" value="<?= h($editItem['subtitle'] ?? '') ?>">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Body</label>
                        <textarea class="form-control" name="body" rows="3"><?= h($editItem['body'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">URL</label>
                        <input class="form-control" name="url" value="<?= h($editItem['url'] ?? '') ?>">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Image Path</label>
                        <input class="form-control" name="image_path" value="<?= h($editItem['image_path'] ?? '') ?>" placeholder="img/... หรือ activity/...">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Sort Order</label>
                        <input class="form-control" type="number" name="sort_order" value="<?= h((string)($editItem['sort_order'] ?? 0)) ?>">
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" <?= !isset($editItem['is_active']) || (int)$editItem['is_active'] === 1 ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active">แสดงผล (Active)</label>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" type="submit" name="save">บันทึก</button>
                        <a href="/nurse_srnh/admin/content.php" class="btn btn-outline-secondary">ล้างฟอร์ม</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 mb-0">รายการข้อมูล</h2>
                    <form method="get" class="d-flex gap-2">
                        <input class="form-control form-control-sm" name="section" placeholder="กรองตาม section" value="<?= h($sectionFilter) ?>">
                        <button class="btn btn-sm btn-outline-primary" type="submit">กรอง</button>
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Section</th>
                            <th>Title</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?= (int)$item['id'] ?></td>
                                <td><code><?= h($item['section']) ?></code></td>
                                <td><?= h($item['title']) ?></td>
                                <td>
                                    <?php if ((int)$item['is_active'] === 1): ?>
                                        <span class="badge text-bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge text-bg-secondary">Hidden</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary" href="/nurse_srnh/admin/content.php?action=edit&id=<?= (int)$item['id'] ?>">แก้ไข</a>
                                    <form method="post" class="d-inline" onsubmit="return confirm('ยืนยันการลบข้อมูลนี้?');">
                                        <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit" name="delete">ลบ</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (count($items) === 0): ?>
                            <tr><td colspan="5" class="text-center text-muted">ยังไม่มีข้อมูล</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($sections): ?>
                    <p class="small text-muted mb-0">Sections ที่มีอยู่: <?= h(implode(', ', $sections)) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/_footer.php'; ?>
