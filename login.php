<?php
require_once __DIR__ . '/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        $error = 'Please enter your username/email and password.';
    } else {
        $db = get_db();
        $stmt = $db->prepare("SELECT id, username, email, password_hash, avatar FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['avatar'] = $user['avatar'];
            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid credentials. Please verify your details.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sign In - OpenFlix Global</title>
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
      --radius-full: 9999px;
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

    /* Ambient Cinema Background Lights */
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
      background: radial-gradient(circle, rgba(147, 51, 234, 0.18) 0%, transparent 70%);
      filter: blur(70px);
      pointer-events: none;
    }

    /* Glisse Liquid Glass Card */
    .glisse-auth-card {
      width: 100%;
      max-width: 440px;
      background: linear-gradient(135deg, rgba(255, 255, 255, 0.08) 0%, rgba(255, 255, 255, 0.02) 100%);
      backdrop-filter: blur(32px) saturate(200%);
      -webkit-backdrop-filter: blur(32px) saturate(200%);
      border: 1px solid rgba(255, 255, 255, 0.15);
      border-radius: var(--radius-lg);
      padding: 40px 36px;
      box-shadow: 
        0 24px 60px rgba(0, 0, 0, 0.8),
        inset 0 1px 1.5px rgba(255, 255, 255, 0.35),
        0 0 30px rgba(229, 9, 20, 0.12);
      position: relative;
      z-index: 10;
    }

    .auth-header {
      text-align: center;
      margin-bottom: 28px;
    }

    .openflix-logo {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      text-decoration: none;
      font-family: var(--font-display);
      font-weight: 900;
      font-size: 30px;
      letter-spacing: 1.5px;
      text-transform: uppercase;
      margin-bottom: 8px;
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
      margin-bottom: 6px;
    }

    .auth-subtitle {
      font-size: 13.5px;
      color: var(--text-muted);
    }

    .form-group {
      margin-bottom: 18px;
    }

    .form-label {
      display: block;
      font-size: 12.5px;
      font-weight: 600;
      color: #cbd5e1;
      margin-bottom: 7px;
      letter-spacing: 0.2px;
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
      font-size: 14px;
      pointer-events: none;
      transition: color 0.2s ease;
    }

    .glisse-input {
      width: 100%;
      height: 46px;
      padding: 0 16px 0 42px;
      background: rgba(255, 255, 255, 0.06);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border: 1px solid rgba(255, 255, 255, 0.12);
      border-radius: var(--radius-md);
      color: #ffffff;
      font-family: inherit;
      font-size: 14px;
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
      font-size: 15px;
      font-weight: 700;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: all 0.22s ease;
      box-shadow: 0 6px 20px var(--red-glow), inset 0 1px 1.5px rgba(255, 255, 255, 0.4);
      margin-top: 24px;
    }

    .glisse-btn-filled:hover {
      background: linear-gradient(135deg, var(--red-hover) 0%, var(--red-primary) 100%);
      transform: translateY(-2px);
      box-shadow: 0 10px 28px rgba(229, 9, 20, 0.65);
    }

    .alert {
      padding: 12px 14px;
      border-radius: var(--radius-sm);
      font-size: 13px;
      margin-bottom: 18px;
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
      margin-top: 24px;
      font-size: 13.5px;
      color: var(--text-muted);
    }

    .auth-link {
      color: #ffffff;
      font-weight: 700;
      text-decoration: none;
      margin-left: 4px;
      transition: color 0.2s ease;
    }
    .auth-link:hover {
      color: var(--red-hover);
      text-decoration: underline;
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
      <h1 class="auth-title">Welcome Back</h1>
      <p class="auth-subtitle">Sign in to sync your Watchlist & 4K streams</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-error">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span><?= htmlspecialchars($error) ?></span>
      </div>
    <?php endif; ?>

    <form method="POST" action="login.php" id="loginForm">
      <input type="hidden" name="action" value="login" />

      <div class="form-group">
        <label class="form-label" for="identifier">Username or Email</label>
        <div class="glisse-input-wrap">
          <input type="text" id="identifier" name="identifier" class="glisse-input" placeholder="Enter your username or email" required autocomplete="username" />
          <i class="fa-solid fa-user glisse-input-icon"></i>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <div class="glisse-input-wrap">
          <input type="password" id="password" name="password" class="glisse-input" placeholder="Enter your password" required autocomplete="current-password" />
          <i class="fa-solid fa-lock glisse-input-icon"></i>
        </div>
      </div>

      <button type="submit" class="glisse-btn-filled">
        <i class="fa-solid fa-right-to-bracket"></i> Sign In
      </button>
    </form>

    <div class="auth-footer">
      Don't have an account yet? 
      <a href="register.php" class="auth-link">Create Account</a>
    </div>
  </div>
</body>
</html>
