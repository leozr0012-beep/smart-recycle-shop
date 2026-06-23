<?php
require_once __DIR__ . '/config/config.php';

$isAdmin = isAdmin();

session_unset();
session_destroy();

if ($isAdmin) {
    header('Location: ' . BASE_URL . '/admin/login.php');
} else {
    header('Location: ' . BASE_URL . '/login.php');
}
exit;
