<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../bootstrap.php';
require_once '/opt/mka/vendor/autoload.php';
use MKA\Formbuilder\FormFormatter;
use MKA\Log\MKALogger;

$formatter = new FormFormatter();

// ✅ Ensure authenticated session (or token)
if (!isset($_SESSION['user_data'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$form_uuid  = $_REQUEST['form_uuid']  ?? '';
$entry_uuid = $_REQUEST['entry_uuid'] ?? '';

if (!$form_uuid || !$entry_uuid) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing form_uuid or entry_uuid']);
    exit;
}

// 🔒 Secure SQL
$stmt = $GLOBALS['pdo_hipaa']->prepare("
SELECT
  fe.submission_data,
  fe.schema_snapshot,
  fe.title_snapshot,
  fd.fields,
  fd.form_title,
  fe.submitted_at,
  fe.opened_on
FROM mka_forms.form_entries fe
LEFT JOIN form_definitions fd ON fe.form_uuid = fd.form_uuid
WHERE fe.form_uuid = :form_uuid AND fe.entry_uuid = :entry_uuid
");
$stmt->execute([
    ':form_uuid' => $form_uuid,
    ':entry_uuid' => $entry_uuid
]);

MKALogger::log('form_submission_viewed', [
    'form_uuid' => $form_uuid,
    'entry_uuid' => $entry_uuid,
    'user_uuid' => $_SESSION['user_data']['user_uuid']
]);

$data = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$data) {
    http_response_code(404);
    echo json_encode(['error' => 'Form submission not found']);
    exit;
}

// update opened_on so we have visual cue on form submission table
$stmt = $GLOBALS['pdo_hipaa']->prepare("
    UPDATE mka_forms.form_entries
       SET opened_on = CURRENT_TIMESTAMP()
     WHERE form_uuid = :form_uuid AND entry_uuid = :entry_uuid
");
$stmt->execute([
    ':form_uuid' => $form_uuid,
    ':entry_uuid' => $entry_uuid
]);

// Prefer per-entry snapshot; fall back to current fields
$schemaRaw = $data['schema_snapshot'] ?: $data['fields'];
$schemaArr = json_decode($schemaRaw, true);

// If snapshot is a flat list of fields (not rows), wrap it so the formatter sees rows
if (is_array($schemaArr) && $schemaArr && isset($schemaArr[0]) && is_array($schemaArr[0]) && isset($schemaArr[0]['id'])) {
    $schemaForFormatter = json_encode([$schemaArr], JSON_UNESCAPED_UNICODE);
} else {
    $schemaForFormatter = $schemaRaw; // already rows-based
}

// --- Build fields from payload + schema (robust for new + legacy) ---

// 1) decode payload and strip csrf
$payload = json_decode($data['submission_data'] ?? '[]', true) ?: [];
unset($payload['csrf_token']);

// 2) decode schema (prefer snapshot)
$schemaRaw = $data['schema_snapshot'] ?: $data['fields'];
$schemaArr = json_decode($schemaRaw, true) ?: [];

// helpers to read rows/fields regardless of shape
function mka_rows_from_def($def) {
    if (isset($def['rows']) && is_array($def['rows'])) return $def['rows'];      // { rows: [...] }
    if (is_array($def) && isset($def[0])) return $def;                            // [[field, ...], ...] or flat [field, ...]
    return [];
}
function mka_fields_from_row($rowLike) {
    if (isset($rowLike['fields']) && is_array($rowLike['fields'])) return $rowLike['fields']; // {fields:[...]}
    if (is_array($rowLike)) return $rowLike;                                                  // [...]
    return [];
}
function mka_build_label_map(array $schema): array {
    $map = [];
    foreach (mka_rows_from_def($schema) as $row) {
        foreach (mka_fields_from_row($row) as $f) {
            if (!is_array($f)) continue;
            $id   = $f['id']   ?? null;
            $type = $f['type'] ?? 'text';
            $lbl  = $f['label'] ?? '';
            if ($id) $map[$id] = ['label' => ($lbl !== '' ? $lbl : $id), 'type' => $type];
        }
    }
    return $map;
}

$labelMap = mka_build_label_map($schemaArr);

// 3) Build the normalized $fields array the renderer expects
$fields = [];
foreach ($payload as $fieldId => $value) {
    // paragraphs are display-only; they won't be in payload—skip nothing here.
    $meta = $labelMap[$fieldId] ?? ['label' => $fieldId, 'type' => ''];
    $fields[] = [
        'field_id' => $fieldId,
        'type'     => $meta['type'],
        'label'    => $meta['label'],
        'value'    => $value,
    ];
}

// 4) Fallback: if somehow we built nothing, try legacy formatter once
if (!$fields) {
    $fields = $formatter->formatSubmissionForReview($data['submission_data'], $schemaForFormatter);
}


// Prefer saved title; fall back to current definition title
$submission_title = $data['title_snapshot'] ?: $data['form_title'];
$submitted_at     = $data['submitted_at'];

function formatSubmissionAsHTML(array $fields, string $form_uuid = '', string $entry_uuid = ''): string {
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

        // --- File-like fields: signature/image/file ---
        if (in_array($type, ['signature','image','file'], true)) {
            $isSignature = ($type === 'signature');

            $buildUrl = function (?string $basename, bool $forcePng = false) use ($form_uuid, $entry_uuid, $field_id): string {
                $base = '/formbuilder/secure_file.php?form_uuid=' . urlencode($form_uuid)
                    . '&entry_uuid=' . urlencode($entry_uuid)
                    . '&field_id='   . urlencode($field_id);
                if ($basename && preg_match('/^[A-Za-z0-9._-]+$/', $basename)) {
                    $base .= '&filename=' . rawurlencode($basename);
                }
                if ($forcePng) $base .= '&render=png&bg=white';
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

            // B) Single string (basename)
            if (is_string($value) && $value !== '') {
                $url = $buildUrl($value, $isSignature);
                if ($isSignature || $looksLikeImageExt($value)) $renderImage($url, $isSignature);
                else $renderFileLink($url, $value);
                $html .= '</div></div>';
                continue;
            }

            // C) Array (assoc or multi-file)
            if (is_array($value)) {
                $files = is_assoc($value) ? [$value] : $value;
                foreach ($files as $file) {
                    $basename = $file['stored_name']  ?? $file['saved_name'] ??
                        $file['original_name'] ?? null;
                    $url = $buildUrl($basename, $isSignature);
                    $shouldImage = ($isSignature || $looksLikeImageExt($basename));
                    if ($shouldImage) $renderImage($url, $isSignature);
                    else $renderFileLink($url, (string)($basename ?: ($file['original_name'] ?? 'download')));
                }
                $html .= '</div></div>';
                continue;
            }

            // D) No value → auto-resolve by field only
            $url = $buildUrl(null, $isSignature);
            if ($isSignature) $renderImage($url, true);
            else $renderFileLink($url, 'download');

            $html .= '</div></div>';
            continue;
        }
        // --- End file-like handling ---

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

    $html .= '</div>'; // container
    return $html;
}

function is_assoc(array $arr): bool {
    return array_keys($arr) !== range(0, count($arr) - 1);
}

header('Content-Type: application/json');
echo json_encode([
    'form_uuid'       => $form_uuid,
    'entry_uuid'      => $entry_uuid,
    'html'            => formatSubmissionAsHTML($fields,$form_uuid,$entry_uuid),
    'submission_title'=> $submission_title,
    'submitted_at'    => $submitted_at,
], JSON_UNESCAPED_UNICODE);
