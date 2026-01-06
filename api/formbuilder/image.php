<?php
require_once '../../dashboards/_init.php'; // Include session + DB

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_uuid = $_SESSION['user_data']['user_uuid'] ?? null;
    $form_uuid = $_POST['form_uuid'] ?? null;               
    $field_id = $_POST['field_id'] ?? null;

    if (!$user_uuid || !$form_uuid || !$field_id || !isset($_FILES['image'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required file upload']);
        exit;
    }

    $upload_dir = "/opt/mka/storage/formbuilder/$user_uuid/$form_uuid/temp/$field_id";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0750, true);
    }

    $filename = basename($_FILES['image']['name']);
    $target = "$upload_dir/$filename";

    if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
        echo json_encode([
            'success' => true,
            'filename' => $filename,
            'url' => "/formbuilder/image.php?form_uuid=$form_uuid&field_id=$field_id&file=$filename"
        ]);
        exit;
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file']);
        exit;
    }
}

// Existing GET logic...
$customer_uuid = $_SESSION['user_data']['user_uuid'] ?? '';
$form_uuid = $_GET['form_uuid'] ?? '';
$file = basename($_GET['file'] ?? '');
$field_id = $_GET['field_id'] ?? '';

$basePath = "/opt/mka/storage/formbuilder";
$filepath = "$basePath/$customer_uuid/$form_uuid/final/$field_id/$file";
if (!file_exists($filepath)) {
    $filepath = "$basePath/$customer_uuid/$form_uuid/temp/$field_id/$file";
}

if (!file_exists($filepath)) {
    http_response_code(404);
    exit('File not found.');
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $filepath);
finfo_close($finfo);

header("Content-Type: $mime");
header('Content-Length: ' . filesize($filepath));
readfile($filepath);
exit;