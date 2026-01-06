<?php
session_start();
require_once('../../bootstrap.php');


$user_uuid = $_SESSION['user_data']['user_uuid'] ?? null;

if (!$user_uuid) {
  echo json_encode(['data' => []]);
  exit;
}

$pdo = $GLOBALS['pdo_hipaa'];
$stmt = $pdo->prepare("SELECT fe.*, 
        fd.form_title 
        FROM form_entries fe 
        LEFT JOIN form_definitions fd 
        ON 
        fe.form_uuid=fd.form_uuid 
        ORDER BY fe.submitted_at DESC");
$stmt->execute();
$forms = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['data' => $forms]);
