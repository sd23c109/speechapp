<?php
session_start();
require_once '../../bootstrap.php';
require_once '/opt/mka/vendor/autoload.php';
require_once '/opt/mka/core/Tasks/CheckSubscriptionLimits.php';
use MKA\Log\MKALogger;
use MKA\Tasks\CheckSubscriptionLimits;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);
// Fallback if someone posts form-encoded:
if ($data === null && !empty($_POST)) {
    $data = $_POST;
}

error_log('FORM IS SAVING THIS...');
error_log(json_encode($data, JSON_UNESCAPED_SLASHES));

// ---- Normalize incoming keys (accept old & new) ----
if (!isset($data['form_title']) && isset($data['title'])) {
    $data['form_title'] = $data['title'];
}
if (!isset($data['form_slug']) && isset($data['slug'])) {
    $data['form_slug'] = $data['slug'];
}
if (!isset($data['form_recipients']) && isset($data['email_recipients'])) {
    $data['form_recipients'] = $data['email_recipients'];
}
if (!isset($data['company_slug']) && isset($data['company'])) {
    $data['company_slug'] = $data['company'];
}

// Some clients send defaults as object; ensure array
if (isset($data['defaults']) && is_string($data['defaults'])) {
    $tmp = json_decode($data['defaults'], true);
    if (is_array($tmp)) $data['defaults'] = $tmp;
}

// ---- Decode fields_json properly ----
// Accept either "fields" (array) OR "fields_json" (stringified or array)
// Your front-end model currently posts: { rows: [...] }
$decodedFields = [];
if (isset($data['fields'])) {
    $decodedFields = is_string($data['fields']) ? (json_decode($data['fields'], true) ?: []) : $data['fields'];
} elseif (isset($data['fields_json'])) {
    if (is_string($data['fields_json'])) {
        $tmp = json_decode($data['fields_json'], true);
        $decodedFields = is_array($tmp) ? $tmp : [];
    } else {
        $decodedFields = is_array($data['fields_json']) ? $data['fields_json'] : [];
    }
}

// If the decoded shape has top-level "rows", store rows; otherwise assume it's already an array of rows
if (isset($decodedFields['rows']) && is_array($decodedFields['rows'])) {
    $fieldsWrapper = $decodedFields;                    // already { rows: [...] }
} else {
    // older path produced just an array of rows
    $fieldsWrapper = ['rows' => (is_array($decodedFields) ? $decodedFields : [])];
}

$customer_uuid = $user_uuid;
foreach ($fieldsWrapper['rows'] as &$row) {
    if (!isset($row['fields']) || !is_array($row['fields'])) continue;
    foreach ($row['fields'] as &$field) {
        $type = $field['type'] ?? '';
        if ($type === 'image' || $type === 'img') {
            $fid = $field['id'] ?? null;
            $src = $field['src'] ?? null;
            if ($src && $fid) {
                $field['src'] = moveImageToFinal($src, $customer_uuid, $form_uuid, $fid);
            }
        }
    }
}
unset($row, $field);

// Provide a consistent, de-nullified defaults object
$defaultsObj = isset($data['defaults']) && is_array($data['defaults']) ? $data['defaults'] : [];

// ---- Validate minimal required fields (use normalized keys) ----
if (empty($data) || empty($data['form_title'])) {
    echo json_encode(['status' => 'error', 'message' => 'Please open the form config and add a title.']);
    exit;
}


$user_uuid = $_SESSION['user_data']['user_uuid'] ?? null;
if (empty($user_uuid)) {
    // Log them out hard if no session
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
    exit;
}

$form_uuid    = $data['form_uuid'] ?? null;
$form_title   = $data['form_title'] ?? '';
$form_desc    = $data['description'] ?? '';
$form_slug    = $data['form_slug'] ?? '';
$company_slug = $data['company_slug'] ?? '';
$recipients   = $data['form_recipients'] ?? '';
$submit_msg   = $data['submit_message'] ?? '';

// Accept either "fields" OR "fields_json"
$fieldsArr    = $data['fields'] ?? $data['fields_json'] ?? [];
$defaultsObj  = $data['defaults'] ?? []; // may be array already

// ---------- Resolve or create form_uuid FIRST ----------
$is_update = false;

if (empty($form_uuid)) {
    // Check if this slug already exists for this company
    $stmt = $GLOBALS['pdo_hipaa']->prepare("
        SELECT form_uuid FROM form_definitions
        WHERE form_slug = :slug AND company_slug = :company_slug
        LIMIT 1
    ");
    $stmt->execute([':slug' => $form_slug, ':company_slug' => $company_slug]);
    $existingForm = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingForm) {
        $form_uuid = $existingForm['form_uuid'];
        $is_update = true;
        error_log('EXISTING FORM FOUND: ' . $form_uuid);
    } else {
        // Subscription gate BEFORE insert
        if (!CheckSubscriptionLimits::canCreateForm($user_uuid, 'hipaa_forms')) {
            echo json_encode(['status' => 'fail', 'message' => "You’ve reached your form limit for this plan. Please upgrade to add more."]);
            exit;
        }
        $form_uuid = generate_uuid_v4();
        $is_update = false;
        error_log('CREATING NEW FORM: ' . $form_uuid);
    }
} else {
    $is_update = true;
}

// ---------- Move images if any (ok if $fieldsArr is empty) ----------
$customer_uuid = $user_uuid;
foreach ($fieldsArr as &$row) {
    foreach ($row as &$field) {
        $type = $field['type'] ?? '';
        if ($type === 'image' || $type === 'img') {
            $fid = $field['id'] ?? null;
            $src = $field['src'] ?? null;
            if ($src && $fid) {
                $field['src'] = moveImageToFinal($src, $customer_uuid, $form_uuid, $fid);
            }
        }
    }
}
unset($row, $field);

// ---------- Upsert record (allow empty fields on first save) ----------
try {
    $pdo = $GLOBALS['pdo_hipaa'];

    if ($is_update) {
        $stmt = $pdo->prepare("
            UPDATE form_definitions SET
                form_title = :form_title,
                form_description = :form_description,
                form_slug = :form_slug,
                company_slug = :company_slug,
                email_recipients = :email_recipients,
                submit_message = :submit_message,
                fields = :fields,
                defaults = :defaults
            WHERE form_uuid = :form_uuid AND user_uuid = :user_uuid
        ");
        MKALogger::log('form_updated', ['form_uuid' => $form_uuid, 'user_uuid' => $user_uuid]);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO form_definitions (
                form_uuid, user_uuid, form_title, form_description, form_slug, company_slug, email_recipients, submit_message, fields, defaults
            ) VALUES (
                :form_uuid, :user_uuid, :form_title, :form_description, :form_slug, :company_slug, :email_recipients, :submit_message, :fields, :defaults
            )
        ");
        MKALogger::log('form_created', ['form_uuid' => $form_uuid, 'user_uuid' => $user_uuid]);
    }

    $stmt->execute([
        ':form_uuid'        => $form_uuid,
        ':user_uuid'        => $user_uuid,
        ':form_title'       => $form_title,
        ':form_description' => $form_desc,
        ':form_slug'        => $form_slug,
        ':company_slug'     => $company_slug,
        ':email_recipients' => $recipients,
        ':submit_message'   => $submit_msg,
        ':fields'           => json_encode($fieldsWrapper, JSON_UNESCAPED_SLASHES),
        ':defaults'         => json_encode(is_array($defaultsObj) ? $defaultsObj : [])
    ]);

    // Return a CONSISTENT success shape with the uuid (and optional version if you want)
    $resp = ['status' => 'success', 'form_uuid' => $form_uuid, 'message' => ($is_update ? 'Form updated' : 'Form created')];
    error_log(json_encode($resp));
    echo json_encode($resp);
    exit;

} catch (PDOException $e) {
    $message = ['status' => 'error', 'message' => 'DB error: ' . $e->getMessage()];
    error_log(json_encode($message));
    echo json_encode($message);
    exit;
}


function generate_uuid_v4() {
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000, // version 4
        mt_rand(0, 0x3fff) | 0x8000, // variant
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

function moveImageToFinal($src, $customer_uuid, $form_uuid, $field_id = null) {
    $base = "/opt/mka/storage/formbuilder/$customer_uuid/$form_uuid";
    $tempPath = "$base/temp/$field_id/";
    $finalPath = "$base/final/$field_id/";

    if (!is_dir($finalPath)) {
        mkdir($finalPath, 0750, true);
    }
    error_log('WHAT SRC AM I USING HERE????');
    error_log($src);
    $parts = parse_url($src);
    parse_str($parts['query'] ?? '', $queryParams);
    $filename = basename($queryParams['file'] ?? '');
     error_log('NOW WHAT IS FILENAME>..');
     error_log($filename);
    $tempFile = $tempPath . $filename;
    $finalFile = $finalPath . $filename;
    if (rename($tempFile, $finalFile)){
        return "/formbuilder/image.php?form_uuid=$form_uuid&field_id=$field_id&file=$filename";
    }

    return $src; // fallback if move fails
}




