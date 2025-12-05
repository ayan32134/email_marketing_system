<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

$message = "Admin access is read-only. Members create and manage their own accounts.";

header("Location: ../admin-dashboard.php?message=" . urlencode($message) . "&type=error");
exit;
?>
