<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['currency' => 'Rs']);
    exit();
}

$currency = $_SESSION['user_currency'] ?? 'Rs';
echo json_encode(['currency' => $currency]);
?>