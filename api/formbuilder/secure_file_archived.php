<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../../bootstrap.php';
require_once '/opt/mka/vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Credentials\InstanceProfileProvider;

// 🔐 Enforce session auth
if (!isset($_SESSION['user_data'])) {
    http_response_code(403);
    exit("Forbidden");
}

// Get query parameters
$form_uuid = $_GET['form_uuid'] ?? '';
$entry_uuid = $_GET['entry_uuid'] ?? '';
$filename = $_GET['filename'] ?? '';
$signature = $_GET['signature'] ?? '';
$yearMonth = $_GET['year_month'] ?? ''; // expected format: 2025-07



if (!$form_uuid || !$entry_uuid || !$filename || !$yearMonth) {
    http_response_code(400);
    exit("Missing required parameters");
}


 $objectKey = "$form_uuid/$yearMonth/files/$entry_uuid/$filename";   


// Setup S3 client
$s3 = new S3Client([
    'region' => 'us-east-2',
    'version' => 'latest',
    'credentials' => new InstanceProfileProvider()
]);

$bucket = 'hipaa-formsubmissions-mkadvantage';

try {
    // Generate signed URL (valid 5 minutes)
    $cmd = $s3->getCommand('GetObject', [
        'Bucket' => $bucket,
        'Key' => $objectKey,
    ]);

    $request = $s3->createPresignedRequest($cmd, '+5 minutes');
    $presignedUrl = (string) $request->getUri();

    header("Location: $presignedUrl");
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo "Error: " . $e->getMessage();
}
