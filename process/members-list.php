<?php
session_start();

if (isset($_GET['edit_id'])) {
    $_SESSION['member_id'] = $_GET['edit_id']; // store the clicked member_id
    header("Location: edit-member.php"); // redirect to edit page
    exit;
}
?>