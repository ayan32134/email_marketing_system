<?php
require_once 'classes/admin.php';

// Initialize Admin
$sql = new Admin();

// ===== Pagination and Search Code =====
$limit = 2; // members per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

$search = isset($_GET['search']) ? $_GET['search'] : '';

// Count total members
$total_sql = "SELECT COUNT(*) AS total FROM members";
if ($search !== '') {
    $total_sql .= " WHERE member_name LIKE '%" . addslashes($search) . "%'";
}
$total_result = $sql->db->query($total_sql);
$total_row = $total_result->fetch_assoc();
$total_members = $total_row['total'];
$total_pages = ceil($total_members / $limit);

// Fetch members for current page
$sql_fetch = "SELECT * FROM members";
if ($search !== '') {
    $sql_fetch .= " WHERE member_name LIKE '%" . addslashes($search) . "%'";
}
$sql_fetch .= " ORDER BY created_at DESC LIMIT $start, $limit";

$result = $sql->db->query($sql_fetch);
// ===== End Pagination and Search Code =====
?>