<?php

require_once __DIR__ . '/../bootstrap.php';

session_unset();
session_destroy();

redirect('/nurse_srnh/admin/login.php');
