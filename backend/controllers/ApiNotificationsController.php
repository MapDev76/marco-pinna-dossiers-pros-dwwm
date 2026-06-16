<?php
/**
 * API endpoint for handling notifications.
 * Receives POST requests with user_id, message, and type.
 * Saves the notification to the requests table and returns a JSON response.
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../models/UserModel.php';

header('Content-Type: application/json; charset=UTF-8');

$userModel = new UserModel(getPDO());

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['user_id']) || !isset($data['message']) || !isset($data['type'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

$userId = (int) $data['user_id'];
$message = trim((string) $data['message']);
$type = trim((string) $data['type']);

if ($userId <= 0 || $message === '' || $type === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid input data']);
    exit;
}

try {
    $notificationId = $userModel->createNotificationForUser($userId, $type, $message);
    echo json_encode(['success' => true, 'notification_id' => $notificationId]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to save notification']);
}