<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('mka_public');
    session_start();
}
require_once '../../bootstrap.php';
require_once '/opt/mka/vendor/autoload.php';

use MKA\Log\MKALogger;
require_once '/opt/mka/core/Email/BrevoMailer.php';
use MKA\Email\BrevoMailer;

header('Content-Type: application/json');

/**
 * Helper: build a compact schema snapshot from fields JSON
 */

/*
function build_schema_snapshot(?string $fieldsJson): string {
    $fields = json_decode($fieldsJson ?? '[]', true);
    if (!is_array($fields)) {
        $fields = [];
    }

    // Your form editor stores rows-of-fields; flatten safely if needed
    // Accept both flat arrays and 2D row arrays.
    $flat = [];
    $pushField = function($f) use (&$flat) {
        if (!is_array($f)) return;
        $flat[] = [
            'id'      => $f['id']      ?? null,
            'type'    => $f['type']    ?? null,
            'label'   => $f['label']   ?? null,
            'options' => $f['options'] ?? null,
            'colSize' => $f['colSize'] ?? null,
            'order'   => $f['order']   ?? null,
        ];
    };

    if ($fields && isset($fields[0]) && is_array($fields[0])) {
        // Could be 2D rows: [[{field},{field}], [{field}]]
        $is2d = array_reduce($fields, function($acc, $row) { return $acc || (is_array($row) && isset($row[0]) && is_array($row[0])); }, false);
        if ($is2d) {
            foreach ($fields as $row) {
                if (is_array($row)) {
                    foreach ($row as $f) { $pushField($f); }
                }
            }
        } else {
            // Already flat
            foreach ($fields as $f) { $pushField($f); }
        }
    }

    return json_encode($flat, JSON_UNESCAPED_UNICODE);
}
*/

function build_schema_snapshot(?string $fieldsJson): string {
    return (string)($fieldsJson ?? '[]');
}


/**
 * UUID v4
 */
function generate_uuid_v4() {
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

try {
    if (empty($_POST['form_uuid'])) {
        throw new Exception("Missing form_uuid");
    }
    error_log('FILES DEBUG: ' . print_r($_FILES, true));
    error_log('POST DEBUG: '  . print_r($_POST, true));

    $form_uuid   = $_POST['form_uuid'];
    $form_version = (int)($_POST['form_version'] ?? 1); // ← from hidden field you added
    $entry_uuid  = generate_uuid_v4();
    $user_uuid   = $_SESSION['user_data']['user_uuid'] ?? '00000000-0000-0000-0000-000000000000';

    // Fetch form meta/definition NOW (so we can snapshot)
    // Use form_uuid (don’t rely on user_uuid match for public submits)
    $stmt = $GLOBALS['pdo_hipaa']->prepare("
        SELECT form_title, fields, email_recipients
        FROM form_definitions
        WHERE form_uuid = :formuuid
        LIMIT 1
    ");
    $stmt->execute([':formuuid' => $form_uuid]);
    $formMeta = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $formTitleAtRenderTime = $formMeta['form_title'] ?? 'Form Submission';
    $fieldsJson            = $formMeta['fields']     ?? '[]';
    $schema_snapshot       = build_schema_snapshot($fieldsJson);

    // Storage directory for any files/signatures
    $storagePath = "/opt/mka/storage/forms/{$form_uuid}/{$entry_uuid}/";
    if (!is_dir($storagePath)) {
        mkdir($storagePath, 0775, true);
    }

    // Build submission_data (exclude control keys)
    $submission_data = [];
    foreach ($_POST as $key => $value) {
        if ($key === 'form_uuid' || $key === 'form_version') continue;

        // Base64 image (e.g., signature pad)
        // Base64 image (e.g., signature pad)
        if (is_string($value) && strpos($value, 'data:image/') === 0) {
            if (preg_match('#^data:image/(png|jpeg|jpg|gif|webp);base64,(.+)$#i', $value, $m)) {
                $mimeExt = strtolower($m[1]); // incoming hint; we'll still re-encode to PNG
                $rawB64  = $m[2];
                $bin     = base64_decode($rawB64, true);
                if ($bin === false) {
                    throw new Exception("Base64 decode failed for $key");
                }

                // Try to create an image from whatever we got (png or jpeg etc)
                $im = @imagecreatefromstring($bin);
                if ($im !== false) {
                    $w = imagesx($im); $h = imagesy($im);

                    // Composite onto white background to avoid black boxes
                    $dst = imagecreatetruecolor($w, $h);
                    $white = imagecolorallocate($dst, 255, 255, 255);
                    imagefill($dst, 0, 0, $white);
                    imagealphablending($dst, true);
                    imagesavealpha($im, true);
                    imagecopy($dst, $im, 0, 0, 0, 0, $w, $h);

                    // Always save signatures as PNG going forward
                    $filename = $key . '_' . uniqid('', true) . '.png';
                    $savePath = $storagePath . $filename;

                    // Write PNG to disk
                    imagepng($dst, $savePath);
                    imagedestroy($dst);
                    imagedestroy($im);

                    // Restrictive perms
                    @chmod($savePath, 0600);

                    // Store both path and basename (basename is handy for viewers)
                    $submission_data[$key] = [
                        'stored_path' => $savePath,
                        'stored_name' => $filename,
                        'type'        => 'signature',
                    ];
                } else {
                    // Fallback: if GD can't parse it, just dump bytes as PNG
                    $filename = $key . '_' . uniqid('', true) . '.png';
                    $savePath = $storagePath . $filename;
                    if (file_put_contents($savePath, $bin) === false) {
                        throw new Exception("Failed to save raw signature for $key");
                    }
                    @chmod($savePath, 0600);
                    $submission_data[$key] = [
                        'stored_path' => $savePath,
                        'stored_name' => $filename,
                        'type'        => 'signature',
                    ];
                }
            }
        } else {
            // Regular text/multi-select etc.
            $submission_data[$key] = $value;
        }
    }

    // Handle uploaded files
    // Handle uploaded files (support single and multiple)
    foreach ($_FILES as $key => $file) {
        // If it's a multiple file input, PHP gives arrays for each attribute
        $isMulti = is_array($file['name']);

        $names     = $isMulti ? $file['name']     : [$file['name']];
        $types     = $isMulti ? $file['type']     : [$file['type']];
        $tmp_names = $isMulti ? $file['tmp_name'] : [$file['tmp_name']];
        $errors    = $isMulti ? $file['error']    : [$file['error']];
        $sizes     = $isMulti ? $file['size']     : [$file['size']];

        $savedUris = []; // collect public URIs for this field

        foreach ($names as $i => $origName) {
            $err = $errors[$i] ?? UPLOAD_ERR_NO_FILE;
            if ($err !== UPLOAD_ERR_OK) {
                // error_log("UPLOAD SKIP {$key}[{$i}]: err={$err}");
                continue;
            }

            $tmp  = $tmp_names[$i] ?? '';
            $size = (int)($sizes[$i] ?? 0);
            if (!$tmp || $size <= 0) {
                // error_log("UPLOAD SKIP {$key}[{$i}]: empty tmp/size");
                continue;
            }

            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION) ?: 'png');
            if (!in_array($ext, ['png','jpg','jpeg','webp','gif','pdf'], true)) {
                // Tune as you wish
                // error_log("UPLOAD SKIP {$key}[{$i}]: ext={$ext}");
                continue;
            }

            // Sequential name avoids clashes: field-37_0.png, field-37_1.png...
            $safeName = $isMulti ? sprintf('%s_%d.%s', $key, $i, $ext)
                : sprintf('%s.%s', $key, $ext);
            $dest = $storagePath . $safeName;

            if (!is_dir($storagePath) && !mkdir($storagePath, 0775, true)) {
                throw new Exception("Failed to create storage path: {$storagePath}");
            }
            if (!move_uploaded_file($tmp, $dest)) {
                throw new Exception("Failed to move uploaded file for {$key}[{$i}]");
            }
            @chmod($dest, 0600);

            // If you have a /storage alias, expose as web URL; else use a download endpoint.
            $publicUri = "/storage/forms/{$form_uuid}/{$entry_uuid}/{$safeName}";
            $savedUris[] = $publicUri;
        }

        if (!empty($savedUris)) {
            // If multiple, store array; if single, store string for convenience
            $submission_data[$key] = (count($savedUris) === 1) ? $savedUris[0] : $savedUris;
        }
    }


    // Insert submission with version + snapshots
    $stmt = $GLOBALS['pdo_hipaa']->prepare("
        INSERT INTO form_entries
            (entry_uuid, form_uuid, user_uuid, submission_data, form_version, schema_snapshot, title_snapshot)
        VALUES
            (:entry_uuid, :form_uuid, :user_uuid, :submission_data, :form_version, :schema_snapshot, :title_snapshot)
    ");
    $stmt->execute([
        ':entry_uuid'      => $entry_uuid,
        ':form_uuid'       => $form_uuid,
        ':user_uuid'       => $user_uuid,
        ':submission_data' => json_encode($submission_data, JSON_UNESCAPED_UNICODE),
        ':form_version'    => $form_version,
        ':schema_snapshot' => $schema_snapshot,
        ':title_snapshot'  => $formTitleAtRenderTime,
    ]);

    MKALogger::log('form_submission_created', [
        'form_uuid'  => $form_uuid,
        'entry_uuid' => $entry_uuid,
        'user_uuid'  => $user_uuid
    ]);

    // Email notifications (unchanged, but now read from earlier $formMeta)
    $formTitle    = $formTitleAtRenderTime;
    $submittedAt  = date('Y-m-d H:i:s');
    $recipientList = array_filter(array_map('trim', explode(',', $formMeta['email_recipients'] ?? '')));

    if (!empty($recipientList)) {
        $subject = 'New Form Submission: ' . $formTitle;
        $html    = "
          <p><strong>New form submission from MKAdvantage!</strong></p>
          <p>A form titled <strong>{$formTitle}</strong> was submitted at <strong>{$submittedAt}</strong>.</p>
          <p><a href='https://app.mkadvantage.com/' target='_blank'>View submissions</a></p>
        ";
        BrevoMailer::send($recipientList, $subject, $html);
    }

    echo json_encode(['success' => true, 'entry_uuid' => $entry_uuid, 'msg' => $formMeta['submit_message'] ?? null]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage() ]);
}
