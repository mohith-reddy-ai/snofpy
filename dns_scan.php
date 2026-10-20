<?php
require_once 'config.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$domain = $input['domain'] ?? '';

if (!filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid domain']);
    exit;
}

// Basic DNS checks
$records = [
    'A' => dns_get_record($domain, DNS_A),
    'AAAA' => dns_get_record($domain, DNS_AAAA),
    'MX' => dns_get_record($domain, DNS_MX),
    'NS' => dns_get_record($domain, DNS_NS),
    'TXT' => dns_get_record($domain, DNS_TXT)
];

$riskScore = 0;
$severity = 'Unknown';

// Simple risk scoring example
foreach ($records as $type => $results) {
    if (empty($results)) {
        $riskScore += 5;
    }
}

if ($riskScore > 15) {
    $severity = 'High';
} elseif ($riskScore > 5) {
    $severity = 'Medium';
} else {
    $severity = 'Low';
}

$data = [
    'domain' => $domain,
    'risk_score' => $riskScore,
    'severity' => $severity,
    'records' => $records
];

// Save to DB
$stmt = $pdo->prepare("INSERT INTO investigations (type, target, risk_score, severity, summary, raw_data) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->execute(['dns', $domain, $riskScore, $severity, 'Automated DNS scan', json_encode($data)]);
$investigationId = $pdo->lastInsertId();

echo json_encode(['id' => $investigationId, 'results' => $data]);
