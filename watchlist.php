<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

// Support both FormData / URL-encoded POST and JSON payload
$raw_input = file_get_contents('php://input');
$json_data = json_decode($raw_input, true) ?: [];
$_REQ = array_merge($_GET, $_POST, $json_data);

$db = get_db();
$user_id = $_SESSION['user_id'] ?? null;
$action = $_REQ['action'] ?? 'get';

if (!$user_id) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required. Please sign in.', 'require_login' => true]);
    exit;
}

// Logged in user watchlist stored in SQLite database
if ($action === 'get') {
    $stmt = $db->prepare("SELECT movie_id FROM watchlist WHERE user_id = ? ORDER BY added_at DESC");
    $stmt->execute([$user_id]);
    $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode(['success' => true, 'watchlist' => $ids]);
    exit;
}

if ($action === 'toggle') {
    $movieId = trim($_REQ['movieId'] ?? $_REQ['movie_id'] ?? '');
    $title = trim($_REQ['title'] ?? '');
    $poster = trim($_REQ['poster'] ?? '');
    $year = trim($_REQ['year'] ?? '');
    $type = trim($_REQ['type'] ?? 'movie');

    if (!$movieId) {
        echo json_encode(['success' => false, 'error' => 'Missing movie ID']);
        exit;
    }

    $check = $db->prepare("SELECT id FROM watchlist WHERE user_id = ? AND movie_id = ?");
    $check->execute([$user_id, $movieId]);
    $row = $check->fetch();

    if ($row) {
        $del = $db->prepare("DELETE FROM watchlist WHERE user_id = ? AND movie_id = ?");
        $del->execute([$user_id, $movieId]);
        $inList = false;
    } else {
        $ins = $db->prepare("INSERT INTO watchlist (user_id, movie_id, title, poster, year, type) VALUES (?, ?, ?, ?, ?, ?)");
        $ins->execute([$user_id, $movieId, $title, $poster, $year, $type]);
        $inList = true;
    }

    $stmt = $db->prepare("SELECT movie_id FROM watchlist WHERE user_id = ? ORDER BY added_at DESC");
    $stmt->execute([$user_id]);
    $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'success' => true,
        'inWatchlist' => $inList,
        'watchlist' => $ids
    ]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
