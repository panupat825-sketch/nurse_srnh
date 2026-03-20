<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../lib/content_repository.php';

require_admin_login();

$keys = [
    'site_title',
    'site_subtitle',
    'contact_address',
    'contact_phone',
    'contact_email',
    'facebook_url',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($keys as $key) {
        upsert_setting($db, $key, trim((string)($_POST[$key] ?? '')));
    }
    flash('success', 'บันทึก Settings เรียบร้อยแล้ว');
    redirect('/nurse_srnh/admin/settings.php');
}

$settings = get_settings($db);
$success = flash('success');

$title = 'Settings';
include __DIR__ . '/_header.php';
?>
<?php if ($success): ?>
    <div class="alert alert-success"><?= h($success) ?></div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <h1 class="h5 mb-3">ตั้งค่าระบบ</h1>
        <form method="post">
            <?php foreach ($keys as $key): ?>
                <div class="mb-3">
                    <label class="form-label"><?= h($key) ?></label>
                    <input class="form-control" name="<?= h($key) ?>" value="<?= h($settings[$key] ?? '') ?>">
                </div>
            <?php endforeach; ?>
            <button class="btn btn-primary" type="submit">บันทึก</button>
        </form>
    </div>
</div>
<?php include __DIR__ . '/_footer.php'; ?>
