<?php

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../lib/content_repository.php';

require_admin_login();

$totalContents = (int)$db->query('SELECT COUNT(*) FROM content_items')->fetchColumn();
$totalSections = (int)$db->query('SELECT COUNT(DISTINCT section) FROM content_items')->fetchColumn();
$totalActive = (int)$db->query('SELECT COUNT(*) FROM content_items WHERE is_active = 1')->fetchColumn();
$recent = $db->query('SELECT id, section, title, is_active, updated_at FROM content_items ORDER BY updated_at DESC LIMIT 8')->fetchAll();

$title = 'Dashboard';
include __DIR__ . '/_header.php';
?>

<div class="mb-3">
    <h1 class="page-title h3 mb-1">Dashboard</h1>
    <p class="text-soft mb-0">ภาพรวมคอนเทนต์ทั้งหมดและทางลัดสำหรับจัดการข้อมูล</p>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="glass-card p-3 h-100">
            <div class="text-soft">รายการข้อมูลทั้งหมด</div>
            <div class="display-6 fw-bold"><?= $totalContents ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="glass-card p-3 h-100">
            <div class="text-soft">หมวดข้อมูล (Section)</div>
            <div class="display-6 fw-bold"><?= $totalSections ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="glass-card p-3 h-100">
            <div class="text-soft">รายการที่แสดงผล</div>
            <div class="display-6 fw-bold"><?= $totalActive ?></div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="glass-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 mb-0">รายการที่อัปเดตล่าสุด</h2>
                <a href="/nurse_srnh/admin/content.php" class="btn btn-sm btn-brand">จัดการทั้งหมด</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Section</th>
                        <th>Title</th>
                        <th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recent as $row): ?>
                        <tr>
                            <td>#<?= (int)$row['id'] ?></td>
                            <td><code><?= h($row['section']) ?></code></td>
                            <td><?= h($row['title']) ?></td>
                            <td><?= ((int)$row['is_active'] === 1) ? 'Active' : 'Hidden' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (count($recent) === 0): ?>
                        <tr><td colspan="4" class="text-center text-muted">ยังไม่มีรายการ</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="glass-card p-4 h-100">
            <h2 class="h5 mb-3">Quick Actions</h2>
            <div class="d-grid gap-2">
                <a href="/nurse_srnh/admin/content.php" class="btn btn-brand">เพิ่ม/แก้ไขเนื้อหา</a>
                <a href="/nurse_srnh/admin/personnel.php" class="btn btn-outline-primary">จัดการทำเนียบบุคลากร</a>
                <a href="/nurse_srnh/admin/settings.php" class="btn btn-outline-secondary">ตั้งค่าเว็บไซต์</a>
                <a href="/nurse_srnh/managed-content.php?section=activity" target="_blank" class="btn btn-outline-secondary">ดูหน้า activity</a>
                <a href="/nurse_srnh/index.php" target="_blank" class="btn btn-outline-secondary">ดูหน้าเว็บไซต์หลัก</a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/_footer.php'; ?>

