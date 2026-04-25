<?php
session_start();
require_once '../config.php';
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: login.php');
    exit;
}

$action = $_GET['action'] ?? '';
$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    if ($action === 'approve') {
        $conn->query("UPDATE reviews SET approved = 1 WHERE id = $id");
    } elseif ($action === 'hide') {
        $conn->query("UPDATE reviews SET approved = 0 WHERE id = $id");
    } elseif ($action === 'delete') {
        $conn->query("DELETE FROM reviews WHERE id = $id");
    }
}

header('Location: reviews.php');
exit;
