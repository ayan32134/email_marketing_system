<?php
session_start();
require_once 'classes/Member.php';
require_once 'classes/smtpSetting.php';
require_once 'classes/auditTrail.php';
require_once 'classes/BaseModelHelper.php';
require_once 'config/Database.php';

if (!isset($_SESSION['member_id'])) {
    header("Location: member-login.php");
    exit;
}

$member_id = $_SESSION['member_id'];
$db = new dataBase(['host' => 'localhost', 'user' => 'root', 'password' => 'root', 'database' => 'email_marketing_system']);

$smtpSetting = new SMTPSetting();
$smtpSetting->db = $db->db;

$auditTrail = new AuditTrail();
$auditTrail->db = $db->db;

// Get existing SMTP settings
$existingSettings = $smtpSetting->getSMTPByMember($member_id);

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $smtp_host = trim($_POST['smtp_host'] ?? '');
    $smtp_port = (int)($_POST['smtp_port'] ?? 587);
    $smtp_user = trim($_POST['smtp_user'] ?? '');
    $smtp_password = trim($_POST['smtp_password'] ?? '');
    $encryption = trim($_POST['encryption'] ?? 'TLS');
    $default_from_email = trim($_POST['default_from_email'] ?? '');
    $default_from_name = trim($_POST['default_from_name'] ?? '');

    // Normalise encryption to one of: TLS, SSL, None
    $encryption = strtoupper($encryption);
    if (!in_array($encryption, ['TLS', 'SSL', 'NONE'], true)) {
        $encryption = 'TLS';
    }

    // For existing settings, allow keeping current password by leaving the field blank
    $isUpdate = (bool)$existingSettings;

    if (!$smtp_host || !$smtp_port || !$smtp_user || (!$smtp_password && !$isUpdate)) {
        $message = "Please fill all required SMTP fields.";
        $messageType = 'error';
    } else {
        $data = [
            'member_id' => $member_id,
            'smtp_host' => $smtp_host,
            'smtp_port' => $smtp_port,
            'smtp_user' => $smtp_user,
            'encryption' => $encryption,
            'default_from_email' => $default_from_email ?: $smtp_user,
            'default_from_name' => $default_from_name,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Only set / overwrite the password if a new one was provided
        if ($smtp_password !== '') {
            $data['smtp_password'] = $smtp_password;
        }
        
        if ($existingSettings) {
            // Update existing
            $smtp_row = BaseModelHelper::mysqliFind($db->db, 'Member_SMTP_Settings', 'member_id', $member_id);
            if ($smtp_row && !empty($smtp_row['smtp_id'])) {
                $updateResult = BaseModelHelper::mysqliUpdate($db->db, 'Member_SMTP_Settings', 'smtp_id', (int)$smtp_row['smtp_id'], $data);
                if ($updateResult) {
                    $message = "SMTP settings updated successfully!";
                    $messageType = 'success';
                } else {
                    $message = "Error updating SMTP settings: " . $db->db->error;
                    $messageType = 'error';
                }
            } else {
                $message = "SMTP settings record not found for this member.";
                $messageType = 'error';
            }
        } else {
            // Create new
            $data['created_at'] = date('Y-m-d H:i:s');
            $smtp_id = BaseModelHelper::mysqliCreate($db->db, 'Member_SMTP_Settings', $data);
            if ($smtp_id) {
                $message = "SMTP settings saved successfully!";
                $messageType = 'success';
            } else {
                $message = "Error saving SMTP settings: " . $db->db->error;
                $messageType = 'error';
            }
        }
        
        if ($messageType == 'success') {
            // Log audit trail
            BaseModelHelper::mysqliCreate($db->db, 'Audit_Trail', [
                'member_id' => $member_id,
                'action_type' => 'UPDATE_SMTP_SETTINGS',
                'entity_type' => 'Member_SMTP_Settings',
                'entity_id' => $member_id,
                'details' => "Updated SMTP settings",
                'performed_on' => date('Y-m-d H:i:s')
            ]);
        }
        
        // Refresh settings
        $existingSettings = $smtpSetting->getSMTPByMember($member_id);
    }
}

// Map settings for display
// Normalise encryption back to UI values: TLS / SSL / None
$displaySettings = $existingSettings ? [
    'smtp_host' => $existingSettings['host'],
    'smtp_port' => $existingSettings['port'],
    'smtp_user' => $existingSettings['username'],
    'smtp_password' => '••••••••',
    'encryption' => strtoupper($existingSettings['encryption'] ?? 'TLS'),
    'default_from_email' => $existingSettings['from_email'],
    'default_from_name' => $existingSettings['from_name']
] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMTP Settings - Email Marketing System</title>
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

        /* ====== Forms ====== */
        .form-container {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 2rem;
            max-width: 800px;
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
        .form-group select {
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
        .form-group select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-group small {
            display: block;
            margin-top: 0.5rem;
            color: var(--text-muted);
            font-size: 0.8125rem;
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
            <a href="member_dashboard.php#settings" class="nav-item active">
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
            <h1>⚙️ SMTP Settings</h1>
            <a href="member_dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
        </div>

        <div class="content-area">
            <?php if ($message): ?>
                <div class="alert <?= $messageType ?>">
                    <span><?= htmlspecialchars($message) ?></span>
                </div>
            <?php endif; ?>

            <div class="form-container">
                <form method="POST">
                    <div class="form-group">
                        <label>SMTP Host *</label>
                        <input type="text" name="smtp_host" value="<?= htmlspecialchars($displaySettings['smtp_host'] ?? '') ?>" required placeholder="smtp.gmail.com">
                    </div>
                    
                    <div class="form-group">
                        <label>SMTP Port *</label>
                        <input type="number" name="smtp_port" value="<?= htmlspecialchars($displaySettings['smtp_port'] ?? '587') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>SMTP Username *</label>
                        <input type="text" name="smtp_user" value="<?= htmlspecialchars($displaySettings['smtp_user'] ?? '') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>SMTP Password *</label>
                        <input type="password" name="smtp_password" value="" required placeholder="<?= $displaySettings ? 'Leave blank to keep current' : 'Enter password' ?>">
                        <?php if ($displaySettings): ?>
                            <small>Leave blank to keep current password</small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label>Encryption *</label>
                        <select name="encryption" required>
                            <option value="TLS" <?= ($displaySettings['encryption'] ?? 'TLS') == 'TLS' ? 'selected' : '' ?>>TLS</option>
                            <option value="SSL" <?= ($displaySettings['encryption'] ?? '') == 'SSL' ? 'selected' : '' ?>>SSL</option>
                            <option value="None" <?= ($displaySettings['encryption'] ?? '') == 'None' ? 'selected' : '' ?>>None</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Default From Email</label>
                        <input type="email" name="default_from_email" value="<?= htmlspecialchars($displaySettings['default_from_email'] ?? '') ?>" placeholder="noreply@example.com">
                    </div>
                    
                    <div class="form-group">
                        <label>Default From Name</label>
                        <input type="text" name="default_from_name" value="<?= htmlspecialchars($displaySettings['default_from_name'] ?? '') ?>" placeholder="Your Company Name">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Save SMTP Settings</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
