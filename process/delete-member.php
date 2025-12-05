<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

$message = "Member removal is disabled. Each client controls their own subscription.";

header("Location: ../admin-dashboard.php?message=" . urlencode($message) . "&type=error");
exit;
?>
