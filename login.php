<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if (is_admin_logged_in()) {
    redirect('/nurse_srnh/admin/dashboard.php');
}

redirect('/nurse_srnh/admin/login.php');
