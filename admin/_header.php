<?php
$currentPage = basename(isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h(isset($title) ? $title : 'Admin') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --brand: #0b5d5b;
            --brand-2: #128c7e;
            --accent: #ef7d32;
            --bg-soft: #f3f7f8;
            --ink: #1f2d3d;
        }

        body {
            font-family: 'Sarabun', sans-serif;
            color: var(--ink);
            background:
                radial-gradient(1200px 500px at -10% -10%, #dff2ef 0%, transparent 70%),
                radial-gradient(1000px 450px at 110% -20%, #ffe8d8 0%, transparent 70%),
                var(--bg-soft);
            min-height: 100vh;
        }

        .topbar {
            background: linear-gradient(120deg, var(--brand), var(--brand-2));
            box-shadow: 0 10px 30px rgba(11, 93, 91, .25);
        }

        .navbar-brand {
            font-weight: 700;
            letter-spacing: .3px;
        }

        .nav-link {
            border-radius: 999px;
            margin-left: .35rem;
            color: rgba(255, 255, 255, .88) !important;
            padding: .45rem .9rem !important;
            transition: all .2s ease;
        }

        .nav-link:hover,
        .nav-link.active {
            color: #fff !important;
            background: rgba(255, 255, 255, .18);
        }

        .admin-shell {
            max-width: 1180px;
        }

        .glass-card {
            border: 1px solid rgba(255, 255, 255, .55);
            border-radius: 18px;
            background: rgba(255, 255, 255, .88);
            box-shadow: 0 10px 25px rgba(8, 33, 41, .08);
        }

        .page-title {
            font-weight: 700;
            letter-spacing: .2px;
        }

        .text-soft {
            color: #607382;
        }

        .btn-brand {
            background: var(--brand);
            border-color: var(--brand);
            color: #fff;
        }

        .btn-brand:hover {
            background: #094c4a;
            border-color: #094c4a;
            color: #fff;
        }

        .chip {
            display: inline-block;
            font-size: .79rem;
            border-radius: 999px;
            padding: .18rem .65rem;
            background: #e9f4f2;
            color: #0c5a58;
            margin: 0 .4rem .4rem 0;
            text-decoration: none;
        }

        .chip:hover {
            background: #d6eeea;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg topbar mb-4">
    <div class="container admin-shell">
        <a class="navbar-brand text-white" href="/nurse_srnh/admin/dashboard.php">Nurse SRNH Admin</a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav" aria-controls="adminNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="adminNav">
            <div class="navbar-nav ms-auto">
                <a class="nav-link <?= ($currentPage === 'dashboard.php') ? 'active' : '' ?>" href="/nurse_srnh/admin/dashboard.php">Dashboard</a>
                <a class="nav-link <?= ($currentPage === 'content.php') ? 'active' : '' ?>" href="/nurse_srnh/admin/content.php">Content CRUD</a>
                <a class="nav-link <?= ($currentPage === 'personnel.php') ? 'active' : '' ?>" href="/nurse_srnh/admin/personnel.php">ทำเนียบบุคลากร</a>
                <a class="nav-link <?= ($currentPage === 'settings.php') ? 'active' : '' ?>" href="/nurse_srnh/admin/settings.php">Settings</a>
                <a class="nav-link" href="/nurse_srnh/admin/logout.php">Logout</a>
            </div>
        </div>
    </div>
</nav>
<main class="container admin-shell pb-5">

