<?php

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../lib/content_repository.php';

require_admin_login();

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$sectionFilter = trim(isset($_GET['section']) ? (string)$_GET['section'] : '');
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save'])) {
        $editId = (isset($_POST['id']) && $_POST['id'] !== '') ? (int)$_POST['id'] : null;
        $formData = $_POST;

        if (isset($_POST['remove_image'])) {
            $formData['image_path'] = '';
        }

        if (isset($_FILES['image_file']) && isset($_FILES['image_file']['error']) && (int)$_FILES['image_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ((int)$_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
                $tmpPath = $_FILES['image_file']['tmp_name'];
                $originalName = isset($_FILES['image_file']['name']) ? $_FILES['image_file']['name'] : '';
                $fileSize = isset($_FILES['image_file']['size']) ? (int)$_FILES['image_file']['size'] : 0;

                $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                $allowed = array('jpg', 'jpeg', 'png', 'gif', 'webp');

                if (!in_array($ext, $allowed, true)) {
                    $error = 'ไฟล์รูปต้องเป็นนามสกุล jpg, jpeg, png, gif หรือ webp เท่านั้น';
                } elseif ($fileSize > (5 * 1024 * 1024)) {
                    $error = 'ขนาดไฟล์ต้องไม่เกิน 5MB';
                } elseif (!is_uploaded_file($tmpPath)) {
                    $error = 'ไม่พบไฟล์อัปโหลดที่ถูกต้อง';
                } else {
                    $monthDir = date('Ym');
                    $relativeDir = 'uploads/content/' . $monthDir;
                    $absoluteDir = __DIR__ . '/../' . $relativeDir;

                    if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0755, true)) {
                        $error = 'ไม่สามารถสร้างโฟลเดอร์อัปโหลดได้';
                    } else {
                        $filename = date('Ymd_His') . '_' . substr(md5(uniqid('', true)), 0, 10) . '.' . $ext;
                        $targetPath = $absoluteDir . '/' . $filename;

                        if (!move_uploaded_file($tmpPath, $targetPath)) {
                            $error = 'ไม่สามารถบันทึกไฟล์รูปได้';
                        } else {
                            $formData['image_path'] = $relativeDir . '/' . $filename;
                        }
                    }
                }
            } else {
                $error = 'เกิดข้อผิดพลาดระหว่างอัปโหลดไฟล์';
            }
        }

        if ($error === null) {
            save_content($db, $formData, $editId);
            flash('success', $editId ? 'แก้ไขข้อมูลเรียบร้อยแล้ว' : 'เพิ่มข้อมูลเรียบร้อยแล้ว');
            redirect('/nurse_srnh/admin/content.php');
        }
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

<div class="d-flex flex-wrap justify-content-between align-items-end mb-3 gap-2">
    <div>
        <h1 class="page-title h3 mb-1">Content Manager</h1>
        <p class="text-soft mb-0">เพิ่ม ลบ แก้ไข ข้อมูลทุกหมวด พร้อมอัปโหลดรูปได้ทันที</p>
    </div>
    <a href="/nurse_srnh/managed-content.php?section=activity" target="_blank" class="btn btn-outline-secondary">ดูตัวอย่างหน้าแสดงผล</a>
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
            <h2 class="h5 mb-3"><?= $editItem ? 'แก้ไขรายการ #' . (int)$editItem['id'] : 'เพิ่มข้อมูลใหม่' ?></h2>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= h(isset($editItem['id']) ? $editItem['id'] : '') ?>">

                <div class="mb-3">
                    <label class="form-label">Section (หมวด/มิติ)</label>
                    <input class="form-control" name="section" value="<?= h(isset($editItem['section']) ? $editItem['section'] : '') ?>" placeholder="เช่น activity, menu, service" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Title</label>
                    <input class="form-control" name="title" value="<?= h(isset($editItem['title']) ? $editItem['title'] : '') ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Subtitle</label>
                    <input class="form-control" name="subtitle" value="<?= h(isset($editItem['subtitle']) ? $editItem['subtitle'] : '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="body" rows="3" placeholder="รายละเอียดเพิ่มเติม"><?= h(isset($editItem['body']) ? $editItem['body'] : '') ?></textarea>
                </div>

                <div class="row g-2">
                    <div class="col-md-8">
                        <label class="form-label">URL (ถ้ามี)</label>
                        <input class="form-control" name="url" value="<?= h(isset($editItem['url']) ? $editItem['url'] : '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Sort</label>
                        <input class="form-control" type="number" name="sort_order" value="<?= h((string)(isset($editItem['sort_order']) ? $editItem['sort_order'] : 0)) ?>">
                    </div>
                </div>

                <div class="mt-3 p-3 rounded" style="background:#f7fbfb; border:1px dashed #bdd9d6;">
                    <label class="form-label mb-2">รูปภาพ</label>
                    <input class="form-control mb-2" type="file" name="image_file" accept=".jpg,.jpeg,.png,.gif,.webp">
                    <input class="form-control" name="image_path" value="<?= h(isset($editItem['image_path']) ? $editItem['image_path'] : '') ?>" placeholder="หรือใส่ path เอง เช่น activity/activity1.jpg">
                    <?php if (isset($editItem['image_path']) && trim($editItem['image_path']) !== ''): ?>
                        <div class="mt-2">
                            <img src="/nurse_srnh/<?= h($editItem['image_path']) ?>" alt="preview" style="max-width:150px; border-radius:10px; border:1px solid #dce7e6;">
                        </div>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="remove_image" id="remove_image">
                            <label class="form-check-label" for="remove_image">ลบรูปปัจจุบัน</label>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-check my-3">
                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" <?= (!isset($editItem['is_active']) || (int)$editItem['is_active'] === 1) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_active">แสดงผล (Active)</label>
                </div>

                <div class="d-flex gap-2">
                    <button class="btn btn-brand" type="submit" name="save">บันทึกข้อมูล</button>
                    <a href="/nurse_srnh/admin/content.php" class="btn btn-outline-secondary">เคลียร์ฟอร์ม</a>
                </div>
            </form>
        </div>
    </div>

    <div class="col-xl-7">
        <div class="glass-card p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h2 class="h5 mb-0">รายการข้อมูลทั้งหมด</h2>
                <form method="get" class="d-flex gap-2">
                    <input class="form-control form-control-sm" name="section" placeholder="กรองตาม section" value="<?= h($sectionFilter) ?>">
                    <button class="btn btn-sm btn-outline-primary" type="submit">กรอง</button>
                </form>
            </div>

            <?php if ($sections): ?>
                <div class="mb-3">
                    <a class="chip" href="/nurse_srnh/admin/content.php">ทั้งหมด</a>
                    <?php foreach ($sections as $s): ?>
                        <a class="chip" href="/nurse_srnh/admin/content.php?section=<?= urlencode($s) ?>"><?= h($s) ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                    <tr>
                        <th style="width:70px;">ID</th>
                        <th style="width:120px;">รูป</th>
                        <th>เนื้อหา</th>
                        <th style="width:140px;">สถานะ</th>
                        <th style="width:140px;"></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td>#<?= (int)$item['id'] ?></td>
                            <td>
                                <?php if (trim($item['image_path']) !== ''): ?>
                                    <img src="/nurse_srnh/<?= h($item['image_path']) ?>" alt="img" style="width:84px; height:56px; object-fit:cover; border-radius:8px; border:1px solid #d7e4e3;">
                                <?php else: ?>
                                    <span class="text-muted small">ไม่มีรูป</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-semibold"><?= h($item['title']) ?></div>
                                <div class="small text-soft"><code><?= h($item['section']) ?></code> • sort <?= (int)$item['sort_order'] ?></div>
                            </td>
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
                        <tr><td colspan="5" class="text-center text-muted py-4">ยังไม่มีข้อมูลในหมวดนี้</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/_footer.php'; ?>
