<?php
session_start();
require_once('../../bootstrap.php');


$user_uuid = $_SESSION['user_data']['user_uuid'] ?? null;

if (!$user_uuid) {
  echo json_encode(['data' => []]);
  exit;
}

$pdo = $GLOBALS['pdo_hipaa'];
$stmt = $pdo->prepare("SELECT form_uuid, form_title, form_description, form_slug, company_slug, created_at, updated_at, is_active FROM form_definitions WHERE user_uuid = :uuid ORDER BY updated_at DESC");
$stmt->execute([':uuid' => $user_uuid]);
$forms = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['data' => $forms]);
