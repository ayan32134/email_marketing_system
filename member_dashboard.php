<?php
session_start();
require_once 'classes/Member.php';
require_once 'classes/template.php';
require_once 'classes/emailLog.php';
require_once 'classes/BaseModelHelper.php';
require_once 'config/Database.php';

if (!isset($_SESSION['member_id'])) {
    header("Location: login.php");
    exit;
}

$member_id = $_SESSION['member_id'];
$db = new dataBase(['host' => 'localhost', 'user' => 'root', 'password' => 'root', 'database' => 'email_marketing_system']);

$member = new Member();
$member->db = $db->db;

$message = '';
$messageType = '';

if (isset($_GET['message'])) {
    $message = $_GET['message'];
    $messageType = $_GET['type'] ?? 'success';
}

// Handle add contact
// Contact adding is handled in process/add-contact.php

// Fetch all contacts for this member
$contacts_data = BaseModelHelper::mysqliGetAll($db->db, 'Contacts', ['member_id' => $member_id]);

// Fetch all contact groups for this member
$sql = $member->buildSQL('ContactGroups', [], 'select', ['member_id' => $member_id]);
$member_groups_result = $member->db->query($sql);

$groups = [];
if ($member_groups_result && $member_groups_result->num_rows > 0) {
    $groups = $member_groups_result->fetch_all(MYSQLI_ASSOC);
    
    // Get contact count for each group
    foreach ($groups as &$group) {
        $groupMembers = BaseModelHelper::mysqliGetAll($db->db, 'Group_Members', ['group_id' => $group['group_id']]);
        $group['contact_count'] = count($groupMembers);
    }
    unset($group);
}

// Get groups for each contact (for display)
$contactGroupsMap = [];
foreach ($contacts_data as $contact) {
    $contactGroups = [];
    $groupMembers = BaseModelHelper::mysqliGetAll($db->db, 'Group_Members', ['contact_id' => $contact['contact_id']]);
    foreach ($groupMembers as $gm) {
        $group = BaseModelHelper::mysqliFind($db->db, 'ContactGroups', 'group_id', $gm['group_id']);
        if ($group && $group['member_id'] == $member_id) {
            $contactGroups[] = [
                'group_id' => $group['group_id'],
                'group_name' => $group['group_name']
            ];
        }
    }
    $contactGroupsMap[$contact['contact_id']] = $contactGroups;
}

// Fetch all campaigns for this member
$campaigns = BaseModelHelper::mysqliGetAll($db->db, 'Campaigns', ['member_id' => $member_id]);

// Fetch all templates for this member - templates are linked via campaigns
$templateModel = new Template();
$templateModel->db = $db->db;
$templates = $templateModel->getTemplatesByMember($member_id);

// Fetch email logs for this member's campaigns
$email_logs = [];
$campaign_ids = array_column($campaigns, 'campaign_id');
if (!empty($campaign_ids)) {
    $campaign_ids_str = implode(',', array_map('intval', $campaign_ids));
    $log_sql = "SELECT el.*, c.campaign_name, ct.email as recipient_email 
                FROM Email_Log el 
                LEFT JOIN Campaigns c ON el.campaign_id = c.campaign_id 
                LEFT JOIN Contacts ct ON el.contact_id = ct.contact_id 
                WHERE el.campaign_id IN ($campaign_ids_str) 
                ORDER BY el.sent_on DESC 
                LIMIT 100";
    $log_result = $db->db->query($log_sql);
    if ($log_result && $log_result->num_rows > 0) {
        $email_logs = $log_result->fetch_all(MYSQLI_ASSOC);
    }
} else {
    $log_sql = "SELECT el.*, c.campaign_name, ct.email as recipient_email 
                FROM Email_Log el 
                LEFT JOIN Campaigns c ON el.campaign_id = c.campaign_id 
                LEFT JOIN Contacts ct ON el.contact_id = ct.contact_id 
                WHERE ct.member_id = " . (int)$member_id . "
                ORDER BY el.sent_on DESC 
                LIMIT 100";
    $log_result = $db->db->query($log_sql);
    if ($log_result && $log_result->num_rows > 0) {
        $email_logs = $log_result->fetch_all(MYSQLI_ASSOC);
    }
}

// Get statistics for dashboard
$total_contacts = count($contacts_data);
$total_campaigns = count($campaigns);
$active_campaigns = count(array_filter($campaigns, function($c) { return $c['campaign_status'] == 'Active'; }));
$total_emails_sent = count(array_filter($email_logs, function($e) { return ($e['delivery_status'] ?? $e['status'] ?? '') == 'Sent'; }));
$failed_deliveries = count(array_filter($email_logs, function($e) { return ($e['delivery_status'] ?? $e['status'] ?? '') == 'Failed'; }));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Dashboard - Email Marketing System</title>
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

        /* ====== Message Alert ====== */
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

        .stat-change {
            font-size: 0.75rem;
            margin-top: 0.5rem;
            color: var(--success);
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

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
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
            <h2>📧 EMS</h2>
        </div>
        <nav class="sidebar-nav">
            <a href="#" class="nav-item active" data-section="dashboard">
                <span>📊</span>
                <span>Dashboard</span>
            </a>
            <a href="campaign.php" class="nav-item">
                <span>📧</span>
                <span>Campaigns</span>
            </a>
            <a href="#" class="nav-item" data-section="templates">
                <span>📝</span>
                <span>Templates</span>
            </a>
            <a href="#" class="nav-item" data-section="contacts">
                <span>👥</span>
                <span>Contacts</span>
            </a>
            <a href="#" class="nav-item" data-section="groups">
                <span>📂</span>
                <span>Groups</span>
            </a>
            <a href="#" class="nav-item" data-section="email_logs">
                <span>📜</span>
                <span>Email Logs</span>
            </a>
            <a href="#" class="nav-item" data-section="settings">
                <span>⚙️</span>
                <span>Settings</span>
            </a>
        </nav>
        <div class="sidebar-footer">
            &copy; 2025 EMS
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="topbar">
            <h1 id="page-title">Dashboard Overview</h1>
            <div class="user-menu">
                <span>Member</span>
                <div class="user-avatar">M</div>
            </div>
        </div>

        <div class="content-area">
            <?php if ($message): ?>
                <div class="alert <?= $messageType ?>">
                    <span><?= htmlspecialchars($message) ?></span>
                </div>
            <?php endif; ?>

            <!-- Dashboard Section -->
        <div id="dashboard" class="section active">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-label">Total Contacts</div>
                        <div class="stat-value"><?= $total_contacts ?></div>
                </div>
                    <div class="stat-card">
                        <div class="stat-label">Active Campaigns</div>
                        <div class="stat-value"><?= $active_campaigns ?></div>
                </div>
                    <div class="stat-card">
                        <div class="stat-label">Emails Sent</div>
                        <div class="stat-value"><?= number_format($total_emails_sent) ?></div>
                </div>
                    <div class="stat-card">
                        <div class="stat-label">Failed Deliveries</div>
                        <div class="stat-value"><?= $failed_deliveries ?></div>
                </div>
            </div>

                <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Campaign Name</th>
                            <th>Status</th>
                            <th>Emails Sent</th>
                                <th>Created</th>
                        </tr>
                    </thead>
                        <tbody>
                            <?php if (empty($campaigns)): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                        No campaigns found. <a href="campaign.php" style="color: var(--accent);">Create your first campaign!</a>
                                    </td>
                        </tr>
                            <?php else: ?>
                                <?php 
                                $recent_campaigns = array_slice($campaigns, 0, 5);
                                foreach ($recent_campaigns as $campaign): 
                                    $campaign_emails = array_filter($email_logs, function($e) use ($campaign) { 
                                        return $e['campaign_id'] == $campaign['campaign_id'] && ($e['delivery_status'] ?? '') == 'Sent'; 
                                    });
                                    $emails_sent_count = count($campaign_emails);
                                ?>
                                    <tr>
                                        <td><?= htmlspecialchars($campaign['campaign_name']) ?></td>
                                        <td>
                                            <span class="badge badge-<?= strtolower($campaign['campaign_status']) == 'active' ? 'success' : (strtolower($campaign['campaign_status']) == 'draft' ? 'muted' : 'warning') ?>">
                                                <?= $campaign['campaign_status'] ?>
                                            </span>
                                        </td>
                                        <td><?= $emails_sent_count ?></td>
                                        <td><?= date('Y-m-d', strtotime($campaign['created_at'])) ?></td>
                        </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

            <!-- Templates Section -->
            <div id="templates" class="section">
            <div class="toolbar">
                    <p>Manage your email templates</p>
                    <a href="templates.php" class="btn btn-primary">+ New Template</a>
            </div>
                <div class="table-container">
            <table>
                <thead>
                    <tr>
                                <th>Template Name</th>
                                <th>Subject</th>
                        <th>Status</th>
                                <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                        <tbody>
                            <?php if (empty($templates)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                        No templates found. <a href="templates.php" style="color: var(--accent);">Create your first template!</a>
                                    </td>
                    </tr>
                            <?php else: ?>
                                <?php foreach ($templates as $template): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($template['template_name'] ?? 'Template #' . $template['template_id']) ?></td>
                                        <td><?= htmlspecialchars($template['template_subject'] ?? 'N/A') ?></td>
                                        <td>
                                            <span class="badge badge-<?= strtolower($template['template_status'] ?? 'Active') == 'active' ? 'success' : 'muted' ?>">
                                                <?= $template['template_status'] ?? 'Active' ?>
                                            </span>
                                        </td>
                                        <td><?= $template['created_at'] ? date('Y-m-d H:i', strtotime($template['created_at'])) : 'N/A' ?></td>
                                        <td>
                                            <div class="actions">
                                                <a href="templates.php?edit=<?= $template['template_id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
            </table>
                </div>
        </div>

            <!-- Contacts Section -->
        <div id="contacts" class="section">
            <div class="toolbar">
                    <p>Manage your contacts</p>
                    <button class="btn btn-primary" onclick="openModal('modal-contact')">+ Add Contact</button>
            </div>
                <div class="table-container">
<table>
                        <thead>
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Email</th>
        <th>Groups</th>
        <th>Status</th>
                                <th>Created</th>
        <th>Actions</th>
    </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($contacts_data)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                        No contacts found. Add your first contact!
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($contacts_data as $contact): ?>
    <tr>
        <td><?= $contact['contact_id'] ?></td>
                                        <td><?= htmlspecialchars(trim($contact['honorifics'] . ' ' . $contact['first_name'] . ' ' . $contact['middle_name'] . ' ' . $contact['last_name'])) ?></td>
        <td><?= htmlspecialchars($contact['email']) ?></td>
                                        <td>
                                            <?php 
                                            $contactGroups = $contactGroupsMap[$contact['contact_id']] ?? [];
                                            if (empty($contactGroups)): 
                                            ?>
                                                <span style="color: var(--text-muted); font-size: 0.875rem;">Solo</span>
                                            <?php else: ?>
                                                <?php foreach ($contactGroups as $groupInfo): ?>
                                                    <span class="badge badge-info" style="margin-right: 0.25rem; display: inline-block; margin-bottom: 0.25rem;">
                                                        <?= htmlspecialchars($groupInfo['group_name']) ?>
                                                        <a href="process/remove-contact-from-group.php?contact_id=<?= $contact['contact_id'] ?>&group_id=<?= $groupInfo['group_id'] ?>" 
                                                           onclick="return confirm('Remove from this group?')" 
                                                           style="color: white; margin-left: 0.5rem; text-decoration: none;">×</a>
                                                    </span>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?= strtolower($contact['contact_status']) == 'active' ? 'success' : 'muted' ?>">
                                                <?= $contact['contact_status'] ?>
                                            </span>
                                        </td>
                                        <td><?= date('Y-m-d', strtotime($contact['created_at'])) ?></td>
                                        <td>
                                            <div class="actions">
                                                <button onclick="openAssignModal(<?= $contact['contact_id'] ?>)" class="btn btn-info btn-sm" style="background: var(--accent);">Assign</button>
                                                <a href="process/edit-contact.php?id=<?= $contact['contact_id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                                                <a href="process/delete-contact.php?id=<?= $contact['contact_id'] ?>" 
                                                   onclick="return confirm('Are you sure?')" 
                                                   class="btn btn-danger btn-sm">Delete</a>
                                            </div>
        </td>
    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
</table>
                </div>
        </div>

            <!-- Groups Section -->
        <div id="groups" class="section">
            <div class="toolbar">
                    <p>Organize contacts into groups</p>
                    <button class="btn btn-primary" onclick="openModal('modal-group')">+ New Group</button>
            </div>
                <div class="table-container">
               <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Group Name</th>
                <th>Description</th>
                <th>Contacts</th>
                <th>Limit</th>
                                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
                            <?php if (empty($groups)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                        No groups found. Create your first group!
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($groups as $group): ?>
                    <tr>
                        <td><?= $group['group_id'] ?></td>
                        <td><?= htmlspecialchars($group['group_name']) ?></td>
                                        <td><?= htmlspecialchars($group['group_description'] ?? '') ?></td>
                                        <td>
                                            <span class="badge badge-<?= ($group['max_contacts'] ?? 0) > 0 && ($group['contact_count'] ?? 0) >= ($group['max_contacts'] ?? 0) ? 'danger' : 'info' ?>">
                                                <?= $group['contact_count'] ?? 0 ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (($group['max_contacts'] ?? 0) > 0): ?>
                                                <?= $group['max_contacts'] ?>
                                            <?php else: ?>
                                                <span style="color: var(--text-muted);">Unlimited</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= date('Y-m-d', strtotime($group['created_at'])) ?></td>
                                        <td>
                                            <div class="actions">
                                                <button onclick="openManageGroupModal(<?= $group['group_id'] ?>, '<?= htmlspecialchars($group['group_name'], ENT_QUOTES) ?>')" class="btn btn-info btn-sm" style="background: var(--accent);">Manage</button>
                                                <a href="process/edit-group.php?id=<?= $group['group_id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                                                <a href="process/delete-group.php?id=<?= $group['group_id'] ?>" 
                                                   onclick="return confirm('Are you sure?')" 
                                                   class="btn btn-danger btn-sm">Delete</a>
                                            </div>
                        </td>
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
                    <p>View email delivery history</p>
            </div>
                <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Campaign</th>
                        <th>Recipient</th>
                        <th>Status</th>
                        <th>Message</th>
                    </tr>
                </thead>
                        <tbody>
                            <?php if (empty($email_logs)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                        No email logs found. Send a campaign to see logs here.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($email_logs as $log): ?>
                                    <tr>
                                        <td><?= $log['sent_on'] ? date('Y-m-d H:i:s', strtotime($log['sent_on'])) : 'N/A' ?></td>
                                        <td><?= htmlspecialchars($log['campaign_name'] ?? 'Campaign #' . $log['campaign_id']) ?></td>
                                        <td><?= htmlspecialchars($log['recipient_email'] ?? 'N/A') ?></td>
                                        <td>
                                            <?php 
                                            $status = strtolower($log['delivery_status'] ?? $log['status'] ?? '');
                                            $badgeClass = $status == 'sent' ? 'success' : ($status == 'failed' ? 'danger' : ($status == 'opened' ? 'info' : ($status == 'clicked' ? 'warning' : 'muted')));
                                            ?>
                                            <span class="badge badge-<?= $badgeClass ?>">
                                                <?= ucfirst($log['delivery_status'] ?? $log['status'] ?? 'Unknown') ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars(substr($log['error_message'] ?? $log['message'] ?? '', 0, 50)) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
            </table>
                </div>
        </div>

            <!-- Settings Section -->
        <div id="settings" class="section">
            <div class="toolbar">
                    <p>Configure your account settings</p>
            </div>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-label">SMTP Settings</div>
                        <p style="color: var(--text-secondary); margin: 0.5rem 0;">Configure mail transport</p>
                        <a href="smtp-settings.php" class="btn btn-primary btn-sm">Configure</a>
                </div>
                    <div class="stat-card">
                        <div class="stat-label">Email Queue Settings</div>
                        <p style="color: var(--text-secondary); margin: 0.5rem 0;">Set sending limits</p>
                        <a href="queue-settings.php" class="btn btn-primary btn-sm">Configure</a>
                </div>
            </div>
        </div>
    </div>
                        </div>

    <!-- Contact Modal -->
    <div class="modal-backdrop" id="modal-contact">
        <div class="modal">
            <div class="modal-header">
                <h3>Add Contact</h3>
                <button class="modal-close" onclick="closeModal('modal-contact')">✕</button>
                        </div>
            <form method="POST" action="member_dashboard.php">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Honorifics</label>
                        <select name="honorifics">
                            <option value="">None</option>
                            <option value="Mr.">Mr.</option>
                            <option value="Mrs.">Mrs.</option>
                            <option value="Ms.">Ms.</option>
                            <option value="Dr.">Dr.</option>
                            </select>
                        </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>First Name *</label>
                            <input type="text" name="first_name" required>
                        </div>
                        <div class="form-group">
                            <label>Middle Name</label>
                            <input type="text" name="middle_name">
                        </div>
                        </div>
                    <div class="form-group">
                        <label>Last Name *</label>
                        <input type="text" name="last_name" required>
                        </div>
                    <div class="form-group">
                        <label>Email *</label>
                            <input type="email" name="email" required>
                        </div>
                    <div class="form-group">
                            <label>Status</label>
                        <select name="contact_status">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('modal-contact')">Cancel</button>
                    <button type="submit" name="add_contact" class="btn btn-primary">Add Contact</button>
                </div>
                </form>
        </div>
    </div>

    <!-- Group Modal -->
    <div class="modal-backdrop" id="modal-group">
        <div class="modal">
            <div class="modal-header">
                <h3>New Group</h3>
                <button class="modal-close" onclick="closeModal('modal-group')">✕</button>
                        </div>
            <form method="POST" action="process/add-group.php">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Group Name *</label>
                        <input type="text" name="group_name" required>
                        </div>
                    <div class="form-group">
                            <label>Description</label>
                        <textarea name="group_description"></textarea>
                        </div>
                    <div class="form-group">
                        <label>Max Contacts (0 = Unlimited)</label>
                        <input type="number" name="max_contacts" value="0" min="0" placeholder="0 for unlimited">
                        <small style="color: var(--text-muted); font-size: 0.75rem; margin-top: 0.25rem; display: block;">Set 0 for unlimited contacts in this group</small>
                    </div>
                    </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('modal-group')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Group</button>
                    </div>
                </form>
        </div>
    </div>

    <!-- Assign Contact to Group Modal -->
    <div class="modal-backdrop" id="modal-assign-contact">
        <div class="modal">
            <div class="modal-header">
                <h3 id="assignContactTitle">Assign Contact to Group</h3>
                <button class="modal-close" onclick="closeModal('modal-assign-contact')">✕</button>
            </div>
            <form method="POST" action="process/assign-contact-to-group.php">
                <div class="modal-body">
                    <input type="hidden" name="contact_id" id="assignContactId">
                    <div class="form-group">
                        <label>Select Group *</label>
                        <select name="group_id" required>
                            <option value="">Choose a group...</option>
                            <?php foreach ($groups as $group): ?>
                                <option value="<?= $group['group_id'] ?>">
                                    <?= htmlspecialchars($group['group_name']) ?>
                                    <?php if (($group['max_contacts'] ?? 0) > 0): ?>
                                        (<?= $group['contact_count'] ?? 0 ?>/<?= $group['max_contacts'] ?>)
                                    <?php else: ?>
                                        (<?= $group['contact_count'] ?? 0 ?> contacts)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($groups)): ?>
                            <p style="color: var(--text-muted); font-size: 0.875rem; margin-top: 0.5rem;">
                                No groups available. <a href="#" onclick="closeModal('modal-assign-contact'); openModal('modal-group'); return false;" style="color: var(--accent);">Create a group first</a>.
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('modal-assign-contact')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Assign to Group</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Manage Group Contacts Modal -->
    <div class="modal-backdrop" id="modal-manage-group">
        <div class="modal" style="max-width: 800px;">
            <div class="modal-header">
                <h3 id="manageGroupTitle">Manage Group Contacts</h3>
                <button class="modal-close" onclick="closeModal('modal-manage-group')">✕</button>
            </div>
            <div class="modal-body">
                <div id="manageGroupContent">
                    <p style="text-align: center; color: var(--text-muted);">Loading...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modal-manage-group')">Close</button>
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
                    'dashboard': 'Dashboard Overview',
                    'templates': 'Templates',
                    'contacts': 'Contacts',
                    'groups': 'Groups',
                    'email_logs': 'Email Logs',
                    'settings': 'Settings'
                };
                document.getElementById('page-title').textContent = titles[section] || 'Dashboard';
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

        // Assign Contact Modal
        function openAssignModal(contactId) {
            document.getElementById('assignContactId').value = contactId;
            document.getElementById('modal-assign-contact').classList.add('active');
        }

        // Manage Group Contacts Modal
        function openManageGroupModal(groupId, groupName) {
            document.getElementById('manageGroupTitle').textContent = 'Manage Contacts: ' + groupName;
            document.getElementById('manageGroupContent').innerHTML = '<p style="text-align: center; color: var(--text-muted);">Loading...</p>';
            document.getElementById('modal-manage-group').classList.add('active');
            
            // Load group contacts via AJAX
            loadGroupContacts(groupId);
        }

        function loadGroupContacts(groupId) {
            fetch('manage-group-contacts.php?group_id=' + groupId)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('manageGroupContent').innerHTML = html;
                    // Re-attach event handlers for forms
                    attachFormHandlers();
                    // Make removeContactFromGroup available globally for the loaded content
                    window.removeContactFromGroup = function(contactId, groupId) {
                        fetch('process/remove-contact-from-group.php?contact_id=' + contactId + '&group_id=' + groupId, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    loadGroupContacts(groupId);
                                } else {
                                    alert(data.message || 'Error removing contact from group.');
                                }
                            })
                            .catch(error => {
                                alert('Error removing contact from group.');
                            });
                    };
                })
                .catch(error => {
                    document.getElementById('manageGroupContent').innerHTML = '<p style="color: var(--danger);">Error loading contacts.</p>';
                });
        }

        function attachFormHandlers() {
            // Handle add contact forms
            document.querySelectorAll('#manageGroupContent form[action*="assign-contact-to-group"]').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    const groupId = formData.get('group_id');
                    fetch(this.action, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            loadGroupContacts(groupId);
                        } else {
                            alert(data.message || 'Error adding contact to group.');
                        }
                    })
                    .catch(error => {
                        alert('Error adding contact to group.');
                    });
                });
            });
        }

        function handleAddContact(event, form) {
            event.preventDefault();
            const formData = new FormData(form);
            const groupId = formData.get('group_id');
            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadGroupContacts(groupId);
                } else {
                    alert(data.message || 'Error adding contact to group.');
                }
            })
            .catch(error => {
                alert('Error adding contact to group.');
            });
            return false;
        }
    </script>
</body>
</html>

