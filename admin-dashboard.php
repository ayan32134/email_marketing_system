<?php
session_start();
require_once 'classes/admin.php';
require_once 'classes/BaseModelHelper.php';
require_once 'config/Database.php';

// Check admin session
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$db = new dataBase(['host' => 'localhost', 'user' => 'root', 'password' => 'root', 'database' => 'email_marketing_system']);

$admin = new Admin();
$admin->db = $db->db;

// Get all members
$members = BaseModelHelper::mysqliGetAll($db->db, 'Members', []);

// Get all campaigns
$allCampaigns = BaseModelHelper::mysqliGetAll($db->db, 'Campaigns', []);

// Get campaign metrics
$metricsSql = "SELECT 
    m.member_id,
    m.member_name,
    COUNT(DISTINCT c.campaign_id) as total_campaigns,
    COUNT(DISTINCT CASE WHEN c.campaign_status = 'Active' THEN c.campaign_id END) as active_campaigns,
    COUNT(DISTINCT el.email_log_id) as total_emails_sent,
    COUNT(DISTINCT CASE WHEN el.delivery_status = 'Failed' THEN el.email_log_id END) as failed_emails,
    COUNT(DISTINCT CASE WHEN el.delivery_status = 'Opened' THEN el.email_log_id END) as opened_emails,
    COUNT(DISTINCT CASE WHEN el.delivery_status = 'Clicked' THEN el.email_log_id END) as clicked_emails
FROM Members m
LEFT JOIN Campaigns c ON m.member_id = c.member_id
LEFT JOIN Email_Log el ON c.campaign_id = el.campaign_id
GROUP BY m.member_id, m.member_name
ORDER BY total_emails_sent DESC";

$metricsResult = $db->db->query($metricsSql);
$campaignMetrics = [];
if ($metricsResult && $metricsResult->num_rows > 0) {
    $campaignMetrics = $metricsResult->fetch_all(MYSQLI_ASSOC);
}

// Get system-wide statistics
$totalMembers = count($members);
$totalCampaigns = count($allCampaigns);
$activeCampaigns = count(array_filter($allCampaigns, function($c) { return $c['campaign_status'] == 'Active'; }));

$totalEmailsSql = "SELECT COUNT(*) as count FROM Email_Log WHERE delivery_status = 'Sent'";
$totalEmailsResult = $db->db->query($totalEmailsSql);
$totalEmailsSent = $totalEmailsResult ? (int)$totalEmailsResult->fetch_assoc()['count'] : 0;

$failedEmailsSql = "SELECT COUNT(*) as count FROM Email_Log WHERE delivery_status = 'Failed'";
$failedEmailsResult = $db->db->query($failedEmailsSql);
$totalFailed = $failedEmailsResult ? (int)$failedEmailsResult->fetch_assoc()['count'] : 0;

// Get recent campaigns across all members
$recentCampaignsSql = "SELECT c.*, m.member_name 
                      FROM Campaigns c 
                      JOIN Members m ON c.member_id = m.member_id 
                      ORDER BY c.created_at DESC 
                      LIMIT 10";
$recentCampaignsResult = $db->db->query($recentCampaignsSql);
$recentCampaigns = [];
if ($recentCampaignsResult && $recentCampaignsResult->num_rows > 0) {
    $recentCampaigns = $recentCampaignsResult->fetch_all(MYSQLI_ASSOC);
}

// Get audit trail
$auditSql = "SELECT at.*, m.member_name, a.admin_email 
             FROM Audit_Trail at
             LEFT JOIN Members m ON at.member_id = m.member_id
             LEFT JOIN Admins a ON at.admin_id = a.admin_id
             ORDER BY at.performed_on DESC 
             LIMIT 50";
$auditResult = $db->db->query($auditSql);
$auditTrail = [];
if ($auditResult && $auditResult->num_rows > 0) {
    $auditTrail = $auditResult->fetch_all(MYSQLI_ASSOC);
}

// Get all email logs
$emailLogsSql = "SELECT el.*, c.campaign_name, m.member_name, ct.email as recipient_email 
                 FROM Email_Log el
                 LEFT JOIN Campaigns c ON el.campaign_id = c.campaign_id
                 LEFT JOIN Members m ON c.member_id = m.member_id
                 LEFT JOIN Contacts ct ON el.contact_id = ct.contact_id
                 ORDER BY el.sent_on DESC 
                 LIMIT 100";
$emailLogsResult = $db->db->query($emailLogsSql);
$emailLogs = [];
if ($emailLogsResult && $emailLogsResult->num_rows > 0) {
    $emailLogs = $emailLogsResult->fetch_all(MYSQLI_ASSOC);
}

$flashMessage = isset($_GET['message']) ? trim($_GET['message']) : '';
$flashType = $_GET['type'] ?? 'info';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Email Marketing System</title>
    <style>
        /* ====== Dark Theme Variables ====== */
        :root {
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --bg-tertiary: #334155;
            --bg-hover: #475569;
            --text-primary: #f1f5f9;
            --text-secondary: #cbd5e1;
            --text-muted: #94a3b8;
            --accent: #3b82f6;
            --accent-hover: #2563eb;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --border: #334155;
            --shadow: rgba(0, 0, 0, 0.3);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', 'Segoe UI', Roboto, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        /* ====== Sidebar ====== */
        .sidebar {
            width: 260px;
            background: var(--bg-secondary);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            box-shadow: 2px 0 10px var(--shadow);
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
            background: linear-gradient(135deg, var(--accent), var(--accent-hover));
        }

        .sidebar-header h2 {
            color: white;
            font-size: 1.25rem;
            font-weight: 600;
        }

        .sidebar-nav {
            flex: 1;
            overflow-y: auto;
            padding: 1rem 0;
        }

        .nav-item {
            display: block;
            padding: 0.875rem 1.5rem;
            color: var(--text-secondary);
            text-decoration: none;
            transition: all 0.2s;
            border-left: 3px solid transparent;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .nav-item:hover {
            background: var(--bg-tertiary);
            color: var(--text-primary);
            border-left-color: var(--accent);
        }

        .nav-item.active {
            background: var(--bg-tertiary);
            color: var(--accent);
            border-left-color: var(--accent);
            font-weight: 500;
        }

        .nav-item span {
            font-size: 0.9375rem;
        }

        .sidebar-footer {
            padding: 1rem;
            text-align: center;
            font-size: 0.75rem;
            color: var(--text-muted);
            border-top: 1px solid var(--border);
        }

        /* ====== Main Content ====== */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .topbar {
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .topbar h1 {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .content-area {
            flex: 1;
            overflow-y: auto;
            padding: 2rem;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert.success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid var(--success);
            color: var(--success);
        }

        .alert.error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid var(--danger);
            color: var(--danger);
        }

        /* ====== Stats Cards ====== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.5rem;
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px var(--shadow);
            border-color: var(--accent);
        }

        .stat-label {
            font-size: 0.875rem;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        /* ====== Section Container ====== */
        .section {
            display: none;
        }

        .section.active {
            display: block;
        }

        /* ====== Toolbar ====== */
        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .toolbar p {
            color: var(--text-secondary);
        }

        /* ====== Buttons ====== */
        .btn {
            padding: 0.625rem 1.25rem;
            border-radius: 8px;
            border: none;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--accent);
            color: white;
        }

        .btn-primary:hover {
            background: var(--accent-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(59, 130, 246, 0.3);
        }

        .btn-secondary {
            background: var(--bg-tertiary);
            color: var(--text-primary);
            border: 1px solid var(--border);
        }

        .btn-secondary:hover {
            background: var(--bg-hover);
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.8125rem;
        }

        /* ====== Tables ====== */
        .table-container {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: var(--bg-tertiary);
        }

        th {
            padding: 1rem;
            text-align: left;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 1rem;
            border-top: 1px solid var(--border);
            color: var(--text-primary);
        }

        tbody tr {
            transition: background 0.2s;
        }

        tbody tr:hover {
            background: var(--bg-tertiary);
        }

        /* ====== Status Badges ====== */
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .badge-success {
            background: rgba(16, 185, 129, 0.2);
            color: var(--success);
        }

        .badge-danger {
            background: rgba(239, 68, 68, 0.2);
            color: var(--danger);
        }

        .badge-warning {
            background: rgba(245, 158, 11, 0.2);
            color: var(--warning);
        }

        .badge-info {
            background: rgba(59, 130, 246, 0.2);
            color: var(--accent);
        }

        .badge-muted {
            background: rgba(148, 163, 184, 0.2);
            color: var(--text-muted);
        }

        /* ====== Modal ====== */
        .modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            backdrop-filter: blur(4px);
        }

        .modal-backdrop.active {
            display: flex;
            align-item
        }

        .modal {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 16px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }

        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            font-size: 1.25rem;
            font-weight: 600;
        }

        .modal-close {
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.25rem;
            line-height: 1;
        }

        .modal-close:hover {
            color: var(--text-primary);
        }

        .modal-body {
            padding: 1.5rem;
            overflow-y: auto;
            flex: 1;
        }

        .modal-footer {
            padding: 1.5rem;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
        }

        /* ====== Forms ====== */
        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-secondary);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            background: var(--bg-primary);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        .form-grid .full {
            grid-column: 1 / -1;
        }

        /* ====== Actions ====== */
        .actions {
            display: flex;
            gap: 0.5rem;
        }

        /* ====== Responsive ====== */
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }

            .nav-item span {
                display: none;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        /* ====== Scrollbar ====== */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--bg-primary);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--bg-tertiary);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--bg-hover);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>🔐 Admin Panel</h2>
        </div>
        <nav class="sidebar-nav">
            <a href="#" class="nav-item active" data-section="dashboard">
                <span>📊</span>
                <span>Dashboard</span>
            </a>
            <a href="#" class="nav-item" data-section="members">
                <span>👥</span>
                <span>Members</span>
            </a>
            <a href="#" class="nav-item" data-section="campaigns">
                <span>📧</span>
                <span>Campaigns</span>
            </a>
            <a href="admin-reporting.php" class="nav-item">
                <span>📈</span>
                <span>Reporting</span>
            </a>
            <a href="#" class="nav-item" data-section="audit_trail">
                <span>📜</span>
                <span>Audit Trail</span>
            </a>
            <a href="#" class="nav-item" data-section="email_logs">
                <span>📨</span>
                <span>Email Logs</span>
            </a>
        </nav>
        <div class="sidebar-footer">
            &copy; 2025 EMS
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="topbar">
            <h1 id="page-title">Admin Dashboard</h1>
            <div class="user-menu">
                <span>Administrator</span>
                <div class="user-avatar">A</div>
            </div>
        </div>

        <div class="content-area">
            <?php if (!empty($flashMessage)): ?>
                <div class="alert <?= $flashType === 'success' ? 'success' : 'error' ?>">
                    <span><?= htmlspecialchars($flashMessage) ?></span>
                </div>
            <?php endif; ?>
            <!-- Dashboard Section -->
        <div id="dashboard" class="section active">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-label">Total Members</div>
                        <div class="stat-value"><?= $totalMembers ?></div>
                </div>
                    <div class="stat-card">
                        <div class="stat-label">Total Campaigns</div>
                        <div class="stat-value"><?= $totalCampaigns ?></div>
                </div>
                    <div class="stat-card">
                        <div class="stat-label">Active Campaigns</div>
                        <div class="stat-value"><?= $activeCampaigns ?></div>
                </div>
                    <div class="stat-card">
                        <div class="stat-label">Emails Sent</div>
                        <div class="stat-value"><?= number_format($totalEmailsSent) ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Failed Deliveries</div>
                        <div class="stat-value"><?= $totalFailed ?></div>
                </div>
            </div>

                <div class="table-container" style="margin-bottom: 2rem;">
                <table>
                    <thead>
                        <tr>
                                <th>Member Name</th>
                                <th>Total Campaigns</th>
                                <th>Active</th>
                            <th>Emails Sent</th>
                                <th>Failed</th>
                                <th>Opened</th>
                                <th>Clicked</th>
                        </tr>
                    </thead>
                        <tbody>
                            <?php if (empty($campaignMetrics)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                        No member metrics available yet.
                                    </td>
                        </tr>
                            <?php else: ?>
                                <?php foreach ($campaignMetrics as $metric): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($metric['member_name'] ?? 'N/A') ?></td>
                                        <td><?= $metric['total_campaigns'] ?? 0 ?></td>
                                        <td><?= $metric['active_campaigns'] ?? 0 ?></td>
                                        <td><?= number_format($metric['total_emails_sent'] ?? 0) ?></td>
                                        <td><span class="badge badge-danger"><?= $metric['failed_emails'] ?? 0 ?></span></td>
                                        <td><span class="badge badge-success"><?= $metric['opened_emails'] ?? 0 ?></span></td>
                                        <td><span class="badge badge-info"><?= $metric['clicked_emails'] ?? 0 ?></span></td>
                        </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                    </tbody>
                </table>
        </div>

                <div class="table-container">
         <table>
    <thead>
        <tr>
                                <th>Campaign Name</th>
                                <th>Member</th>
            <th>Status</th>
            <th>Created</th>
        </tr>
    </thead>
                        <tbody>
                            <?php if (empty($recentCampaigns)): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                        No recent campaigns found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentCampaigns as $campaign): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($campaign['campaign_name']) ?></td>
                                        <td><?= htmlspecialchars($campaign['member_name']) ?></td>
                                        <td>
                                            <span class="badge badge-<?= strtolower($campaign['campaign_status']) == 'active' ? 'success' : (strtolower($campaign['campaign_status']) == 'draft' ? 'muted' : 'warning') ?>">
                                                <?= $campaign['campaign_status'] ?>
                                            </span>
                                        </td>
                                        <td><?= date('Y-m-d H:i', strtotime($campaign['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
    </tbody>
</table>
                </div>
        </div>

            <!-- Members Section -->
            <div id="members" class="section">
            <div class="toolbar">
                    <p>Read-only overview of everyone who has self-registered</p>
            </div>
                <div class="table-container">
            <table>
                <thead>
                    <tr>
                                <th>ID</th>
                        <th>Name</th>
                                <th>Email</th>
                        <th>Status</th>
                                <th>Created</th>
                    </tr>
                </thead>
                        <tbody>
                            <?php if (empty($members)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                        No members have registered yet.
                                    </td>
                    </tr>
                            <?php else: ?>
                                <?php foreach ($members as $member): ?>
                                    <tr>
                                        <td><?= $member['member_id'] ?></td>
                                        <td><?= htmlspecialchars($member['member_name']) ?></td>
                                        <td><?= htmlspecialchars($member['member_email']) ?></td>
                                        <td>
                                            <span class="badge badge-<?= strtolower($member['member_status']) == 'active' ? 'success' : 'muted' ?>">
                                                <?= $member['member_status'] ?>
                                            </span>
                                        </td>
                                        <td><?= date('Y-m-d', strtotime($member['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
            </table>
                </div>
        </div>

            <!-- Campaigns Section -->
            <div id="campaigns" class="section">
            <div class="toolbar">
                    <p>View all campaigns across all members</p>
            </div>
                <div class="table-container">
            <table>
                <thead>
                    <tr>
                                <th>Campaign Name</th>
                                <th>Member</th>
                        <th>Status</th>
                                <th>Scheduled</th>
                                <th>Created</th>
                    </tr>
                </thead>
                        <tbody>
                            <?php if (empty($allCampaigns)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                        No campaigns found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php 
                                $memberNames = [];
                                foreach ($members as $m) {
                                    $memberNames[$m['member_id']] = $m['member_name'];
                                }
                                foreach ($allCampaigns as $campaign): 
                                ?>
                                    <tr>
                                        <td><?= htmlspecialchars($campaign['campaign_name']) ?></td>
                                        <td><?= htmlspecialchars($memberNames[$campaign['member_id']] ?? 'Unknown') ?></td>
                                        <td>
                                            <span class="badge badge-<?= strtolower($campaign['campaign_status']) == 'active' ? 'success' : (strtolower($campaign['campaign_status']) == 'draft' ? 'muted' : 'warning') ?>">
                                                <?= $campaign['campaign_status'] ?>
                                            </span>
                                        </td>
                                        <td><?= $campaign['schedule_time'] ? date('Y-m-d H:i', strtotime($campaign['schedule_time'])) : 'Not scheduled' ?></td>
                                        <td><?= date('Y-m-d H:i', strtotime($campaign['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
            </table>
                </div>
        </div>

            <!-- Audit Trail Section -->
            <div id="audit_trail" class="section">
            <div class="toolbar">
                    <p>View all system actions and changes</p>
            </div>
                <div class="table-container">
            <table>
                <thead>
                    <tr>
                                <th>Time</th>
                                <th>Action</th>
                                <th>Entity</th>
                                <th>User</th>
                                <th>Details</th>
                    </tr>
                </thead>
                        <tbody>
                            <?php if (empty($auditTrail)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                        No audit trail entries found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($auditTrail as $audit): ?>
                                    <tr>
                                        <td><?= date('Y-m-d H:i:s', strtotime($audit['performed_on'])) ?></td>
                                        <td>
                                            <span class="badge badge-info"><?= htmlspecialchars($audit['action_type']) ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($audit['entity_type'] . ' #' . $audit['entity_id']) ?></td>
                                        <td><?= htmlspecialchars($audit['member_name'] ?? $audit['admin_email'] ?? 'System') ?></td>
                                        <td><?= htmlspecialchars(substr($audit['details'] ?? '', 0, 60)) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
            </table>
                </div>
        </div>

            <!-- Email Logs Section -->
        <div id="email_logs" class="section">
            <div class="toolbar">
                    <p>View all email delivery logs</p>
            </div>
                <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Campaign</th>
                                <th>Member</th>
                        <th>Recipient</th>
                        <th>Status</th>
                                <th>Error</th>
                    </tr>
                </thead>
                        <tbody>
                            <?php if (empty($emailLogs)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                        No email logs found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($emailLogs as $log): ?>
                                    <tr>
                                        <td><?= $log['sent_on'] ? date('Y-m-d H:i:s', strtotime($log['sent_on'])) : 'N/A' ?></td>
                                        <td><?= htmlspecialchars($log['campaign_name'] ?? 'Campaign #' . $log['campaign_id']) ?></td>
                                        <td><?= htmlspecialchars($log['member_name'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($log['recipient_email'] ?? 'N/A') ?></td>
                                        <td>
                                            <?php 
                                            $status = strtolower($log['delivery_status'] ?? '');
                                            $badgeClass = $status == 'sent' ? 'success' : ($status == 'failed' ? 'danger' : ($status == 'opened' ? 'info' : ($status == 'clicked' ? 'warning' : 'muted')));
                                            ?>
                                            <span class="badge badge-<?= $badgeClass ?>">
                                                <?= ucfirst($log['delivery_status'] ?? 'Unknown') ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars(substr($log['error_message'] ?? '', 0, 40)) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
            </table>
        </div>
            </div>
        </div>
    </div>

<script>
        // Section Navigation
        document.querySelectorAll('.nav-item[data-section]').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                const section = this.getAttribute('data-section');
                
                // Update active nav
                document.querySelectorAll('.nav-item').forEach(nav => nav.classList.remove('active'));
                this.classList.add('active');
                
                // Update active section
                document.querySelectorAll('.section').forEach(sec => sec.classList.remove('active'));
                document.getElementById(section).classList.add('active');
                
                // Update page title
                const titles = {
                    'dashboard': 'Admin Dashboard',
                    'members': 'Members Overview',
                    'campaigns': 'All Campaigns',
                    'audit_trail': 'Audit Trail',
                    'email_logs': 'Email Logs'
                };
                document.getElementById('page-title').textContent = titles[section] || 'Admin Dashboard';
            });
        });

        // Modal Functions
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }

    function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        // Close modal on backdrop click
        document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
            backdrop.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                }
  });
});
</script>
</body>
</html>
