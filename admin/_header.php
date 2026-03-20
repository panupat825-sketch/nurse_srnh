<?php
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h(isset($title) ? $title : 'Admin') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container">
        <a class="navbar-brand" href="/nurse_srnh/admin/dashboard.php">Nurse SRNH Admin</a>
        <div class="navbar-nav ms-auto">
            <a class="nav-link text-white" href="/nurse_srnh/admin/dashboard.php">Dashboard</a>
            <a class="nav-link text-white" href="/nurse_srnh/admin/content.php">Content CRUD</a>
            <a class="nav-link text-white" href="/nurse_srnh/admin/settings.php">Settings</a>
            <a class="nav-link text-white" href="/nurse_srnh/admin/logout.php">Logout</a>
        </div>
    </div>
</nav>
<main class="container pb-5">
