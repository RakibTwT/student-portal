<?php
require_once 'config.php';

// Protect page
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    header("Location: students.php");
    exit();
}

// Delete student
$stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
$stmt->bind_param("i", $id);

$stmt->execute();
$stmt->close();

header("Location: students.php");
exit();
