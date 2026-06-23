<?php
require_once __DIR__ . '/../config/config.php';

if (isAdmin()) {
    redirect(BASE_URL . '/admin/dashboard.php');
} else {
    redirect(BASE_URL . '/admin/login.php');
}
