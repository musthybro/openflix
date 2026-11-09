<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

// Support both FormData / URL-encoded POST and JSON payload
$raw_input = file_get_contents('php://input');
$json_data = json_decode($raw_input, true) ?: [];
$_REQ = array_merge($_GET, $_POST, $json_data);

$action = $_REQ['action'] ?? '';

$db = get_db();

if ($action === 'register') {
    $username = trim($_REQ['username'] ?? '');
    $email = trim($_REQ['email'] ?? '');
    $password = $_REQ['password'] ?? '';

    if (empty($username) || empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'error' => 'Please fill in all fields.']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'error' => 'Please enter a valid email address.']);
        exit;
    }

    if (strlen($username) < 3) {
        echo json_encode(['success' => false, 'error' => 'Username must be at least 3 characters.']);
        exit;
    }

    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'error' => 'Password must be at least 6 characters.']);
        exit;
    }

    // Check if user already exists
    $check = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $check->execute([$username, $email]);
    if ($check->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Username or email is already registered.']);
        exit;
    }

    // Hash password with bcrypt
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $initials = strtoupper(substr($username, 0, 2));

    try {
        $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, avatar) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $email, $hash, $initials]);
        $new_id = $db->lastInsertId();

        $_SESSION['user_id'] = $new_id;
        $_SESSION['username'] = $username;
        $_SESSION['avatar'] = $initials;

        echo json_encode([
            'success' => true,
            'message' => 'Account created successfully!',
            'user' => [
                'id' => $new_id,
                'username' => $username,
                'email' => $email,
                'avatar' => $initials
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Registration failed: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'login') {
    $identifier = trim($_REQ['identifier'] ?? $_REQ['username'] ?? $_REQ['email'] ?? '');
    $password = $_REQ['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        echo json_encode(['success' => false, 'error' => 'Please enter your username/email and password.']);
        exit;
    }

    $stmt = $db->prepare("SELECT id, username, email, password_hash, avatar FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$identifier, $identifier]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid username/email or password.']);
        exit;
    }

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['avatar'] = $user['avatar'];

    echo json_encode([
        'success' => true,
        'message' => 'Login successful!',
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'avatar' => $user['avatar']
        ]
    ]);
    exit;
}

if ($action === 'logout') {
    session_unset();
    session_destroy();
    echo json_encode(['success' => true, 'message' => 'Logged out successfully.']);
    exit;
}

if ($action === 'status' || $action === 'me') {
    $user = get_current_user_data();
    echo json_encode([
        'authenticated' => ($user !== null),
        'user' => $user
    ]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action.']);
