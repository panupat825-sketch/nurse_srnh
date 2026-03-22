<?php

require_once __DIR__ . '/../bootstrap.php';

if (is_admin_logged_in()) {
    redirect('/nurse_srnh/admin/dashboard.php');
}

$error = null;
$success = flash('success');
$notice = flash('notice');
$totalAdmins = (int)$db->query('SELECT COUNT(*) FROM admins')->fetchColumn();
$allowRegisterLink = ($totalAdmins === 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim(isset($_POST['username']) ? (string)$_POST['username'] : '');
    $password = isset($_POST['password']) ? (string)$_POST['password'] : '';

    $stmt = $db->prepare('SELECT id, username, password_hash FROM admins WHERE username = :username LIMIT 1');
    $stmt->execute(array('username' => $username));
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        $_SESSION['admin_id'] = (int)$admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        redirect('/nurse_srnh/admin/dashboard.php');
    }

    $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            min-height: 100vh;
            background:
                radial-gradient(900px 400px at 20% 0%, #dbf3ef 0%, transparent 70%),
                radial-gradient(900px 400px at 100% 20%, #ffe7d7 0%, transparent 70%),
                #f2f6f8;
        }

        .login-card {
            max-width: 430px;
            border-radius: 18px;
            border: 1px solid rgba(255,255,255,.65);
            box-shadow: 0 14px 36px rgba(11, 55, 66, .12);
            background: rgba(255,255,255,.88);
            backdrop-filter: blur(5px);
        }

        .brand-chip {
            display: inline-block;
            padding: .3rem .75rem;
            border-radius: 999px;
            background: #e6f3f1;
            color: #0b5d5b;
            font-weight: 600;
            font-size: .85rem;
        }

        .btn-brand {
            background: #0b5d5b;
            border-color: #0b5d5b;
            color: #fff;
        }

        .btn-brand:hover {
            background: #094d4b;
            border-color: #094d4b;
            color: #fff;
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center p-3">
    <div class="login-card p-4">
        <div class="text-center mb-3">
            <span class="brand-chip">Nurse SRNH</span>
            <h1 class="h4 mt-3 mb-1">Admin Sign In</h1>
            <p class="text-muted mb-0">จัดการเนื้อหาเว็บไซต์ได้จากหน้านี้</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= h($success) ?></div>
        <?php endif; ?>

        <?php if ($notice): ?>
            <div class="alert alert-warning"><?= h($notice) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= h($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-brand w-100">เข้าสู่ระบบ</button>
        </form>

        <?php if ($allowRegisterLink): ?>
            <p class="small text-center mt-3 mb-1">
                ยังไม่มีบัญชี?
                <a href="/nurse_srnh/admin/register.php">ลงทะเบียนเข้าใช้</a>
            </p>
        <?php endif; ?>
        <p class="small text-muted mb-0 text-center">ค่าเริ่มต้นหลังติดตั้ง: admin / admin1234</p>
    </div>
</body>
</html>
