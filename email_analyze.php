<?php
require_once 'config.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$headers = $input['headers'] ?? '';

if (empty($headers) || strlen($headers) > 50000) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid or oversized headers']);
    exit;
}

// Basic email header parsing
function parseHeaders($raw) {
    $lines = explode("\n", $raw);
    $parsed = [];
    $current = '';
    foreach ($lines as $line) {
        if (preg_match('/^([A-Za-z-]+):\s*(.*)$/', $line, $m)) {
            if ($current) $parsed[$current] = trim($parsed[$current] ?? '');
            $current = $m[1];
            $parsed[$current] = $m[2];
        } elseif ($current && preg_match('/^\s+(.*)$/', $line, $m)) {
            $parsed[$current] .= ' ' . $m[1];
        }
    }
    if ($current) $parsed[$current] = trim($parsed[$current] ?? '');
    return $parsed;
}

$parsed = parseHeaders($headers);
$riskScore = 0;
$findings = [];

// Check for SPF
if (preg_match('/spf=pass/i', $headers)) {
    $findings[] = ['title' => 'SPF Pass', 'severity' => 'Info', 'detail' => 'SPF authentication passed'];
} elseif (preg_match('/spf=fail/i', $headers)) {
    $riskScore += 30;
    $findings[] = ['title' => 'SPF Fail', 'severity' => 'High', 'detail' => 'SPF authentication failed'];
} else {
    $findings[] = ['title' => 'SPF Unknown', 'severity' => 'Info', 'detail' => 'No SPF result in headers'];
}

// Check for DKIM
if (preg_match('/dkim=pass/i', $headers)) {
    $findings[] = ['title' => 'DKIM Pass', 'severity' => 'Info', 'detail' => 'DKIM signature verified'];
} elseif (preg_match('/dkim=fail/i', $headers)) {
    $riskScore += 25;
    $findings[] = ['title' => 'DKIM Fail', 'severity' => 'High', 'detail' => 'DKIM signature failed'];
} else {
    $findings[] = ['title' => 'DKIM Unknown', 'severity' => 'Info', 'detail' => 'No DKIM result in headers'];
}

// Check for DMARC
if (preg_match('/dmarc=pass/i', $headers)) {
    $findings[] = ['title' => 'DMARC Pass', 'severity' => 'Info', 'detail' => 'DMARC policy passed'];
} elseif (preg_match('/dmarc=fail/i', $headers)) {
    $riskScore += 35;
    $findings[] = ['title' => 'DMARC Fail', 'severity' => 'High', 'detail' => 'DMARC policy failed'];
} else {
    $findings[] = ['title' => 'DMARC Unknown', 'severity' => 'Info', 'detail' => 'No DMARC result in headers'];
}

// From/Return-Path mismatch
$from = $parsed['From'] ?? $parsed['from'] ?? '';
$returnPath = $parsed['Return-Path'] ?? $parsed['return-path'] ?? '';
if ($from && $returnPath && stripos($from, $returnPath) === false) {
    $riskScore += 15;
    $findings[] = ['title' => 'From/Return-Path Mismatch', 'severity' => 'Medium', 'detail' => 'Sender address differs from return path'];
}

$severity = $riskScore >= 50 ? 'High' : ($riskScore >= 20 ? 'Medium' : 'Low');

$data = [
    'risk_score' => min($riskScore, 100),
    'severity' => $severity,
    'findings' => $findings,
    'parsed_headers' => $parsed
];

$stmt = $pdo->prepare("INSERT INTO investigations (type, target, risk_score, severity, summary, raw_data) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->execute(['email', $parsed['Subject'] ?? 'Email Analysis', min($riskScore, 100), $severity, 'Email header analysis', json_encode($data)]);
$investigationId = $pdo->lastInsertId();

echo json_encode(['id' => $investigationId, 'results' => $data]);