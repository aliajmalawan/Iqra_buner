<?php
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'cookie_secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
]);
require_once '../includes/config.php';
require_once '../includes/audit_helper.php';

// Optional ?next=page.php (e.g. the homepage "Support Staff" card links to
// login.php?next=manage_support_staff.php). Only an existing AdminCP page name is
// accepted — never a full URL — so it cannot be used as an open redirect.
function adminSafeNext($next) {
    $next = (string) $next;
    if (!preg_match('/^[a-z0-9_]+\.php$/', $next) || in_array($next, ['login.php', 'logout.php', 'index.php'], true)) return 'dashboard.php';
    return is_file(__DIR__ . '/' . $next) ? $next : 'dashboard.php';
}
$login_next = adminSafeNext($_POST['next'] ?? $_GET['next'] ?? '');

// Redirect if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: ' . $login_next);
    exit;
}

$error_message = '';

// Brute-force protection: lock an account for 15 minutes after 5 consecutive failed attempts.
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_MINUTES', 15);

// Prepared statement for the admin_users updates below (never builds SQL from input)
function adminLoginExec($conn, $sql, $types, ...$args) {
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$args);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  try {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    $stmt = mysqli_prepare($conn, "SELECT * FROM admin_users WHERE username = ? AND status = 'active' LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $username);
    mysqli_stmt_execute($stmt);
    $admin = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    $who = $admin ? ['id' => $admin['id'], 'username' => $admin['username'], 'role' => $admin['role']] : ['id' => null, 'username' => mb_substr($username, 0, 50), 'role' => ''];

    if ($admin) {
        $is_locked = !empty($admin['locked_until']) && strtotime($admin['locked_until']) > time();

        if ($is_locked) {
            $minutes_left = max(1, ceil((strtotime($admin['locked_until']) - time()) / 60));
            $error_message = "Too many failed attempts. Try again in {$minutes_left} minute(s).";
            audit_log($conn, 'login_blocked', 'auth', $admin['username'], 'Sign-in attempt while the account is locked', $who);
        } elseif (password_verify($password, $admin['password'])) {
            // Correct password — regenerate the session ID before establishing the
            // authenticated session so a pre-login session (session fixation) can
            // never be reused as a logged-in admin session.
            session_regenerate_id(true);

            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_name'] = $admin['full_name'];
            $_SESSION['admin_role'] = $admin['role'];

            adminLoginExec($conn, "UPDATE admin_users SET last_login = NOW(), failed_login_attempts = 0, locked_until = NULL WHERE id = ?", 'i', (int) $admin['id']);
            audit_log($conn, 'login', 'auth', $admin['username'], 'Signed in');

            header('Location: ' . $login_next);
            exit;
        } else {
            // Wrong password — count the attempt and lock the account once the threshold is hit.
            $attempts = (int) $admin['failed_login_attempts'] + 1;
            if ($attempts >= MAX_LOGIN_ATTEMPTS) {
                adminLoginExec($conn, "UPDATE admin_users SET failed_login_attempts = 0, locked_until = DATE_ADD(NOW(), INTERVAL " . (int) LOCKOUT_MINUTES . " MINUTE) WHERE id = ?", 'i', (int) $admin['id']);
                $error_message = "Too many failed attempts. Account locked for " . LOCKOUT_MINUTES . " minutes.";
                audit_log($conn, 'account_locked', 'auth', $admin['username'], 'Locked for ' . LOCKOUT_MINUTES . ' minutes after ' . MAX_LOGIN_ATTEMPTS . ' wrong passwords', $who);
            } else {
                adminLoginExec($conn, "UPDATE admin_users SET failed_login_attempts = ? WHERE id = ?", 'ii', $attempts, (int) $admin['id']);
                $error_message = 'Invalid username or password';
                audit_log($conn, 'login_failed', 'auth', $admin['username'], 'Wrong password (attempt ' . $attempts . ' of ' . MAX_LOGIN_ATTEMPTS . ')', $who);
            }
        }
    } else {
        $error_message = 'Invalid username or password';
        audit_log($conn, 'login_failed', 'auth', mb_substr($username, 0, 50), 'Unknown or inactive username', $who);
    }
  } catch (mysqli_sql_exception $e) {
    error_log('[login] ' . $e->getMessage());
    $error_message = 'Sign-in is temporarily unavailable. Please try again in a moment.';
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo htmlspecialchars(getSiteName()); ?></title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo @filemtime(__DIR__ . '/../css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, var(--primary-color) 0%, #0B1F42 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 16px;
            border-top: 3px solid var(--gold-color);
            box-shadow: 0 24px 64px rgba(0, 0, 0, 0.32);
            max-width: 450px;
            width: 100%;
            padding: 50px 40px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .login-logo {
            width: 84px; height: 84px; object-fit: contain;
            margin: 0 auto 18px; display: block;
            border-radius: 14px; padding: 6px;
            border: 1px solid var(--border-color);
        }
        .login-header h1 {
            color: var(--primary-color);
            font-size: 26px;
            margin-bottom: 10px;
        }

        .login-header p {
            color: var(--text-light);
        }

        .logo-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-color), #0B1F42);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 40px;
            color: white;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        .alert-error {
            background: #FEF2F2;
            color: #991B1B;
            border: 1px solid #f5c6cb;
        }

        .form-group {
            margin-bottom: 22px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 14px;
        }

        .form-group label i {
            color: var(--primary-color);
            margin-right: 6px;
            width: 16px;
            text-align: center;
        }

        .form-group input {
            display: block;
            width: 100%;
            padding: 12px 16px;
            font-size: 15px;
            font-family: var(--font);
            border: 1px solid #DCE5EF;
            border-radius: 10px;
            background: #f9fafb;
            transition: var(--transition);
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            background: white;
            box-shadow: 0 0 0 3px rgba(16,42,86,0.12);
        }

        .btn-login {
            width: 100%;
            padding: 15px;
            font-size: 16px;
            font-weight: 600;
            margin-top: 10px;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: var(--primary-color);
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <?php $login_logo = getLogoPath(); ?>
            <img src="../<?php echo htmlspecialchars(ltrim($login_logo, '/')); ?>" alt="" class="login-logo"
                 onerror="this.outerHTML='<div class=&quot;logo-icon&quot;><i class=&quot;fas fa-user-shield&quot;></i></div>'">
            <h1>Admin Login</h1>
            <p><?php echo htmlspecialchars(getSiteName()); ?></p>
        </div>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <?php if ($login_next !== 'dashboard.php'): ?><input type="hidden" name="next" value="<?php echo htmlspecialchars($login_next); ?>"><?php endif; ?>
            <div class="form-group">
                <label for="username"><i class="fas fa-user"></i> Username</label>
                <input type="text" id="username" name="username" required autofocus placeholder="Enter your username">
            </div>

            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Password</label>
                <input type="password" id="password" name="password" required placeholder="Enter your password">
            </div>

            <button type="submit" class="btn btn-primary btn-login">
                <i class="fas fa-sign-in-alt"></i> Login to Dashboard
            </button>
        </form>

        <div class="back-link">
            <a href="../index.php"><i class="fas fa-arrow-left"></i> Back to Website</a>
        </div>

    </div>
</body>
</html>
