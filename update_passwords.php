<?php
require_once __DIR__ . '/config/db.php';
$passwordHash = password_hash('password', PASSWORD_DEFAULT);
$stmt = $mysqli->prepare('UPDATE users SET password = ? WHERE phone IN (\'0900000000\', \'0912345678\')');
$stmt->bind_param('s', $passwordHash);
if ($stmt->execute()) {
    echo "Passwords updated successfully to 'password'\n";
} else {
    echo "Error updating passwords: " . $mysqli->error . "\n";
}
$stmt->close();
$mysqli->close();
