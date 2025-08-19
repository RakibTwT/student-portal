<?php
session_start();
require_once 'config.php';

function verifyGoogleToken($id_token) {
    $client_id = '685696352202-m46agnnlidcchatdtihob3lrqv68d4is.apps.googleusercontent.com';

    $url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . $id_token;
    $response = @file_get_contents($url);
    if ($response === false) return false;

    $payload = json_decode($response, true);
    if (isset($payload['aud']) && $payload['aud'] === $client_id) {
        return $payload; // valid token info
    }
    return false;
}

// Get POST JSON
$data = json_decode(file_get_contents('php://input'), true);
$id_token = $data['id_token'] ?? '';

if (!$id_token) {
    echo json_encode(['success' => false, 'message' => 'No ID token provided']);
    exit;
}

$payload = verifyGoogleToken($id_token);
if (!$payload) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID token']);
    exit;
}

// Extract user info
$email = $payload['email'] ?? '';
$name = $payload['name'] ?? '';
$oauth_uid = $payload['sub'] ?? ''; // Google user ID
$oauth_provider = 'google';

if (!$email || !$oauth_uid) {
    echo json_encode(['success' => false, 'message' => 'Invalid token data']);
    exit;
}

// Check if user exists
$stmt = $conn->prepare("SELECT id FROM users WHERE oauth_provider = ? AND oauth_uid = ?");
$stmt->bind_param("ss", $oauth_provider, $oauth_uid);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($user_id);
    $stmt->fetch();
    $_SESSION['user_id'] = $user_id;
} else {
    $stmt->close();
    $stmt = $conn->prepare("INSERT INTO users (name, email, oauth_provider, oauth_uid) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $oauth_provider, $oauth_uid);
    if ($stmt->execute()) {
        $_SESSION['user_id'] = $stmt->insert_id;
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
        exit;
    }
}
$stmt->close();

echo json_encode(['success' => true]);
exit;
