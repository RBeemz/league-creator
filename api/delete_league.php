<?php
require_once __DIR__ . '/../includes/functions.php';
require_once 'bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $db = getDB();
    $stmt = $db->prepare("SELECT name FROM leagues WHERE id = ?");
    $stmt->execute([$id]);
    $league = $stmt->fetch();
    if ($league) {
        $db->prepare("DELETE FROM leagues WHERE id = ?")->execute([$id]);
        setFlash('success', "League '{$league['name']}' deleted.");
    }
}
header('Location: index.php'); exit;
