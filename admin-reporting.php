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

// Get campaign metrics - Fixed query to handle members without campaigns
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
$totalCampaignsSql = "SELECT COUNT(*) as count FROM Campaigns";
$totalCampaignsResult = $db->db->query($totalCampaignsSql);
$totalCampaigns = $totalCampaignsResult ? (int)$totalCampaignsResult->fetch_assoc()['count'] : 0;

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Reporting - Email Marketing System</title>
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

        .btn-secondary {
            background: var(--bg-tertiary);
            color: var(--text-primary);
            border: 1px solid var(--border);
        }

        .btn-secondary:hover {
            background: var(--bg-hover);
        }

        /* ====== Tables ====== */
        .table-container {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 2rem;
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

        h2 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text-primary);
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
            <a href="admin-dashboard.php" class="nav-item">
                <span>📊</span>
                <span>Dashboard</span>
            </a>
            <a href="admin-dashboard.php#members" class="nav-item">
                <span>👥</span>
                <span>Members</span>
            </a>
            <a href="admin-dashboard.php#campaigns" class="nav-item">
                <span>📧</span>
                <span>Campaigns</span>
            </a>
            <a href="admin-reporting.php" class="nav-item active">
                <span>📈</span>
                <span>Reporting</span>
            </a>
            <a href="admin-dashboard.php#audit_trail" class="nav-item">
                <span>📜</span>
                <span>Audit Trail</span>
            </a>
            <a href="admin-dashboard.php#email_logs" class="nav-item">
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
            <h1>📈 Reporting Dashboard</h1>
            <div class="user-menu">
                <a href="admin-dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
                <span>Administrator</span>
                <div class="user-avatar">A</div>
            </div>
        </div>

        <div class="content-area">
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
                    <div class="stat-label">Total Emails Sent</div>
                    <div class="stat-value"><?= number_format($totalEmailsSent) ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Failed Deliveries</div>
                    <div class="stat-value"><?= number_format($totalFailed) ?></div>
                </div>
            </div>

            <h2>Campaign Metrics by Member</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Total Campaigns</th>
                            <th>Active Campaigns</th>
                            <th>Emails Sent</th>
                            <th>Opened</th>
                            <th>Clicked</th>
                            <th>Failed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($campaignMetrics)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                    No campaign data available.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($campaignMetrics as $metric): ?>
                                <tr>
                                    <td><?= htmlspecialchars($metric['member_name'] ?? 'N/A') ?></td>
                                    <td><?= $metric['total_campaigns'] ?? 0 ?></td>
                                    <td><?= $metric['active_campaigns'] ?? 0 ?></td>
                                    <td><?= number_format($metric['total_emails_sent'] ?? 0) ?></td>
                                    <td><span class="badge badge-success"><?= number_format($metric['opened_emails'] ?? 0) ?></span></td>
                                    <td><span class="badge badge-info"><?= number_format($metric['clicked_emails'] ?? 0) ?></span></td>
                                    <td><span class="badge badge-danger"><?= number_format($metric['failed_emails'] ?? 0) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <h2>Recent Campaigns</h2>
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
                                    No campaigns found.
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
    </div>
</body>
</html>
