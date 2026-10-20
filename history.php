<?php
require_once 'config.php';
header('Content-Type: application/json');

$limit = intval($_GET['limit'] ?? 50);
$stmt = $pdo->prepare("SELECT id, type, target, risk_score, severity, created_at FROM investigations ORDER BY created_at DESC LIMIT ?");
$stmt->bindValue(1, $limit, PDO::PARAM_INT);
$stmt->execute();

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
