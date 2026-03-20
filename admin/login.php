<?php

require_once __DIR__ . '/../bootstrap.php';

if (is_admin_logged_in()) {
    redirect('/nurse_srnh/admin/dashboard.php');
}

$error = null;

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

$title = 'Admin Login';
include __DIR__ . '/_header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 mb-3">เข้าสู่ระบบผู้ดูแล</h1>
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
                    <button type="submit" class="btn btn-primary w-100">Login</button>
                </form>
                <p class="text-muted small mt-3 mb-0">ค่าเริ่มต้นหลังติดตั้ง: admin / admin1234</p>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/_footer.php'; ?>
