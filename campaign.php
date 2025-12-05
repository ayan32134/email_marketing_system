<?php
session_start();
require_once 'classes/Member.php';
require_once 'classes/campaign.php';
require_once 'classes/contactGroup.php';
require_once 'classes/template.php';
require_once 'classes/auditTrail.php';
require_once 'classes/BaseModelHelper.php';
require_once 'config/Database.php';

if (!isset($_SESSION['member_id'])) {
    header("Location: member-login.php");
    exit;
}

$member_id = $_SESSION['member_id'];
$db = new dataBase(['host' => 'localhost', 'user' => 'root', 'password' => 'root', 'database' => 'email_marketing_system']);

$campaignModel = new Campaign();
$campaignModel->db = $db->db;

$groupModel = new ContactGroup();
$groupModel->db = $db->db;

$templateModel = new Template();
$templateModel->db = $db->db;

$auditTrail = new AuditTrail();
$auditTrail->db = $db->db;

// Get all campaigns for this member
$campaigns = BaseModelHelper::mysqliGetAll($db->db, 'Campaigns', ['member_id' => $member_id]);

// Get all groups for this member
$groups = BaseModelHelper::mysqliGetAll($db->db, 'ContactGroups', ['member_id' => $member_id]);

// Get all templates for this member's campaigns
$templates = $templateModel->getTemplatesByMember($member_id);

$message = '';
$messageType = '';

if (isset($_GET['message'])) {
    $message = $_GET['message'];
    $messageType = isset($_GET['type']) ? $_GET['type'] : 'success';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campaign Management - Email Marketing System</title>
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

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
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

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover {
            background: #059669;
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
            max-width: 700px;
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
            min-height: 100px;
            resize: vertical;
        }

        .checkbox-group {
            max-height: 250px;
            overflow-y: auto;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 0.75rem;
            background: var(--bg-primary);
        }

        .checkbox-item {
            padding: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-radius: 6px;
            transition: background 0.2s;
        }

        .checkbox-item:hover {
            background: var(--bg-tertiary);
        }

        .checkbox-item input[type="checkbox"] {
            width: auto;
            cursor: pointer;
        }

        .checkbox-item label {
            margin: 0;
            font-weight: normal;
            cursor: pointer;
            flex: 1;
        }

        /* ====== Actions ====== */
        .actions {
            display: flex;
            gap: 0.5rem;
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
            <a href="campaign.php" class="nav-item active">
                <span>📧</span>
                <span>Campaigns</span>
            </a>
            <a href="templates.php" class="nav-item">
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
            <h1>📧 Campaign Management</h1>
            <div class="topbar-actions">
                <a href="member_dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
                <button class="btn btn-primary" onclick="openCreateModal()">+ New Campaign</button>
            </div>
        </div>

        <div class="content-area">
            <?php if ($message): ?>
                <div class="alert <?= $messageType ?>">
                    <span><?= htmlspecialchars($message) ?></span>
                </div>
            <?php endif; ?>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Campaign Name</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Scheduled</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($campaigns)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                    No campaigns found. Create your first campaign!
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($campaigns as $campaign): ?>
                                <tr>
                                    <td><?= htmlspecialchars($campaign['campaign_name']) ?></td>
                                    <td><?= htmlspecialchars($campaign['campaign_description'] ?? '') ?></td>
                                    <td>
                                        <span class="badge badge-<?= strtolower($campaign['campaign_status']) == 'active' ? 'success' : (strtolower($campaign['campaign_status']) == 'draft' ? 'muted' : 'warning') ?>">
                                            <?= ucfirst($campaign['campaign_status']) ?>
                                        </span>
                                    </td>
                                    <td><?= $campaign['schedule_time'] ? date('Y-m-d H:i', strtotime($campaign['schedule_time'])) : 'Not scheduled' ?></td>
                                    <td><?= date('Y-m-d H:i', strtotime($campaign['created_at'])) ?></td>
                                    <td>
                                        <div class="actions">
                                            <button class="btn btn-success btn-sm" onclick="openSendModal(<?= $campaign['campaign_id'] ?>, '<?= htmlspecialchars($campaign['campaign_name'], ENT_QUOTES) ?>')">
                                                Send
                                            </button>
                                            <a href="?edit=<?= $campaign['campaign_id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Create Campaign Modal -->
    <div class="modal-backdrop" id="createModal">
        <div class="modal">
            <div class="modal-header">
                <h3>Create New Campaign</h3>
                <button class="modal-close" onclick="closeModal('createModal')">✕</button>
            </div>
            <form action="process/save_campaign.php" method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Campaign Name *</label>
                        <input type="text" name="campaign_name" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="campaign_description"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Status *</label>
                        <select name="campaign_status" required>
                            <option value="Draft">Draft</option>
                            <option value="Active">Active</option>
                            <option value="Paused">Paused</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Scheduled Date & Time</label>
                        <input type="datetime-local" name="schedule_time">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('createModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Campaign</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Send Campaign Modal -->
    <div class="modal-backdrop" id="sendModal">
        <div class="modal">
            <div class="modal-header">
                <h3 id="sendModalTitle">Send Campaign</h3>
                <button class="modal-close" onclick="closeModal('sendModal')">✕</button>
            </div>
            <form action="process/send_campaign.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="campaign_id" id="sendCampaignId">
                    <div class="form-group">
                        <label>Select Template *</label>
                        <select name="template_id" required>
                            <option value="">Choose a template...</option>
                            <?php foreach ($templates as $template): ?>
                                <option value="<?= $template['template_id'] ?>">
                                    <?= htmlspecialchars($template['template_name'] ?? 'Template #' . $template['template_id']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($templates)): ?>
                            <p style="color: var(--text-muted); font-size: 0.875rem; margin-top: 0.5rem;">
                                No templates available. <a href="templates.php" style="color: var(--accent);">Create a template first</a>.
                            </p>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label>Select Groups *</label>
                        <div class="checkbox-group">
                            <?php if (empty($groups)): ?>
                                <p style="padding: 1rem; color: var(--text-muted); text-align: center;">
                                    No groups available. <a href="member_dashboard.php#groups" style="color: var(--accent);">Create groups first</a>.
                                </p>
                            <?php else: ?>
                                <?php foreach ($groups as $group): ?>
                                    <div class="checkbox-item">
                                        <input type="checkbox" name="group_ids[]" value="<?= $group['group_id'] ?>" id="group_<?= $group['group_id'] ?>">
                                        <label for="group_<?= $group['group_id'] ?>">
                                            <?= htmlspecialchars($group['group_name']) ?>
                                            <?php if ($group['group_description']): ?>
                                                <span style="color: var(--text-muted); font-size: 0.875rem;">
                                                    - <?= htmlspecialchars($group['group_description']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Email Subject *</label>
                        <input type="text" name="email_subject" required placeholder="Enter email subject">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('sendModal')">Cancel</button>
                    <button type="submit" class="btn btn-success">Send Campaign</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openCreateModal() {
            document.getElementById('createModal').classList.add('active');
        }

        function openSendModal(campaignId, campaignName) {
            document.getElementById('sendCampaignId').value = campaignId;
            document.getElementById('sendModalTitle').textContent = 'Send Campaign: ' + campaignName;
            document.getElementById('sendModal').classList.add('active');
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
