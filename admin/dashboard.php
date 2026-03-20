<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../lib/content_repository.php';

require_admin_login();

$totalContents = (int)$db->query('SELECT COUNT(*) FROM content_items')->fetchColumn();
$totalSections = (int)$db->query('SELECT COUNT(DISTINCT section) FROM content_items')->fetchColumn();
$totalActive = (int)$db->query('SELECT COUNT(*) FROM content_items WHERE is_active = 1')->fetchColumn();

$title = 'Dashboard';
include __DIR__ . '/_header.php';
?>
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card shadow-sm"><div class="card-body"><h2 class="h6 text-muted">รายการข้อมูลทั้งหมด</h2><p class="display-6 mb-0"><?= $totalContents ?></p></div></div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm"><div class="card-body"><h2 class="h6 text-muted">มิติ/หมวดข้อมูล</h2><p class="display-6 mb-0"><?= $totalSections ?></p></div></div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm"><div class="card-body"><h2 class="h6 text-muted">รายการที่เปิดใช้งาน</h2><p class="display-6 mb-0"><?= $totalActive ?></p></div></div>
    </div>
</div>
<div class="card shadow-sm">
    <div class="card-body">
        <h1 class="h5">เริ่มจัดการข้อมูล</h1>
        <p class="mb-3">ระบบนี้รองรับ เพิ่ม/ลบ/แก้ไข, เปิด-ปิดการแสดงผล, จัดเรียงลำดับ และแบ่งข้อมูลตามหมวด (section) ได้จากหน้าเดียว</p>
        <a href="/nurse_srnh/admin/content.php" class="btn btn-primary">ไปหน้า Content CRUD</a>
    </div>
</div>
<?php include __DIR__ . '/_footer.php'; ?>
