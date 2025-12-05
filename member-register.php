<?php
session_start();
require_once 'classes/Member.php';
require_once 'classes/BaseModelHelper.php';
require_once 'config/Database.php';

$db = new dataBase(['host' => 'localhost', 'user' => 'root', 'password' => 'root', 'database' => 'email_marketing_system']);

$message = '';
$messageType = 'error';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_name = trim($_POST['member_name'] ?? '');
    $member_email = trim($_POST['member_email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($member_name) || empty($member_email) || empty($password)) {
        $message = "All fields are required.";
    } elseif (!filter_var($member_email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email address.";
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
    } else {
        // Check if email already exists
        $emailCheck = $db->db->query("SELECT * FROM Members WHERE member_email = '" . $db->db->real_escape_string($member_email) . "' LIMIT 1");
        
        if ($emailCheck && $emailCheck->num_rows > 0) {
            $message = "Email already registered. Please log in instead.";
        } else {
            // Hash password
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            
            // Create member
            $data = [
                'member_name' => $member_name,
                'member_email' => $member_email,
                'member_status' => 'Active',
                'password_hash' => $password_hash,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $member_id = BaseModelHelper::mysqliCreate($db->db, 'Members', $data);
            
            if ($member_id) {
                // Log audit trail
                BaseModelHelper::mysqliCreate($db->db, 'Audit_Trail', [
                    'member_id' => $member_id,
                    'action_type' => 'MEMBER_REGISTERED',
                    'entity_type' => 'Members',
                    'entity_id' => $member_id,
                    'details' => "Member self-registered: {$member_name} ({$member_email})",
                    'performed_on' => date('Y-m-d H:i:s')
                ]);
                
                // Auto-login after registration
                $_SESSION['member_id'] = $member_id;
                $_SESSION['member_name'] = $member_name;
                $_SESSION['member_email'] = $member_email;
                
                header("Location: member_dashboard.php?message=" . urlencode("Registration successful! Welcome!") . "&type=success");
                exit;
            } else {
                $message = "Registration failed: " . $db->db->error;
            }
        }
    }
    
    if ($message) {
        $messageType = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Email Marketing System</title>
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
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 2rem;
        }

        .register-container {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 2.5rem;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 20px 60px var(--shadow);
        }

        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .register-header h1 {
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .register-header p {
            color: var(--text-muted);
            font-size: 0.875rem;
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

        .form-group input {
            width: 100%;
            padding: 0.75rem;
            background: var(--bg-primary);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
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

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            border: none;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
            width: 100%;
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

        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        .login-link a {
            color: var(--accent);
            text-decoration: none;
        }

        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h1>Create Account</h1>
            <p>Register to get started with email marketing</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="member_name" value="<?= htmlspecialchars($_POST['member_name'] ?? '') ?>" required placeholder="Enter your full name">
            </div>

            <div class="form-group">
                <label>Email Address *</label>
                <input type="email" name="member_email" value="<?= htmlspecialchars($_POST['member_email'] ?? '') ?>" required placeholder="Enter your email">
            </div>

            <div class="form-group">
                <label>Password *</label>
                <input type="password" name="password" required placeholder="Minimum 6 characters" minlength="6">
            </div>

            <div class="form-group">
                <label>Confirm Password *</label>
                <input type="password" name="confirm_password" required placeholder="Re-enter your password" minlength="6">
            </div>

            <button type="submit" class="btn btn-primary">Register</button>
        </form>

        <div class="login-link">
            Already have an account? <a href="login.php">Log in here</a>
        </div>
    </div>
</body>
</html>

