<?php

function h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect($path)
{
    header('Location: ' . $path);
    exit;
}

function is_admin_logged_in()
{
    return !empty($_SESSION['admin_id']);
}

function require_admin_login()
{
    if (!is_admin_logged_in()) {
        redirect('/nurse_srnh/admin/login.php');
    }
}

function flash($key, $value = null)
{
    if ($value !== null) {
        $_SESSION['_flash'][$key] = $value;
        return null;
    }

    if (!isset($_SESSION['_flash'][$key])) {
        return null;
    }

    $message = $_SESSION['_flash'][$key];
    unset($_SESSION['_flash'][$key]);
    return $message;
}
