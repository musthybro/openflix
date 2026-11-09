<?php
require_once __DIR__ . '/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $db = get_db();
        $check = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check->execute([$username, $email]);
        if ($check->fetch()) {
            $error = 'Username or email is already registered.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $initials = strtoupper(substr($username, 0, 2));

            try {
                $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, avatar) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $email, $hash, $initials]);
                $new_id = $db->lastInsertId();

                $_SESSION['user_id'] = $new_id;
                $_SESSION['username'] = $username;
                $_SESSION['avatar'] = $initials;

                header('Location: index.php');
                exit;
            } catch (Exception $e) {
                $error = 'Registration failed: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Create Account - OpenFlix Global</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Outfit:wght@600;700;800;900&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

  <style>
    :root {
      --bg-primary: #07080b;
      --bg-card: rgba(18, 21, 30, 0.7);
      --red-primary: #e50914;
      --red-hover: #ff2a33;
      --red-glow: rgba(229, 9, 20, 0.55);
      --text-main: #ffffff;
      --text-muted: #a1a1aa;
      --font-body: 'Inter', -apple-system, sans-serif;
      --font-display: 'Outfit', sans-serif;
      --radius-sm: 8px;
      --radius-md: 14px;
      --radius-lg: 22px;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      background-color: var(--bg-primary);
      color: var(--text-main);
      font-family: var(--font-body);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      overflow-x: hidden;
      padding: 24px;
    }

    .ambient-glow-1 {
      position: absolute;
      top: -120px;
      left: 10%;
      width: 450px;
      height: 450px;
      background: radial-gradient(circle, rgba(229, 9, 20, 0.28) 0%, transparent 70%);
      filter: blur(60px);
      pointer-events: none;
    }
    .ambient-glow-2 {
      position: absolute;
      bottom: -100px;
      right: 10%;
      width: 480px;
      height: 480px;
      background: radial-gradient(circle, rgba(56, 189, 248, 0.15) 0%, transparent 70%);
      filter: blur(70px);
      pointer-events: none;
    }

    .glisse-auth-card {
      width: 100%;
      max-width: 440px;
      background: linear-gradient(135deg, rgba(255, 255, 255, 0.08) 0%, rgba(255, 255, 255, 0.02) 100%);
      backdrop-filter: blur(32px) saturate(200%);
      -webkit-backdrop-filter: blur(32px) saturate(200%);
      border: 1px solid rgba(255, 255, 255, 0.15);
      border-radius: var(--radius-lg);
      padding: 38px 34px;
      box-shadow: 
        0 24px 60px rgba(0, 0, 0, 0.8),
        inset 0 1px 1.5px rgba(255, 255, 255, 0.35),
        0 0 30px rgba(229, 9, 20, 0.12);
      position: relative;
      z-index: 10;
    }

    .auth-header {
      text-align: center;
      margin-bottom: 24px;
    }

    .openflix-logo {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      text-decoration: none;
      font-family: var(--font-display);
      font-weight: 900;
      font-size: 28px;
      letter-spacing: 1.5px;
      text-transform: uppercase;
      margin-bottom: 6px;
    }
    .logo-open { color: #ffffff; }
    .logo-flix { color: var(--red-primary); text-shadow: 0 0 18px var(--red-glow); }
    .logo-badge {
      font-size: 10px;
      font-weight: 800;
      background: var(--red-primary);
      color: #fff;
      padding: 2px 7px;
      border-radius: var(--radius-sm);
      margin-left: 6px;
    }

    .auth-title {
      font-size: 21px;
      font-weight: 800;
      color: #ffffff;
      margin-bottom: 4px;
    }

    .auth-subtitle {
      font-size: 13px;
      color: var(--text-muted);
    }

    .form-group {
      margin-bottom: 15px;
    }

    .form-label {
      display: block;
      font-size: 12px;
      font-weight: 600;
      color: #cbd5e1;
      margin-bottom: 6px;
    }

    .glisse-input-wrap {
      position: relative;
      display: flex;
      align-items: center;
    }

    .glisse-input-icon {
      position: absolute;
      left: 14px;
      color: #71717a;
      font-size: 13.5px;
      pointer-events: none;
      transition: color 0.2s ease;
    }

    .glisse-input {
      width: 100%;
      height: 44px;
      padding: 0 16px 0 40px;
      background: rgba(255, 255, 255, 0.06);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border: 1px solid rgba(255, 255, 255, 0.12);
      border-radius: var(--radius-md);
      color: #ffffff;
      font-family: inherit;
      font-size: 13.5px;
      outline: none;
      transition: all 0.22s ease;
      box-shadow: inset 0 1px 1.5px rgba(255, 255, 255, 0.1);
    }

    .glisse-input:focus {
      background: rgba(255, 255, 255, 0.10);
      border-color: rgba(229, 9, 20, 0.65);
      box-shadow: 0 0 16px var(--red-glow), inset 0 1px 1px rgba(255, 255, 255, 0.25);
    }

    .glisse-input:focus + .glisse-input-icon,
    .glisse-input-wrap:focus-within .glisse-input-icon {
      color: var(--red-primary);
    }

    .glisse-btn-filled {
      width: 100%;
      height: 46px;
      background: linear-gradient(135deg, var(--red-primary) 0%, #b80710 100%);
      border: 1px solid rgba(255, 255, 255, 0.28);
      border-radius: var(--radius-md);
      color: #ffffff;
      font-family: inherit;
      font-size: 14.5px;
      font-weight: 700;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: all 0.22s ease;
      box-shadow: 0 6px 20px var(--red-glow), inset 0 1px 1.5px rgba(255, 255, 255, 0.4);
      margin-top: 20px;
    }

    .glisse-btn-filled:hover {
      background: linear-gradient(135deg, var(--red-hover) 0%, var(--red-primary) 100%);
      transform: translateY(-2px);
      box-shadow: 0 10px 28px rgba(229, 9, 20, 0.65);
    }

    .alert {
      padding: 11px 13px;
      border-radius: var(--radius-sm);
      font-size: 13px;
      margin-bottom: 16px;
      display: flex;
      align-items: center;
      gap: 9px;
    }
    .alert-error {
      background: rgba(239, 68, 68, 0.15);
      border: 1px solid rgba(239, 68, 68, 0.4);
      color: #fca5a5;
    }

    .auth-footer {
      text-align: center;
      margin-top: 22px;
      font-size: 13.5px;
      color: var(--text-muted);
    }

    .auth-link {
      color: #ffffff;
      font-weight: 700;
      text-decoration: none;
      margin-left: 4px;
    }
    .auth-link:hover {
      color: var(--red-hover);
      text-decoration: underline;
    }

    .back-home {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      color: var(--text-muted);
      text-decoration: none;
      font-size: 13px;
      margin-bottom: 14px;
      transition: color 0.2s ease;
    }
    .back-home:hover {
      color: #ffffff;
    }
  </style>
</head>
<body>
  <div class="ambient-glow-1"></div>
  <div class="ambient-glow-2"></div>

  <div class="glisse-auth-card">
    <div class="auth-header">
      <div class="openflix-logo">
        <span class="logo-open">OPEN</span><span class="logo-flix">FLIX</span>
        <span class="logo-badge">GLOBAL</span>
      </div>
      <h1 class="auth-title">Create Account</h1>
      <p class="auth-subtitle">Join OpenFlix for limitless 4K streaming</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-error">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span><?= htmlspecialchars($error) ?></span>
      </div>
    <?php endif; ?>

    <form method="POST" action="register.php">
      <input type="hidden" name="action" value="register" />

      <div class="form-group">
        <label class="form-label" for="username">Username</label>
        <div class="glisse-input-wrap">
          <input type="text" id="username" name="username" class="glisse-input" placeholder="Choose a username" required minlength="3" autocomplete="username" />
          <i class="fa-solid fa-user glisse-input-icon"></i>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="email">Email Address</label>
        <div class="glisse-input-wrap">
          <input type="email" id="email" name="email" class="glisse-input" placeholder="name@example.com" required autocomplete="email" />
          <i class="fa-solid fa-envelope glisse-input-icon"></i>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <div class="glisse-input-wrap">
          <input type="password" id="password" name="password" class="glisse-input" placeholder="Minimum 6 characters" required minlength="6" autocomplete="new-password" />
          <i class="fa-solid fa-lock glisse-input-icon"></i>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="confirm_password">Confirm Password</label>
        <div class="glisse-input-wrap">
          <input type="password" id="confirm_password" name="confirm_password" class="glisse-input" placeholder="Repeat your password" required minlength="6" autocomplete="new-password" />
          <i class="fa-solid fa-shield-halved glisse-input-icon"></i>
        </div>
      </div>

      <button type="submit" class="glisse-btn-filled">
        <i class="fa-solid fa-user-plus"></i> Create Account
      </button>
    </form>

    <div class="auth-footer">
      Already have an account? 
      <a href="login.php" class="auth-link">Sign In</a>
    </div>
  </div>
</body>
</html>
