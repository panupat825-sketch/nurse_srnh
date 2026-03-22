<?php

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../lib/content_repository.php';

require_admin_login();

$keys = array(
    'site_title' => 'ชื่อเว็บไซต์',
    'site_subtitle' => 'คำอธิบายสั้น',
    'contact_address' => 'ที่อยู่',
    'contact_phone' => 'เบอร์โทร',
    'contact_email' => 'อีเมล',
    'facebook_url' => 'ลิงก์ Facebook',
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($keys as $key => $label) {
        $value = trim(isset($_POST[$key]) ? (string)$_POST[$key] : '');
        upsert_setting($db, $key, $value);
    }
    flash('success', 'บันทึก Settings เรียบร้อยแล้ว');
    redirect('/nurse_srnh/admin/settings.php');
}

$settings = get_settings($db);
$success = flash('success');

$title = 'Settings';
include __DIR__ . '/_header.php';
?>

<div class="mb-3">
    <h1 class="page-title h3 mb-1">Settings</h1>
    <p class="text-soft mb-0">ตั้งค่าข้อมูลภาพรวมเว็บไซต์จากจุดเดียว</p>
</div>

<?php if ($success): ?>
    <div class="alert alert-success glass-card border-0"><?= h($success) ?></div>
<?php endif; ?>

<div class="glass-card p-4">
    <form method="post">
        <div class="row g-3">
            <?php foreach ($keys as $key => $label): ?>
                <div class="col-md-6">
                    <label class="form-label"><?= h($label) ?> <span class="text-muted small">(<?= h($key) ?>)</span></label>
                    <input class="form-control" name="<?= h($key) ?>" value="<?= h(isset($settings[$key]) ? $settings[$key] : '') ?>">
                </div>
            <?php endforeach; ?>
        </div>
        <div class="mt-4 d-flex gap-2">
            <button class="btn btn-brand" type="submit">บันทึกการตั้งค่า</button>
            <a href="/nurse_srnh/admin/dashboard.php" class="btn btn-outline-secondary">กลับ Dashboard</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/_footer.php'; ?>
