<?php
// OpenFlix Database Connection & Session Management
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function get_db() {
    static $pdo = null;
    if ($pdo === null) {
        $db_path = __DIR__ . '/database.sqlite';
        $pdo = new PDO('sqlite:' . $db_path);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // Initialize tables
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                email TEXT UNIQUE NOT NULL,
                password_hash TEXT NOT NULL,
                avatar TEXT DEFAULT 'OF',
                role TEXT DEFAULT 'user',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS watchlist (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                movie_id TEXT NOT NULL,
                title TEXT NOT NULL,
                poster TEXT,
                year TEXT,
                type TEXT,
                added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(user_id, movie_id),
                FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS watch_history (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                movie_id TEXT NOT NULL,
                title TEXT NOT NULL,
                poster TEXT,
                progress_pct INTEGER DEFAULT 0,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(user_id, movie_id)
            );
        ");

        // Seed default demo user if no users exist
        $stmt = $pdo->query("SELECT COUNT(*) FROM users");
        if ($stmt->fetchColumn() == 0) {
            $default_pw = password_hash('Password123!', PASSWORD_BCRYPT);
            $seed = $pdo->prepare("INSERT INTO users (username, email, password_hash, avatar, role) VALUES (?, ?, ?, ?, ?)");
            $seed->execute(['demo', 'demo@openflix.com', $default_pw, 'OF', 'member']);
        }
    }
    return $pdo;
}

function get_current_user_data() {
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    try {
        $db = get_db();
        $stmt = $db->prepare("SELECT id, username, email, avatar, role, created_at FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch() ?: null;
    } catch (Exception $e) {
        return null;
    }
}

function is_logged_in() {
    return !empty($_SESSION['user_id']);
}
