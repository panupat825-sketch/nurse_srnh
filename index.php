<?php

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

function load_config_data()
{
    $config = require __DIR__ . '/config.php';
    $localConfigPath = __DIR__ . '/config.local.php';
    if (file_exists($localConfigPath)) {
        $local = require $localConfigPath;
        if (is_array($local)) {
            $config = array_replace_recursive($config, $local);
        }
    }

    return $config;
}

function get_section_items($pdo, $section, $limit = 0)
{
    if (!$pdo) {
        return array();
    }

    $sql = 'SELECT * FROM content_items WHERE section = :section AND is_active = 1 ORDER BY sort_order, id DESC';
    if ($limit > 0) {
        $sql .= ' LIMIT ' . (int)$limit;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute(array('section' => $section));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$settings = array();
$sections = array(
    'achievements' => array(),
    'directory' => array(),
    'links' => array(),
    'activity' => array(),
);

$pdo = null;

try {
    $config = load_config_data();
    $dbCfg = $config['db'];

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $dbCfg['host'],
        (int)$dbCfg['port'],
        $dbCfg['name'],
        $dbCfg['charset']
    );

    $pdo = new PDO($dsn, $dbCfg['user'], $dbCfg['pass'], array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ));

    $settingRows = $pdo->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
    foreach ($settingRows as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    $sections['achievements'] = get_section_items($pdo, 'achievements', 12);
    $sections['directory'] = get_section_items($pdo, 'directory', 24);
    $sections['links'] = get_section_items($pdo, 'links', 24);
    $sections['activity'] = get_section_items($pdo, 'activity', 12);
} catch (Exception $e) {
    $pdo = null;
}

$siteTitle = isset($settings['site_title']) && trim($settings['site_title']) !== '' ? $settings['site_title'] : 'Nurse SRNH';
$siteSubtitle = isset($settings['site_subtitle']) && trim($settings['site_subtitle']) !== '' ? $settings['site_subtitle'] : 'พื้นที่แสดงผลงาน ทำเนียบ และลิงก์สำคัญของกลุ่มการพยาบาล';
$contactAddress = isset($settings['contact_address']) ? $settings['contact_address'] : '182 M.15 Sikeaw Sirattana Sisaket';
$contactPhone = isset($settings['contact_phone']) ? $settings['contact_phone'] : '045677014';
$contactEmail = isset($settings['contact_email']) ? $settings['contact_email'] : 'admin@example.com';
$facebookUrl = isset($settings['facebook_url']) ? $settings['facebook_url'] : 'https://www.facebook.com/sirattanahosp/';

if (count($sections['achievements']) === 0) {
    $sections['achievements'] = array(
        array('title' => 'พัฒนาคุณภาพการพยาบาล', 'subtitle' => 'Quality Improvement', 'body' => 'สรุปผลการพัฒนาคุณภาพและตัวชี้วัดบริการประจำปี', 'url' => '', 'image_path' => 'img/blog-1.jpg'),
        array('title' => 'ผลงานวิชาการ', 'subtitle' => 'Academic Works', 'body' => 'รวบรวมบทความ งานประชุม และนวัตกรรมของทีมพยาบาล', 'url' => '', 'image_path' => 'img/blog-2.jpg'),
        array('title' => 'รางวัลและการยกย่อง', 'subtitle' => 'Awards', 'body' => 'ผลงานโดดเด่นที่ได้รับการยอมรับระดับหน่วยงาน/เขต', 'url' => '', 'image_path' => 'img/blog-3.jpg'),
    );
}

if (count($sections['directory']) === 0) {
    $sections['directory'] = array(
        array('title' => 'หัวหน้ากลุ่มการพยาบาล', 'subtitle' => 'Nurse Director', 'body' => 'ดูแลภาพรวมและการบริหารงานพยาบาล', 'url' => '', 'image_path' => 'img/team-1.jpg'),
        array('title' => 'หัวหน้าหอผู้ป่วย', 'subtitle' => 'Ward Supervisor', 'body' => 'ดูแลมาตรฐานบริการและการประสานงานทีม', 'url' => '', 'image_path' => 'img/team-2.jpg'),
        array('title' => 'ผู้ประสานงานวิชาการ', 'subtitle' => 'Academic Coordinator', 'body' => 'สนับสนุนการพัฒนาความรู้และวิจัย', 'url' => '', 'image_path' => 'img/team-3.jpg'),
        array('title' => 'ผู้ประสานงานคุณภาพ', 'subtitle' => 'Quality Coordinator', 'body' => 'กำกับ KPI และการประเมินคุณภาพ', 'url' => '', 'image_path' => 'img/team-4.jpg'),
    );
}

if (count($sections['links']) === 0) {
    $sections['links'] = array(
        array('title' => 'Dashboard ตัวชี้วัด', 'subtitle' => 'ข้อมูลบริการ', 'body' => 'แดชบอร์ดสถิติและผลการดำเนินงาน', 'url' => 'http://sirattanahosp.moph.go.th/dashboard/', 'image_path' => ''),
        array('title' => 'แบบฟอร์มรายงานการพยาบาล', 'subtitle' => 'Google Sheet', 'body' => 'อัปเดตข้อมูลรายงานประจำหน่วยงาน', 'url' => 'https://docs.google.com/', 'image_path' => ''),
        array('title' => 'THAILAND NURSING DIGITAL PLATFORM', 'subtitle' => 'บริการภายนอก', 'body' => 'ระบบสนับสนุนวิชาชีพการพยาบาล', 'url' => 'https://www.don.go.th/nperson/app/index.php/member/login', 'image_path' => ''),
    );
}

if (count($sections['activity']) === 0) {
    $sections['activity'] = array(
        array('title' => 'กิจกรรม 1', 'image_path' => 'activity/activity1.jpg'),
        array('title' => 'กิจกรรม 2', 'image_path' => 'activity/activity2.jpg'),
        array('title' => 'กิจกรรม 3', 'image_path' => 'activity/activity3.jpg'),
        array('title' => 'กิจกรรม 4', 'image_path' => 'activity/activity4.jpg'),
    );
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <title><?= e($siteTitle) ?></title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="Nurse Portfolio, Directory, Links" name="keywords">
    <meta content="เว็บไซต์กลุ่มการพยาบาลสำหรับแสดงผลงาน ทำเนียบ และลิงก์สำคัญ" name="description">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="skho_TBm_icon.icon" rel="shortcut icon" type="image/x-icon" />
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --brand: #0d5d56;
            --brand-2: #168a79;
            --accent: #ee8434;
            --paper: #f3f8f7;
            --ink: #18303a;
        }

        body {
            font-family: 'Sarabun', sans-serif;
            color: var(--ink);
            background:
                radial-gradient(1000px 420px at -15% -20%, #dcf4ef 0%, transparent 70%),
                radial-gradient(920px 360px at 110% -10%, #ffe7d8 0%, transparent 70%),
                var(--paper);
        }

        .navbar-custom {
            background: linear-gradient(120deg, var(--brand), var(--brand-2));
            box-shadow: 0 10px 30px rgba(13, 93, 86, .22);
        }

        .navbar-custom .nav-link,
        .navbar-custom .navbar-brand {
            color: #fff !important;
        }

        .hero {
            position: relative;
            overflow: hidden;
            border-radius: 24px;
            background: linear-gradient(120deg, rgba(13,93,86,.96), rgba(22,138,121,.9));
            color: #fff;
            box-shadow: 0 18px 40px rgba(12, 66, 70, .22);
        }

        .hero::after {
            content: '';
            position: absolute;
            right: -60px;
            top: -70px;
            width: 260px;
            height: 260px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .1);
        }

        .section-block {
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,.6);
            background: rgba(255,255,255,.9);
            box-shadow: 0 12px 28px rgba(13, 51, 61, .08);
        }

        .section-title {
            font-weight: 700;
            letter-spacing: .2px;
        }

        .portfolio-card,
        .team-card,
        .link-card,
        .activity-card {
            height: 100%;
            border: 1px solid #e0edeb;
            border-radius: 16px;
            background: #fff;
            transition: transform .25s ease, box-shadow .25s ease;
            overflow: hidden;
        }

        .portfolio-card:hover,
        .team-card:hover,
        .link-card:hover,
        .activity-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 14px 28px rgba(14, 60, 68, .12);
        }

        .portfolio-image,
        .team-image,
        .activity-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
            background: #f0f4f6;
        }

        .team-image {
            height: 220px;
        }

        .activity-image {
            height: 170px;
        }

        .muted {
            color: #5f7580;
        }

        .btn-brand {
            background: var(--brand);
            border-color: var(--brand);
            color: #fff;
        }

        .btn-brand:hover {
            background: #0a4d46;
            border-color: #0a4d46;
            color: #fff;
        }

        .btn-accent {
            background: var(--accent);
            border-color: var(--accent);
            color: #fff;
        }

        .btn-accent:hover {
            background: #d76f21;
            border-color: #d76f21;
            color: #fff;
        }

        .footer-box {
            border-radius: 18px;
            background: #0f2f3c;
            color: #dce8ec;
        }

        .footer-box a {
            color: #fff;
            text-decoration: none;
        }

        .badge-soft {
            background: #e5f3ef;
            color: #0d5d56;
            border-radius: 999px;
            padding: .28rem .7rem;
            font-size: .78rem;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">Nurse SRNH</a>
        <button class="navbar-toggler text-white border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMain">
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="#achievements">ผลงาน</a>
                <a class="nav-link" href="#directory">ทำเนียบ</a>
                <a class="nav-link" href="#links">ลิงก์สำคัญ</a>
                <a class="nav-link" href="#activity">กิจกรรม</a>
                <a class="nav-link" href="admin/login.php">หลังบ้าน</a>
            </div>
        </div>
    </div>
</nav>

<div class="container py-4 py-md-5">
    <section class="hero p-4 p-md-5 mb-4 mb-md-5">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <span class="badge-soft mb-3 d-inline-block">Nursing Showcase Portal</span>
                <h1 class="display-5 fw-bold mb-3"><?= e($siteTitle) ?></h1>
                <p class="lead mb-4"><?= e($siteSubtitle) ?></p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="#achievements" class="btn btn-light">ดูผลงาน</a>
                    <a href="#links" class="btn btn-accent">ลิงก์ใช้งานด่วน</a>
                    <a href="<?= e($facebookUrl) ?>" class="btn btn-outline-light" target="_blank">Facebook</a>
                </div>
            </div>
            <div class="col-lg-4 text-lg-end">
                <img src="<?= e(normalize_asset_url('img/hero.png')) ?>" class="img-fluid" style="max-height:220px;" alt="Nurse Hero">
            </div>
        </div>
    </section>

    <section id="achievements" class="section-block p-4 p-md-5 mb-4 mb-md-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="section-title h3 mb-0">ผลงานเด่นกลุ่มการพยาบาล</h2>
            <a href="managed-content.php?section=achievements" class="btn btn-sm btn-outline-secondary" target="_blank">ดูทั้งหมด</a>
        </div>
        <div class="row g-3 g-md-4">
            <?php foreach ($sections['achievements'] as $item): ?>
                <div class="col-md-6 col-xl-4">
                    <div class="portfolio-card">
                        <?php if (isset($item['image_path']) && trim($item['image_path']) !== ''): ?>
                            <img src="<?= e(normalize_asset_url($item['image_path'])) ?>" class="portfolio-image" alt="<?= e(isset($item['title']) ? $item['title'] : '') ?>">
                        <?php endif; ?>
                        <div class="p-3 p-md-4">
                            <h3 class="h5"><?= e(isset($item['title']) ? $item['title'] : '') ?></h3>
                            <?php if (isset($item['subtitle']) && trim($item['subtitle']) !== ''): ?>
                                <p class="muted mb-2"><?= e($item['subtitle']) ?></p>
                            <?php endif; ?>
                            <p class="mb-3"><?= e(isset($item['body']) ? $item['body'] : '') ?></p>
                            <?php if (isset($item['url']) && trim($item['url']) !== ''): ?>
                                <a class="btn btn-sm btn-brand" href="<?= e($item['url']) ?>" target="_blank">เปิดรายละเอียด</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section id="directory" class="section-block p-4 p-md-5 mb-4 mb-md-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="section-title h3 mb-0">ทำเนียบบุคลากรพยาบาล</h2>
            <a href="managed-content.php?section=directory" class="btn btn-sm btn-outline-secondary" target="_blank">ดูข้อมูลตาราง</a>
        </div>
        <div class="row g-3 g-md-4">
            <?php foreach ($sections['directory'] as $member): ?>
                <div class="col-sm-6 col-xl-3">
                    <div class="team-card">
                        <img src="<?= e(normalize_asset_url(isset($member['image_path']) ? $member['image_path'] : 'img/team-1.jpg')) ?>" class="team-image" alt="<?= e(isset($member['title']) ? $member['title'] : '') ?>">
                        <div class="p-3">
                            <h3 class="h6 mb-1"><?= e(isset($member['title']) ? $member['title'] : '') ?></h3>
                            <p class="muted small mb-2"><?= e(isset($member['subtitle']) ? $member['subtitle'] : '') ?></p>
                            <p class="small mb-0"><?= e(isset($member['body']) ? $member['body'] : '') ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section id="links" class="section-block p-4 p-md-5 mb-4 mb-md-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="section-title h3 mb-0">ลิงก์สำคัญสำหรับงานพยาบาล</h2>
            <a href="admin/content.php?section=links" class="btn btn-sm btn-outline-secondary" target="_blank">จัดการลิงก์</a>
        </div>
        <div class="row g-3 g-md-4">
            <?php foreach ($sections['links'] as $link): ?>
                <div class="col-md-6 col-xl-4">
                    <div class="link-card p-3 p-md-4">
                        <h3 class="h5 mb-2"><?= e(isset($link['title']) ? $link['title'] : '') ?></h3>
                        <p class="muted mb-2"><?= e(isset($link['subtitle']) ? $link['subtitle'] : '') ?></p>
                        <p class="mb-3"><?= e(isset($link['body']) ? $link['body'] : '') ?></p>
                        <?php if (isset($link['url']) && trim($link['url']) !== ''): ?>
                            <a class="btn btn-sm btn-brand" href="<?= e($link['url']) ?>" target="_blank"><i class="bi bi-box-arrow-up-right me-1"></i> ไปยังลิงก์</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section id="activity" class="section-block p-4 p-md-5 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="section-title h3 mb-0">ภาพกิจกรรม</h2>
            <a href="admin/content.php?section=activity" class="btn btn-sm btn-outline-secondary" target="_blank">จัดการกิจกรรม</a>
        </div>
        <div class="row g-3 g-md-4">
            <?php foreach ($sections['activity'] as $act): ?>
                <?php if (!isset($act['image_path']) || trim($act['image_path']) === '') { continue; } ?>
                <div class="col-6 col-md-4 col-xl-3">
                    <a class="activity-card d-block" href="<?= e(normalize_asset_url($act['image_path'])) ?>" target="_blank">
                        <img src="<?= e(normalize_asset_url($act['image_path'])) ?>" class="activity-image" alt="<?= e(isset($act['title']) ? $act['title'] : 'activity') ?>">
                        <div class="p-2 small muted text-center"><?= e(isset($act['title']) ? $act['title'] : 'กิจกรรม') ?></div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="footer-box p-4 p-md-5">
        <div class="row g-3 align-items-center">
            <div class="col-lg-6">
                <h3 class="h4 mb-2"><?= e($siteTitle) ?></h3>
                <p class="mb-0">เว็บไซต์เพื่อการสื่อสารผลงานและข้อมูลสำคัญของกลุ่มการพยาบาล</p>
            </div>
            <div class="col-lg-6">
                <div class="small">
                    <div><i class="bi bi-geo-alt me-2"></i><?= e($contactAddress) ?></div>
                    <div><i class="bi bi-telephone me-2"></i><?= e($contactPhone) ?></div>
                    <div><i class="bi bi-envelope me-2"></i><?= e($contactEmail) ?></div>
                    <div class="mt-2"><a href="<?= e($facebookUrl) ?>" target="_blank"><i class="bi bi-facebook me-2"></i>ติดตาม Facebook</a></div>
                </div>
            </div>
        </div>
    </section>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
