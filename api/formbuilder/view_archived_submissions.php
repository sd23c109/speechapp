<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../bootstrap.php';
require_once '/opt/mka/vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use MKA\Formbuilder\FormFormatter;

$formatter = new FormFormatter();

// ✅ Auth check
if (!isset($_SESSION['user_data'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$form_uuid  = $_POST['form_uuid']  ?? '';
$entry_uuid = $_POST['entry_uuid'] ?? '';
$user_uuid  = $_SESSION['user_data']['user_uuid'] ?? '';

if (!$form_uuid || !$entry_uuid) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing form_uuid or entry_uuid']);
    exit;
}

// 🔍 Look up archive metadata + live snapshots for fallback
$stmt = $GLOBALS['pdo_hipaa']->prepare("
    SELECT
        fae.s3_path,
        fae.submitted_at,
        fd.fields                            AS fd_fields,
        fd.form_title                        AS fd_form_title,
        fe.schema_snapshot                   AS fe_schema_snapshot,
        fe.title_snapshot                    AS fe_title_snapshot
    FROM form_archived_entries fae
    JOIN form_definitions fd
      ON fae.form_uuid = fd.form_uuid
    LEFT JOIN form_entries fe
      ON fe.form_uuid  = fae.form_uuid
     AND fe.entry_uuid = fae.entry_uuid
   WHERE fae.form_uuid  = :form_uuid
     AND fae.entry_uuid = :entry_uuid
     AND fd.user_uuid   = :user_uuid
");
$stmt->execute([
    ':form_uuid'  => $form_uuid,
    ':entry_uuid' => $entry_uuid,
    ':user_uuid'  => $user_uuid
]);

$data = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$data || empty($data['s3_path'])) {
    http_response_code(404);
    echo json_encode(['error' => 'Archived submission not found']);
    exit;
}

// 🪣 S3 read
$s3 = new S3Client([
    'region'      => 'us-east-2',
    'version'     => 'latest',
    'http'        => ['verify' => true],
    'credentials' => new Aws\Credentials\InstanceProfileProvider(),
]);

try {
    $result         = $s3->getObject([
        'Bucket' => 'hipaa-formsubmissions-mkadvantage',
        'Key'    => $data['s3_path'],
    ]);
    $archiveJsonStr = (string)$result['Body'];
} catch (AwsException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load archived data from S3', 'details' => $e->getAwsErrorMessage()]);
    exit;
}

// 🔎 Archive may be:
//  A) legacy: raw submission_data object/array
//  B) new:    { submission_data, schema_snapshot, title_snapshot }
$archive = json_decode($archiveJsonStr, true);

// Normalize shapes
$submission_data = [];
$s3_schema_snapshot = null;
$s3_title_snapshot  = null;

if (is_array($archive)) {
    if (array_key_exists('submission_data', $archive)) {
        $submission_data    = $archive['submission_data'];
        $s3_schema_snapshot = $archive['schema_snapshot'] ?? null;
        $s3_title_snapshot  = $archive['title_snapshot']  ?? null;
    } else {
        $submission_data = $archive; // legacy
    }
} else {
    $submission_data = [];
}

if (is_string($submission_data)) {
    $tmp = json_decode($submission_data, true);
    if (is_array($tmp)) {
        $submission_data = $tmp;
    }
}

// 🧭 Choose schema (prefer S3 → entry snapshot → current fd.fields)
if ($s3_schema_snapshot !== null) {
    $schemaRaw = json_encode($s3_schema_snapshot, JSON_UNESCAPED_UNICODE);
} elseif (!empty($data['fe_schema_snapshot'])) {
    $schemaRaw = $data['fe_schema_snapshot'];
} else {
    $schemaRaw = $data['fd_fields'] ?? '[]';
}

$schemaArr = json_decode($schemaRaw, true);

// Flat→rows wrapper (back-compat)
if (is_array($schemaArr) && $schemaArr && isset($schemaArr[0]) && is_array($schemaArr[0]) && isset($schemaArr[0]['id'])) {
    $schemaForFormatter = json_encode([$schemaArr], JSON_UNESCAPED_UNICODE);
} else {
    $schemaForFormatter = $schemaRaw;
}

// 🧩 Format fields
$submissionJsonForFormatter = json_encode($submission_data, JSON_UNESCAPED_UNICODE);
$fields = $formatter->formatSubmissionForReview($submissionJsonForFormatter, $schemaForFormatter);

// Prefer archived title snapshot → entry title snapshot → current form title
if ($s3_title_snapshot !== null && $s3_title_snapshot !== '') {
    $form_title = $s3_title_snapshot;
} elseif (!empty($data['fe_title_snapshot'])) {
    $form_title = $data['fe_title_snapshot'];
} else {
    $form_title = $data['fd_form_title'] ?? 'Form Submission';
}

$submitted_at = $data['submitted_at'] ?? '';

// 🔧 Render HTML
function formatSubmissionAsHTML(array $fields, string $form_uuid = '', string $entry_uuid = '', string $submitted_at = ''): string {
    $html = '<div class="container-fluid submission-detail">';

    foreach ($fields as $field) {
        $type     = $field['type']     ?? '';
        $label    = $field['label']    ?? '';
        $value    = $field['value']    ?? '';
        $field_id = $field['field_id'] ?? '';

        if (in_array($type, ['separator', 'paragraph'], true)) continue;

        if (preg_match('/^h[1-6]$/', $type)) {
            $html .= '<h4 class="mt-4 mb-3 text-primary fw-bold">' . htmlspecialchars($label) . '</h4>';
            continue;
        }

        $html .= '<div class="row mb-3 align-items-center">';
        $html .= '<div class="col-sm-6 fw-bold text-muted">' . htmlspecialchars($label) . '</div>';
        $html .= '<div class="col-sm-6">';

        if (in_array($type, ['signature','image','file'], true)) {
            $isSignature = ($type === 'signature');

            $pickBasename = function($val) {
                if (is_string($val) && $val !== '') return $val;
                if (is_array($val)) {
                    if (!empty($val['stored_name'])   && is_string($val['stored_name']))   return $val['stored_name'];
                    if (!empty($val['saved_name'])    && is_string($val['saved_name']))    return $val['saved_name'];
                    if (!empty($val['stored_path'])   && is_string($val['stored_path']))   return basename($val['stored_path']);
                    if (!empty($val['original_name']) && is_string($val['original_name'])) return $val['original_name'];
                }
                return null;
            };

            $buildUrl = function (?string $basename, bool $forcePng = false) use ($form_uuid, $entry_uuid, $field_id, $submitted_at): string {
                $base = getSecureProxyUrl($form_uuid, $entry_uuid, $field_id, $basename, $submitted_at, $forcePng);
                return htmlspecialchars($base, ENT_QUOTES);
            };

            $looksLikeImageExt = fn (?string $name) =>
                is_string($name) && preg_match('/\.(png|jpe?g|gif|webp)$/i', $name);

            $renderFileLink = function (string $url, string $text) use (&$html) {
                $html .= '<a href="' . $url . '" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary me-2 mt-1">'
                    . '<i class="fa fa-download"></i> ' . htmlspecialchars($text) . '</a>';
            };

            $renderImage = function (string $url, bool $isSignature) use (&$html) {
                $style = 'max-width:100%;height:auto;border:1px solid #ccc;';
                if ($isSignature) $style = 'background:#fff;' . $style;
                $html .= '<img class="mka-form-image" src="' . $url . '" alt="image" style="' . $style . '" />';
            };

            // A) Legacy data URL signature
            if (is_string($value) && str_starts_with($value, 'data:image/')) {
                $renderImage(htmlspecialchars($value, ENT_QUOTES), true);
                $html .= '</div></div>';
                continue;
            }

            // B) Single string => treat as basename
            if (is_string($value) && $value !== '') {
                $url = $buildUrl($value, $isSignature);
                if ($isSignature || $looksLikeImageExt($value)) $renderImage($url, $isSignature);
                else $renderFileLink($url, $value);
                $html .= '</div></div>';
                continue;
            }

            // C) Array payload (assoc or multi-file)
            if (is_array($value)) {
                $files = is_assoc($value) ? [$value] : $value;
                foreach ($files as $file) {
                    $basename = $pickBasename($file);
                    $url      = $buildUrl($basename, $isSignature);
                    $shouldImage = ($isSignature || $looksLikeImageExt($basename));
                    if ($shouldImage) $renderImage($url, $isSignature);
                    else $renderFileLink($url, (string)($basename ?: ($file['original_name'] ?? 'download')));
                }
                $html .= '</div></div>';
                continue;
            }

            // D) No value → auto-resolve by field only
            $url = $buildUrl(null, $isSignature);
            if ($isSignature || $type === 'image') $renderImage($url, $isSignature);
            else $renderFileLink($url, 'download');

            $html .= '</div></div>';
            continue;
        }

        if (in_array($type, ['select-multiple', 'checkbox'], true) && is_array($value)) {
            $html .= '<ul class="mb-0">';
            foreach ($value as $v) $html .= '<li>' . htmlspecialchars((string)$v) . '</li>';
            $html .= '</ul>';
        } else {
            $renderedValue = is_array($value) ? implode(', ', array_map('strval', $value)) : (string)$value;
            $html .= '<p class="mb-0">' . nl2br(htmlspecialchars($renderedValue)) . '</p>';
        }

        $html .= '</div></div>'; // end row
    }

    $html .= '</div>';
    return $html;
}

function getSecureProxyUrl(
    string $form_uuid,
    string $entry_uuid,
    string $field_id,
    ?string $basename,
    string $submitted_at = '',
    bool $forcePng = false
) : string {
    $yearMonth = $submitted_at ? date('Y-m', strtotime($submitted_at)) : '';
    $url = "/formbuilder/secure_file_archived.php"
        . "?form_uuid=" . urlencode($form_uuid)
        . "&entry_uuid=" . urlencode($entry_uuid)
        . "&field_id="   . urlencode($field_id);

    if ($basename && preg_match('/^[A-Za-z0-9._-]+$/', $basename)) {
        $url .= "&filename=" . rawurlencode($basename);
    }
    if ($yearMonth !== '') {
        $url .= "&year_month=" . urlencode($yearMonth);
    }
    if ($forcePng) {
        $url .= "&render=png&bg=white";
    }
    return $url;
}

function is_assoc(array $arr): bool {
    return array_keys($arr) !== range(0, count($arr) - 1);
}

header('Content-Type: application/json');
echo json_encode([
    'form_uuid'       => $form_uuid,
    'entry_uuid'      => $entry_uuid,
    'html'            => formatSubmissionAsHTML($fields,$form_uuid,$entry_uuid,$submitted_at),
    'submission_title'=> $form_title,
    'submitted_at'    => $submitted_at,
], JSON_UNESCAPED_UNICODE);
