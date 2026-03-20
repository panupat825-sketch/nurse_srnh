<?php

$baseConfig = require __DIR__ . '/config.php';
$defaultDb = $baseConfig['db'];
$message = null;
$error = null;

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
    file_put_contents(__DIR__ . '/config.local.php', $content);
}

function seed_defaults($pdo)
{
    $settingStmt = $pdo->prepare(
        "INSERT INTO settings (setting_key, setting_value, updated_at)
         VALUES (:k, :v, NOW())
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()"
    );

    $settings = array(
        'site_title' => 'Nurse SRNH',
        'site_subtitle' => 'Content Management System',
        'contact_address' => '182 M.15 Sikeaw Sirattana Sisaket',
        'contact_phone' => '045677014',
        'contact_email' => 'admin@example.com',
        'facebook_url' => 'https://www.facebook.com/sirattanahosp/',
    );

    foreach ($settings as $k => $v) {
        $settingStmt->execute(array('k' => $k, 'v' => $v));
    }

    $contentStmt = $pdo->prepare(
        "INSERT INTO content_items (section, title, subtitle, body, url, image_path, sort_order, is_active, created_at, updated_at)
         VALUES (:section, :title, :subtitle, :body, :url, :image_path, :sort_order, :is_active, NOW(), NOW())"
    );

    $existing = (int)$pdo->query("SELECT COUNT(*) FROM content_items")->fetchColumn();
    if ($existing === 0) {
        $seedRows = array(
            array('section' => 'menu', 'title' => 'HOME', 'subtitle' => '', 'body' => 'ลิงก์หน้าแรก', 'url' => 'index.php', 'image_path' => '', 'sort_order' => 1, 'is_active' => 1),
            array('section' => 'menu', 'title' => 'CONTACT', 'subtitle' => '', 'body' => 'หน้าติดต่อ', 'url' => 'contact.php', 'image_path' => '', 'sort_order' => 2, 'is_active' => 1),
            array('section' => 'activity', 'title' => 'กิจกรรมตัวอย่าง 1', 'subtitle' => '', 'body' => 'อัปเดตได้จากหลังบ้าน', 'url' => '', 'image_path' => 'activity/activity1.jpg', 'sort_order' => 1, 'is_active' => 1),
            array('section' => 'activity', 'title' => 'กิจกรรมตัวอย่าง 2', 'subtitle' => '', 'body' => 'เพิ่มได้ไม่จำกัดรายการ', 'url' => '', 'image_path' => 'activity/activity2.jpg', 'sort_order' => 2, 'is_active' => 1),
            array('section' => 'service', 'title' => 'THAILAND NURSING DIGITAL PLATFORM', 'subtitle' => '', 'body' => '', 'url' => 'https://www.don.go.th/nperson/app/index.php/member/login', 'image_path' => '', 'sort_order' => 1, 'is_active' => 1),
        );

        foreach ($seedRows as $row) {
            $contentStmt->execute($row);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbHost = trim(isset($_POST['db_host']) ? (string)$_POST['db_host'] : $defaultDb['host']);
    $dbPort = (int)(isset($_POST['db_port']) ? $_POST['db_port'] : $defaultDb['port']);
    $dbName = trim(isset($_POST['db_name']) ? (string)$_POST['db_name'] : $defaultDb['name']);
    $dbUser = trim(isset($_POST['db_user']) ? (string)$_POST['db_user'] : $defaultDb['user']);
    $dbPass = isset($_POST['db_pass']) ? (string)$_POST['db_pass'] : $defaultDb['pass'];

    $adminUser = trim(isset($_POST['admin_user']) ? (string)$_POST['admin_user'] : 'admin');
    $adminPass = isset($_POST['admin_pass']) ? (string)$_POST['admin_pass'] : 'admin1234';

    try {
        $dsnNoDb = sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $dbHost, $dbPort);
        $rootPdo = new PDO($dsnNoDb, $dbUser, $dbPass, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
        $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        $dsnDb = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $dbHost, $dbPort, $dbName);
        $pdo = new PDO($dsnDb, $dbUser, $dbPass, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));

        $schemaSql = file_get_contents(__DIR__ . '/installer/schema.sql');
        $statements = array_filter(array_map('trim', explode(';', (string)$schemaSql)));
        foreach ($statements as $statement) {
            if ($statement !== '') {
                $pdo->exec($statement);
            }
        }

        $hash = password_hash($adminPass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare(
            "INSERT INTO admins (username, password_hash, created_at)
             VALUES (:username, :password_hash, NOW())
             ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)"
        );
        $stmt->execute(array('username' => $adminUser, 'password_hash' => $hash));

        seed_defaults($pdo);
        write_local_config(array(
            'host' => $dbHost,
            'port' => $dbPort,
            'name' => $dbName,
            'user' => $dbUser,
            'pass' => $dbPass,
        ));

        $message = 'ติดตั้งสำเร็จแล้ว สามารถเข้าใช้งานที่ /nurse_srnh/admin/login.php';
    } catch (Exception $e) {
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h4 mb-3">ติดตั้งระบบ Nurse SRNH CMS</h1>
                    <?php if ($message): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                    <form method="post" class="row g-3">
                        <div class="col-md-6"><label class="form-label">DB Host</label><input class="form-control" name="db_host" value="<?= htmlspecialchars((string)$defaultDb['host'], ENT_QUOTES, 'UTF-8') ?>" required></div>
                        <div class="col-md-6"><label class="form-label">DB Port</label><input class="form-control" name="db_port" value="<?= (int)$defaultDb['port'] ?>" required></div>
                        <div class="col-md-6"><label class="form-label">DB Name</label><input class="form-control" name="db_name" value="<?= htmlspecialchars((string)$defaultDb['name'], ENT_QUOTES, 'UTF-8') ?>" required></div>
                        <div class="col-md-6"><label class="form-label">DB User</label><input class="form-control" name="db_user" value="<?= htmlspecialchars((string)$defaultDb['user'], ENT_QUOTES, 'UTF-8') ?>" required></div>
                        <div class="col-12"><label class="form-label">DB Password</label><input class="form-control" name="db_pass" type="password" value=""></div>
                        <hr class="my-2">
                        <div class="col-md-6"><label class="form-label">Admin Username</label><input class="form-control" name="admin_user" value="admin" required></div>
                        <div class="col-md-6"><label class="form-label">Admin Password</label><input class="form-control" name="admin_pass" value="admin1234" required></div>
                        <div class="col-12 d-grid"><button class="btn btn-primary" type="submit">เริ่มติดตั้ง</button></div>
                    </form>
                    <p class="text-muted small mt-3 mb-0">หลังติดตั้งเสร็จ แนะนำลบหรือปิดการเข้าถึงไฟล์ install.php เพื่อความปลอดภัย</p>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
