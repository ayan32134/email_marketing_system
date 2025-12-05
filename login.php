<?php
session_start();
require_once 'classes/admin.php';
require_once 'classes/BaseModelHelper.php';
require_once 'config/Database.php';

$db = new dataBase(['host' => 'localhost', 'user' => 'root', 'password' => 'root', 'database' => 'email_marketing_system']);
$adminModel = new Admin();
$adminModel->db = $db->db;

$adminCountResult = $db->db->query("SELECT COUNT(*) as total FROM Admins");
$existingAdmins = $adminCountResult ? (int)$adminCountResult->fetch_assoc()['total'] : 0;
$allowAdminRegistration = $existingAdmins === 0;

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $message = "Please fill in all fields.";
    } else {
        // Attempt admin login first
        $sql = $adminModel->buildSQL('Admins', [], 'select', ['admin_email' => $email]);
        $result = $adminModel->db->query($sql);

        if ($result && $result->num_rows === 1) {
            $admin = $result->fetch_assoc();
            if (password_verify($password, $admin['password_hash'])) {
                $_SESSION['admin_id'] = $admin['admin_id'];
                $_SESSION['admin_email'] = $admin['admin_email'];
                $_SESSION['admin_role'] = $admin['role'] ?? 'SuperAdmin';
                header('Location: admin-dashboard.php');
                exit;
            } else {
                $message = "Invalid credentials.";
            }
        } else {
            // Attempt member login
            $memberRows = BaseModelHelper::mysqliGetAll($db->db, 'Members', ['member_email' => $email]);
            $member = $memberRows[0] ?? null;

            if ($member && isset($member['password_hash']) && password_verify($password, $member['password_hash'])) {
                $_SESSION['member_id'] = $member['member_id'];
                $_SESSION['member_name'] = $member['member_name'];
                $_SESSION['member_email'] = $member['member_email'];

                header('Location: member_dashboard.php');
                exit;
            } else {
                $message = "Invalid credentials.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Email Marketing System</title>
    <style>
        /* ====== Dark Theme Variables ====== */
        :root {
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --bg-tertiary: #334155;
            --text-primary: #f1f5f9;
            --text-secondary: #cbd5e1;
            --accent: #3b82f6;
            --accent-hover: #2563eb;
            --success: #10b981;
            --danger: #ef4444;
            --border: #334155;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .login-container {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 3rem;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, var(--accent), var(--accent-hover));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .login-header p {
            color: var(--text-secondary);
            font-size: 0.9375rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-secondary);
        }

        .form-group input {
            width: 100%;
            padding: 0.875rem 1rem;
            background: var(--bg-primary);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 0.9375rem;
            transition: all 0.2s;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-group input::placeholder {
            color: var(--text-secondary);
        }

        .btn-login {
            width: 100%;
            padding: 0.875rem;
            background: var(--accent);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 0.5rem;
        }

        .btn-login:hover {
            background: var(--accent-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            text-align: center;
            font-size: 0.875rem;
        }

        .message.error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid var(--danger);
            color: var(--danger);
        }

        .message.success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid var(--success);
            color: var(--success);
        }

        .login-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border);
        }

        .login-footer a {
            color: var(--accent);
            text-decoration: none;
            font-size: 0.875rem;
        }

        .login-footer a:hover {
            text-decoration: underline;
        }

        .back-home {
            position: absolute;
            top: 2rem;
            left: 2rem;
            color: var(--text-secondary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: color 0.2s;
            
        }
      
        .back-home:hover {
            color: var(--text-primary);
        }
    </style>
</head>
<body>
    <a href="index.php" class="back-home">← Back to Home</a>

    <div class="login-container">
        <div class="login-header">
            <h1>🔐 Unified Login</h1>
            <p>Sign in to access your admin or member workspace</p>
        </div>

        <?php if (isset($_GET['registered']) && $_GET['registered'] == 1): ?>
            <div class="message success">Registration successful. Please login.</div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="message error"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="admin@example.com" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>

            <button type="submit" class="btn-login">Sign In</button>
        </form>

        <div class="login-footer">
            <?php if ($allowAdminRegistration): ?>
                <p>First-time setup? <a href="process/register_admin.php">Create the initial admin</a>.</p>
            <?php else: ?>
                <p>Only the system owner can sign in here. Members should use the public register/login pages.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
