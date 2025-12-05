<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

$message = "Member management is read-only. Please ask members to update their own profiles.";

header("Location: ../admin-dashboard.php?message=" . urlencode($message) . "&type=error");
exit;
?>
