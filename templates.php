<?php
session_start();
require_once 'classes/Member.php';
require_once 'classes/template.php';
require_once 'classes/campaign.php';
require_once 'classes/auditTrail.php';
require_once 'classes/BaseModelHelper.php';
require_once 'config/Database.php';

if (!isset($_SESSION['member_id'])) {
    header("Location: member-login.php");
    exit;
}

$member_id = $_SESSION['member_id'];
$db = new dataBase(['host' => 'localhost', 'user' => 'root', 'password' => 'root', 'database' => 'email_marketing_system']);

// Get all templates for this member's campaigns
$templateModel = new Template();
$templateModel->db = $db->db;
$templates = $templateModel->getTemplatesByMember($member_id);

// Get template for editing
$edit_template = null;
$edit_id = (int)($_GET['edit'] ?? 0);
if ($edit_id) {
    $edit_template = BaseModelHelper::mysqliFind($db->db, 'Templates', 'template_id', $edit_id);
    if ($edit_template) {
        // Verify template's campaign belongs to member
        $campaign = BaseModelHelper::mysqliFind($db->db, 'Campaigns', 'campaign_id', $edit_template['campaign_id']);
        if (!$campaign || $campaign['member_id'] != $member_id) {
            $edit_template = null; // Access denied
        }
    }
}

$message = '';
$messageType = '';
if (isset($_GET['message'])) {
    $message = $_GET['message'];
    $messageType = isset($_GET['type']) ? $_GET['type'] : 'success';
}

// Get campaigns for dropdown
$campaigns = BaseModelHelper::mysqliGetAll($db->db, 'Campaigns', ['member_id' => $member_id]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Template Management - Email Marketing System</title>
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

        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.8125rem;
        }

        /* ====== Forms ====== */
        .form-container {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .form-container h2 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }

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
            min-height: 200px;
            resize: vertical;
            font-family: 'Courier New', monospace;
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
            <a href="member_dashboard.php" class="nav-item">
                <span>📊</span>
                <span>Dashboard</span>
            </a>
            <a href="campaign.php" class="nav-item">
                <span>📧</span>
                <span>Campaigns</span>
            </a>
            <a href="templates.php" class="nav-item active">
                <span>📝</span>
                <span>Templates</span>
            </a>
            <a href="member_dashboard.php#contacts" class="nav-item">
                <span>👥</span>
                <span>Contacts</span>
            </a>
            <a href="member_dashboard.php#groups" class="nav-item">
                <span>📂</span>
                <span>Groups</span>
            </a>
            <a href="member_dashboard.php#email_logs" class="nav-item">
                <span>📜</span>
                <span>Email Logs</span>
            </a>
            <a href="member_dashboard.php#settings" class="nav-item">
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
            <h1>📝 Template Management</h1>
            <a href="member_dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
        </div>

        <div class="content-area">
            <?php if ($message): ?>
                <div class="alert <?= $messageType ?>">
                    <span><?= htmlspecialchars($message) ?></span>
                </div>
            <?php endif; ?>

            <div class="form-container">
                <h2><?= $edit_template ? 'Edit Template' : 'Create New Template' ?></h2>
                <form action="process/<?= $edit_template ? 'edit' : 'add' ?>-template.php<?= $edit_template ? '?id=' . $edit_id : '' ?>" method="POST" enctype="multipart/form-data">
                    <?php if (!$edit_template): ?>
                        <div class="form-group">
                            <label>Campaign *</label>
                            <select name="campaign_id" required>
                                <option value="">Select Campaign</option>
                                <?php foreach ($campaigns as $camp): ?>
                                    <option value="<?= $camp['campaign_id'] ?>"><?= htmlspecialchars($camp['campaign_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php else: ?>
                        <input type="hidden" name="campaign_id" value="<?= $edit_template['campaign_id'] ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label>Template Name *</label>
                        <input type="text" name="template_name" value="<?= htmlspecialchars($edit_template['template_name'] ?? '') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Email Subject</label>
                        <input type="text" name="template_subject" value="<?= htmlspecialchars($edit_template['template_subject'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Template Content (HTML) *</label>
                        <textarea name="template_content" required><?= htmlspecialchars($edit_template['template_content'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Status</label>
                        <select name="template_status">
                            <option value="Active" <?= ($edit_template['template_status'] ?? 'Active') == 'Active' ? 'selected' : '' ?>>Active</option>
                            <option value="Inactive" <?= ($edit_template['template_status'] ?? '') == 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Attachment (Optional)</label>
                        <input type="file" name="attachment" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.zip,.txt">
                        <small style="color: var(--text-muted); font-size: 0.75rem; margin-top: 0.25rem; display: block;">
                            Allowed: PDF, DOC, DOCX, Images, ZIP, TXT (Max 10MB)
                        </small>
                        <?php if ($edit_template && !empty($edit_template['attachment_path'])): ?>
                            <div style="margin-top: 0.5rem;">
                                <span style="color: var(--text-muted); font-size: 0.875rem;">Current: </span>
                                <a href="<?= htmlspecialchars($edit_template['attachment_path']) ?>" target="_blank" style="color: var(--accent);">
                                    <?= htmlspecialchars(basename($edit_template['attachment_path'])) ?>
                                </a>
                                <label style="margin-left: 1rem; color: var(--text-muted); font-size: 0.75rem;">
                                    <input type="checkbox" name="remove_attachment" value="1"> Remove attachment
                                </label>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div style="display: flex; gap: 0.75rem;">
                        <button type="submit" class="btn btn-primary"><?= $edit_template ? 'Update Template' : 'Create Template' ?></button>
                        <?php if ($edit_template): ?>
                            <a href="templates.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <h2>All Templates</h2>
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
                                    No templates found. Create your first template!
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
                                        <a href="?edit=<?= $template['template_id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                                    </td>
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
