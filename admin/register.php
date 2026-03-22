<?php

require_once __DIR__ . '/../bootstrap.php';

$isLoggedIn = is_admin_logged_in();
$totalAdmins = (int)$db->query('SELECT COUNT(*) FROM admins')->fetchColumn();
$allowPublicBootstrapRegister = ($totalAdmins === 0);
$canAccessRegister = $allowPublicBootstrapRegister || $isLoggedIn;

if (!$canAccessRegister) {
    flash('notice', 'ระบบปิดการลงทะเบียนสาธารณะ กรุณาเข้าสู่ระบบ');
    redirect('/nurse_srnh/admin/login.php');
}

if (empty($_SESSION['register_csrf_token'])) {
    $_SESSION['register_csrf_token'] = bin2hex(random_bytes(32));
}

$error = null;
$success = flash('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = isset($_POST['csrf_token']) ? (string)$_POST['csrf_token'] : '';
    $sessionToken = isset($_SESSION['register_csrf_token']) ? (string)$_SESSION['register_csrf_token'] : '';

    if ($csrfToken === '' || $sessionToken === '' || !hash_equals($sessionToken, $csrfToken)) {
        $error = 'คำขอไม่ถูกต้อง (CSRF token ไม่ถูกต้อง)';
    }

    $username = trim(isset($_POST['username']) ? (string)$_POST['username'] : '');
    $password = isset($_POST['password']) ? (string)$_POST['password'] : '';
    $confirmPassword = isset($_POST['confirm_password']) ? (string)$_POST['confirm_password'] : '';

    if ($error === null) {
        if ($username === '' || $password === '' || $confirmPassword === '') {
            $error = 'กรุณากรอกข้อมูลให้ครบทุกช่อง';
        } elseif (strlen($username) < 3 || strlen($username) > 100) {
            $error = 'Username ต้องมีความยาว 3-100 ตัวอักษร';
        } elseif (!preg_match('/^[a-zA-Z0-9._-]+$/', $username)) {
            $error = 'Username ใช้ได้เฉพาะ a-z, A-Z, 0-9 และ . _ -';
        } elseif (strlen($password) < 6) {
            $error = 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร';
        } elseif ($password !== $confirmPassword) {
            $error = 'รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน';
        }
    }

    if ($error === null) {
        $stmt = $db->prepare('SELECT id FROM admins WHERE username = :username LIMIT 1');
        $stmt->execute(array('username' => $username));
        $exists = $stmt->fetch();

        if ($exists) {
            $error = 'Username นี้ถูกใช้งานแล้ว';
        } else {
            $stmt = $db->prepare('INSERT INTO admins (username, password_hash, created_at) VALUES (:username, :password_hash, NOW())');
            $stmt->execute(array(
                'username' => $username,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ));

            $_SESSION['register_csrf_token'] = bin2hex(random_bytes(32));

            if ($isLoggedIn) {
                $success = 'เพิ่มบัญชีผู้ดูแลเรียบร้อยแล้ว';
            } else {
                flash('success', 'ลงทะเบียนสำเร็จ กรุณาเข้าสู่ระบบ');
                redirect('/nurse_srnh/admin/login.php');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Register</title>
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

        .register-card {
            max-width: 460px;
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
    <div class="register-card p-4">
        <div class="text-center mb-3">
            <span class="brand-chip">Nurse SRNH</span>
            <h1 class="h4 mt-3 mb-1"><?= $isLoggedIn ? 'เพิ่มบัญชีผู้ดูแล' : 'Admin Register' ?></h1>
            <p class="text-muted mb-0"><?= $allowPublicBootstrapRegister ? 'ตั้งค่าบัญชีผู้ดูแลระบบครั้งแรก' : 'เพิ่มบัญชีผู้ใช้งานหลังบ้าน' ?></p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= h($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= h($success) ?></div>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= h($_SESSION['register_csrf_token']) ?>">

            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" minlength="3" maxlength="100" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" minlength="6" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control" minlength="6" required>
            </div>
            <button type="submit" class="btn btn-brand w-100">ลงทะเบียน</button>
        </form>

        <p class="small mt-3 mb-0 text-center">
            <?php if ($isLoggedIn): ?>
                <a href="/nurse_srnh/admin/dashboard.php">กลับไปหน้า Dashboard</a>
            <?php else: ?>
                มีบัญชีแล้ว?
                <a href="/nurse_srnh/admin/login.php">กลับไปหน้าเข้าสู่ระบบ</a>
            <?php endif; ?>
        </p>
    </div>
</body>
</html>
