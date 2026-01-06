<?php
require_once '../../bootstrap.php';

$slug = $_POST['slug'] ?? '';
$company = $_POST['company_slug'] ?? '';

header('Content-Type: application/json');

if (!$slug || !$company) {
    echo json_encode(['exists' => false]);
    exit;
}

$stmt = $pdo_hipaa->prepare("SELECT COUNT(*) FROM form_definitions WHERE form_slug = ? AND company_slug = ?");
$stmt->execute([$slug, $company]);
$count = $stmt->fetchColumn();

echo json_encode(['exists' => $count > 0]);      

