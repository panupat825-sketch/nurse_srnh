<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/lib/content_repository.php';

$section = trim(isset($_GET['section']) ? (string)$_GET['section'] : '');
if ($section === '') {
    $section = 'menu';
}

$stmt = $db->prepare('SELECT * FROM content_items WHERE section = :section AND is_active = 1 ORDER BY sort_order, id DESC');
$stmt->execute(array('section' => $section));
$items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Managed Content - <?= h($section) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Managed Content: <code><?= h($section) ?></code></h1>
        <a class="btn btn-primary btn-sm" href="/nurse_srnh/admin/content.php">ไปหน้า CRUD</a>
    </div>

    <div class="table-responsive bg-white border rounded">
        <table class="table table-striped mb-0">
            <thead>
            <tr>
                <th>Title</th>
                <th>Subtitle</th>
                <th>URL</th>
                <th>Image</th>
                <th>Body</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= h($item['title']) ?></td>
                    <td><?= h($item['subtitle']) ?></td>
                    <td><?php if (!empty($item['url'])): ?><a href="<?= h($item['url']) ?>" target="_blank">เปิดลิงก์</a><?php endif; ?></td>
                    <td><?= h($item['image_path']) ?></td>
                    <td><?= nl2br(h($item['body'])) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (count($items) === 0): ?>
                <tr><td colspan="5" class="text-center text-muted">ไม่มีข้อมูลใน section นี้</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
