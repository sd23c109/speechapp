<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../bootstrap.php';
require_once '/opt/mka/vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use MKA\Log\MKALogger;

header('Content-Type: application/json');

if (empty($_SESSION['user_data']['user_uuid'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
$uuids = $body['entry_uuids'] ?? [];
if (!is_array($uuids) || !$uuids) {
    echo json_encode(['success' => false, 'message' => 'No entries specified']);
    exit;
}

$user_uuid = $_SESSION['user_data']['user_uuid'];
$placeholders = implode(',', array_fill(0, count($uuids), '?'));

$s3 = new S3Client([
    'region'      => 'us-east-2',
    'version'     => 'latest',
    'http'        => ['verify' => true],
    'credentials' => new Aws\Credentials\InstanceProfileProvider(),
]);
$bucket = 'hipaa-formsubmissions-mkadvantage';

try {
    $GLOBALS['pdo_hipaa']->beginTransaction();

    // Fetch the entries we are archiving (join fd to confirm tenant)
    $q = $GLOBALS['pdo_hipaa']->prepare("
        SELECT fe.entry_uuid, fe.form_uuid, fe.submission_data, fe.schema_snapshot, fe.title_snapshot, fe.submitted_at,
               fd.user_uuid AS owner_user_uuid
          FROM form_entries fe
          JOIN form_definitions fd ON fd.form_uuid = fe.form_uuid
         WHERE fe.entry_uuid IN ($placeholders)
    ");
    $q->execute($uuids);
    $rows = $q->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) {
        throw new Exception('No matching entries found');
    }

    foreach ($rows as $row) {
        if (empty($row['owner_user_uuid']) || $row['owner_user_uuid'] !== $user_uuid) {
            // Optional: enforce tenant/ACL per your rules
            continue;
        }

        $entry_uuid      = $row['entry_uuid'];
        $form_uuid       = $row['form_uuid'];
        $submitted_at    = $row['submitted_at'] ?? null;

        // Normalize submission_data to array
        $submission_data = json_decode($row['submission_data'] ?? '[]', true);
        if (!is_array($submission_data)) $submission_data = [];

        // Normalize schema_snapshot to array|null
        $schema_snapshot = $row['schema_snapshot'] ? json_decode($row['schema_snapshot'], true) : null;
        $title_snapshot  = $row['title_snapshot'] ?? null;

        // Build S3 key (partition by YYYY-MM)
        $ym    = $submitted_at ? date('Y-m', strtotime($submitted_at)) : date('Y-m');
        $s3Key = "forms/$form_uuid/$ym/$entry_uuid.json";

        // Build archive payload (self-contained)
        $payload = [
            'submission_data' => $submission_data,
            'schema_snapshot' => $schema_snapshot,   // may be null on very old entries
            'title_snapshot'  => $title_snapshot,
            'meta'            => [
                'form_uuid'    => $form_uuid,
                'entry_uuid'   => $entry_uuid,
                'submitted_at' => $submitted_at,
            ],
        ];

        // Upload to S3 (enable bucket versioning for safety if possible)
        $s3->putObject([
            'Bucket'               => $bucket,
            'Key'                  => $s3Key,
            'Body'                 => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'ContentType'          => 'application/json',
            'ServerSideEncryption' => 'AES256',
        ]);

        // Build a human-readable excerpt safely
        $excerptPieces = [];
        $walker = function ($val, $key = null) use (&$walker, &$excerptPieces) {
            $prefix = $key !== null ? ($key . ': ') : '';
            if ($val === null) return;
            if (is_scalar($val)) {
                $excerptPieces[] = $prefix . ($val === true ? 'Yes' : ($val === false ? 'No' : (string)$val));
                return;
            }
            if (is_array($val)) {
                foreach ($val as $k => $v) $walker($v, is_string($k) ? $k : null);
                return;
            }
            $encoded = json_encode($val, JSON_UNESCAPED_UNICODE);
            if ($encoded !== false) $excerptPieces[] = $prefix . $encoded;
        };
        // Prefer a few specific fields if you want; otherwise walk all:
        $walker($submission_data);
        $excerpt = trim(mb_substr(implode(' ', $excerptPieces), 0, 1000));

        // Upsert archive row
        $ins = $GLOBALS['pdo_hipaa']->prepare("
            INSERT INTO form_archived_entries
                (entry_uuid, form_uuid, user_uuid, submission_data_excerpt, s3_path, submitted_at, archived_at)
            VALUES
                (:entry_uuid, :form_uuid, :user_uuid, :excerpt, :s3_path, :submitted_at, NOW())
            ON DUPLICATE KEY UPDATE
                submission_data_excerpt = VALUES(submission_data_excerpt),
                s3_path                 = VALUES(s3_path),
                archived_at             = VALUES(archived_at),
                submitted_at            = VALUES(submitted_at)
        ");
        $ins->execute([
            ':entry_uuid'   => $entry_uuid,
            ':form_uuid'    => $form_uuid,
            ':user_uuid'    => $user_uuid,
            ':excerpt'      => $excerpt,
            ':s3_path'      => $s3Key,
            ':submitted_at' => $submitted_at,
        ]);

        // Mark entry archived (if you keep the flag on the live table)
        $GLOBALS['pdo_hipaa']->prepare("
            UPDATE form_entries SET archived = 'y', archived_at = NOW() WHERE entry_uuid = :e
        ")->execute([':e' => $entry_uuid]);
    }

    MKALogger::log('submissions_archived', [
        'entry_uuids' => $uuids,
        'user_uuid'   => $user_uuid
    ]);

    $GLOBALS['pdo_hipaa']->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($GLOBALS['pdo_hipaa']->inTransaction()) {
        $GLOBALS['pdo_hipaa']->rollBack();
    }
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
