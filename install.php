<?php

$baseConfig = require __DIR__ . '/config.php';
$defaultDb = $baseConfig['db'];

$message = null;
$error = null;
$links = array();

$form = array(
    'db_host' => isset($defaultDb['host']) ? (string)$defaultDb['host'] : '127.0.0.1',
    'db_port' => isset($defaultDb['port']) ? (string)$defaultDb['port'] : '3306',
    'db_name' => isset($defaultDb['name']) ? (string)$defaultDb['name'] : 'nurse_srnh',
    'db_user' => isset($defaultDb['user']) ? (string)$defaultDb['user'] : 'root',
    'db_pass' => '',
    'admin_user' => 'admin',
    'admin_pass' => 'admin1234',
);

function h_local($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function write_local_config($db)
{
    $export = var_export(array(
        'db' => array(
            'host' => $db['host'],
            'port' => (int)$db['port'],
            'name' => $db['name'],
            'user' => $db['user'],
            'pass' => $db['pass'],
            'charset' => 'utf8mb4',
        ),
    ), true);

    $content = "<?php\nreturn " . $export . ";\n";
    $result = @file_put_contents(__DIR__ . '/config.local.php', $content);

    if ($result === false) {
        throw new RuntimeException('ไม่สามารถเขียนไฟล์ config.local.php ได้ กรุณาตรวจสอบสิทธิ์การเขียนไฟล์');
    }
}

function ensure_upload_directories()
{
    $dirs = array(
        __DIR__ . '/uploads',
        __DIR__ . '/uploads/content',
        __DIR__ . '/uploads/personnel',
    );

    foreach ($dirs as $dir) {
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            throw new RuntimeException('ไม่สามารถสร้างโฟลเดอร์อัปโหลด: ' . $dir);
        }
    }
}

function execute_schema(PDO $pdo)
{
    $schemaPath = __DIR__ . '/installer/schema.sql';
    if (!is_file($schemaPath)) {
        throw new RuntimeException('ไม่พบไฟล์ schema.sql');
    }

    $schemaSql = file_get_contents($schemaPath);
    if ($schemaSql === false) {
        throw new RuntimeException('ไม่สามารถอ่านไฟล์ schema.sql ได้');
    }

    $schemaSql = preg_replace('/^\xEF\xBB\xBF/', '', (string)$schemaSql);
    $statements = preg_split('/;\s*(\r\n|\r|\n)/', $schemaSql);

    if (!is_array($statements)) {
        throw new RuntimeException('รูปแบบ schema.sql ไม่ถูกต้อง');
    }

    foreach ($statements as $statement) {
        $statement = trim($statement);
        if ($statement === '') {
            continue;
        }
        $pdo->exec($statement);
    }
}

function seed_defaults(PDO $pdo)
{
    $settingStmt = $pdo->prepare(
        "INSERT INTO settings (setting_key, setting_value, updated_at)
         VALUES (:k, :v, NOW())
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()"
    );

    $settings = array(
        'site_title' => 'Nurse SRNH',
        'site_subtitle' => 'Nursing Excellence and Care Portfolio',
        'contact_address' => '182 M.15 Sikeaw Sirattana Sisaket',
        'contact_phone' => '045677014',
        'contact_email' => 'admin@example.com',
        'facebook_url' => 'https://www.facebook.com/sirattanahosp/',
    );

    foreach ($settings as $k => $v) {
        $settingStmt->execute(array('k' => $k, 'v' => $v));
    }

    $existing = (int)$pdo->query("SELECT COUNT(*) FROM content_items")->fetchColumn();
    if ($existing > 0) {
        return;
    }

    $contentStmt = $pdo->prepare(
        "INSERT INTO content_items (section, title, subtitle, body, url, image_path, sort_order, is_active, created_at, updated_at)
         VALUES (:section, :title, :subtitle, :body, :url, :image_path, :sort_order, :is_active, NOW(), NOW())"
    );

    $seedRows = array(
        array('section' => 'achievements', 'title' => 'พัฒนาคุณภาพการพยาบาล', 'subtitle' => 'Quality Improvement', 'body' => 'สรุปผลการพัฒนาคุณภาพและตัวชี้วัดบริการประจำปี', 'url' => '', 'image_path' => 'img/blog-1.jpg', 'sort_order' => 1, 'is_active' => 1),
        array('section' => 'achievements', 'title' => 'ผลงานวิชาการ', 'subtitle' => 'Academic Works', 'body' => 'รวบรวมบทความ งานประชุม และนวัตกรรมของทีมพยาบาล', 'url' => '', 'image_path' => 'img/blog-2.jpg', 'sort_order' => 2, 'is_active' => 1),
        array('section' => 'achievements', 'title' => 'รางวัลและการยกย่อง', 'subtitle' => 'Awards', 'body' => 'ผลงานโดดเด่นที่ได้รับการยอมรับระดับหน่วยงาน/เขต', 'url' => '', 'image_path' => 'img/blog-3.jpg', 'sort_order' => 3, 'is_active' => 1),

        array('section' => 'directory', 'title' => 'หัวหน้ากลุ่มการพยาบาล', 'subtitle' => 'Nurse Director', 'body' => 'ดูแลภาพรวมและการบริหารงานพยาบาล', 'url' => '', 'image_path' => 'img/team-1.jpg', 'sort_order' => 1, 'is_active' => 1),
        array('section' => 'directory', 'title' => 'หัวหน้าหอผู้ป่วย', 'subtitle' => 'Ward Supervisor', 'body' => 'ดูแลมาตรฐานบริการและการประสานงานทีม', 'url' => '', 'image_path' => 'img/team-2.jpg', 'sort_order' => 2, 'is_active' => 1),

        array('section' => 'links', 'title' => 'Dashboard ตัวชี้วัด', 'subtitle' => 'ข้อมูลบริการ', 'body' => 'แดชบอร์ดสถิติและผลการดำเนินงาน', 'url' => 'http://sirattanahosp.moph.go.th/dashboard/', 'image_path' => '', 'sort_order' => 1, 'is_active' => 1),
        array('section' => 'links', 'title' => 'THAILAND NURSING DIGITAL PLATFORM', 'subtitle' => 'บริการภายนอก', 'body' => 'ระบบสนับสนุนวิชาชีพการพยาบาล', 'url' => 'https://www.don.go.th/nperson/app/index.php/member/login', 'image_path' => '', 'sort_order' => 2, 'is_active' => 1),

        array('section' => 'activity', 'title' => 'กิจกรรมตัวอย่าง 1', 'subtitle' => '', 'body' => 'อัปเดตได้จากหลังบ้าน', 'url' => '', 'image_path' => 'activity/activity1.jpg', 'sort_order' => 1, 'is_active' => 1),
        array('section' => 'activity', 'title' => 'กิจกรรมตัวอย่าง 2', 'subtitle' => '', 'body' => 'เพิ่มได้ไม่จำกัดรายการ', 'url' => '', 'image_path' => 'activity/activity2.jpg', 'sort_order' => 2, 'is_active' => 1),
    );

    foreach ($seedRows as $row) {
        $contentStmt->execute($row);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['db_host'] = trim(isset($_POST['db_host']) ? (string)$_POST['db_host'] : $form['db_host']);
    $form['db_port'] = trim(isset($_POST['db_port']) ? (string)$_POST['db_port'] : $form['db_port']);
    $form['db_name'] = trim(isset($_POST['db_name']) ? (string)$_POST['db_name'] : $form['db_name']);
    $form['db_user'] = trim(isset($_POST['db_user']) ? (string)$_POST['db_user'] : $form['db_user']);
    $form['db_pass'] = isset($_POST['db_pass']) ? (string)$_POST['db_pass'] : '';

    $form['admin_user'] = trim(isset($_POST['admin_user']) ? (string)$_POST['admin_user'] : $form['admin_user']);
    $form['admin_pass'] = isset($_POST['admin_pass']) ? (string)$_POST['admin_pass'] : $form['admin_pass'];

    try {
        if (!extension_loaded('pdo_mysql')) {
            throw new RuntimeException('ไม่พบ extension pdo_mysql กรุณาเปิดใช้งานก่อนติดตั้ง');
        }

        $dbPort = (int)$form['db_port'];
        if ($dbPort <= 0) {
            throw new RuntimeException('DB Port ไม่ถูกต้อง');
        }

        if (!preg_match('/^[A-Za-z0-9_]+$/', $form['db_name'])) {
            throw new RuntimeException('DB Name ใช้ได้เฉพาะตัวอักษรอังกฤษ ตัวเลข และ _ เท่านั้น');
        }

        if ($form['admin_user'] === '' || strlen($form['admin_user']) < 3) {
            throw new RuntimeException('Admin Username ต้องมีอย่างน้อย 3 ตัวอักษร');
        }

        if (strlen($form['admin_pass']) < 6) {
            throw new RuntimeException('Admin Password ต้องมีอย่างน้อย 6 ตัวอักษร');
        }

        $dsnNoDb = sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $form['db_host'], $dbPort);
        $rootPdo = new PDO($dsnNoDb, $form['db_user'], $form['db_pass'], array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));

        $safeDbName = str_replace('`', '``', $form['db_name']);
        $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `{$safeDbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        $dsnDb = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $form['db_host'], $dbPort, $form['db_name']);
        $pdo = new PDO($dsnDb, $form['db_user'], $form['db_pass'], array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));

        $pdo->beginTransaction();
        execute_schema($pdo);

        $hash = password_hash($form['admin_pass'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare(
            "INSERT INTO admins (username, password_hash, created_at)
             VALUES (:username, :password_hash, NOW())
             ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)"
        );
        $stmt->execute(array('username' => $form['admin_user'], 'password_hash' => $hash));

        seed_defaults($pdo);
        $pdo->commit();

        ensure_upload_directories();

        write_local_config(array(
            'host' => $form['db_host'],
            'port' => $dbPort,
            'name' => $form['db_name'],
            'user' => $form['db_user'],
            'pass' => $form['db_pass'],
        ));

        $message = 'ติดตั้งสำเร็จแล้ว สามารถเริ่มดูผลงานและใช้งานระบบได้ทันที';
        $links = array(
            array('label' => 'ดูหน้าเว็บหลัก', 'href' => '/nurse_srnh/index.php', 'class' => 'btn btn-success'),
            array('label' => 'ดูผลงาน (achievements)', 'href' => '/nurse_srnh/managed-content.php?section=achievements', 'class' => 'btn btn-outline-primary'),
            array('label' => 'เข้าสู่ระบบหลังบ้าน', 'href' => '/nurse_srnh/admin/login.php', 'class' => 'btn btn-primary'),
        );
    } catch (Exception $e) {
        if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nurse SRNH Installer</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; background: #f3f7fb; }
        .install-card { border-radius: 18px; border: 1px solid #d9e7ef; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm install-card">
                <div class="card-body p-4 p-md-5">
                    <h1 class="h4 mb-2">ติดตั้งระบบ Nurse SRNH CMS</h1>
                    <p class="text-muted mb-4">กรอกค่าฐานข้อมูลและบัญชีผู้ดูแล ระบบจะสร้างตารางและข้อมูลตัวอย่างให้พร้อมดูผลงานทันที</p>

                    <?php if ($message): ?>
                        <div class="alert alert-success"><?= h_local($message) ?></div>
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <?php foreach ($links as $link): ?>
                                <a class="<?= h_local($link['class']) ?>" href="<?= h_local($link['href']) ?>" target="_blank"><?= h_local($link['label']) ?></a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= h_local($error) ?></div>
                    <?php endif; ?>

                    <form method="post" class="row g-3" autocomplete="off">
                        <div class="col-md-6">
                            <label class="form-label">DB Host</label>
                            <input class="form-control" name="db_host" value="<?= h_local($form['db_host']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">DB Port</label>
                            <input class="form-control" name="db_port" value="<?= h_local($form['db_port']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">DB Name</label>
                            <input class="form-control" name="db_name" value="<?= h_local($form['db_name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">DB User</label>
                            <input class="form-control" name="db_user" value="<?= h_local($form['db_user']) ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">DB Password</label>
                            <input class="form-control" name="db_pass" type="password" value="<?= h_local($form['db_pass']) ?>">
                        </div>

                        <hr class="my-2">

                        <div class="col-md-6">
                            <label class="form-label">Admin Username</label>
                            <input class="form-control" name="admin_user" value="<?= h_local($form['admin_user']) ?>" minlength="3" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Admin Password</label>
                            <input class="form-control" name="admin_pass" value="<?= h_local($form['admin_pass']) ?>" minlength="6" required>
                        </div>

                        <div class="col-12 d-grid mt-3">
                            <button class="btn btn-primary" type="submit">เริ่มติดตั้ง</button>
                        </div>
                    </form>

                    <p class="text-muted small mt-4 mb-0">
                        หลังติดตั้งเสร็จ แนะนำลบหรือปิดการเข้าถึงไฟล์ <code>install.php</code> เพื่อความปลอดภัย
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
