<?php
// secure_file.php

require_once '../../bootstrap.php';

// === INPUTS ===============================================================
$formUuid  = $_GET['form_uuid']  ?? '';
$entryUuid = $_GET['entry_uuid'] ?? '';
$fieldId   = $_GET['field_id']   ?? '';
$filename  = $_GET['filename']   ?? null;

// Optional rendering hint to fix old JPEG signatures (alpha -> black)
$render = $_GET['render'] ?? '';           // e.g., 'png'
$bg     = $_GET['bg']     ?? 'white';      // 'white' | 'transparent'

// === VALIDATION ===========================================================
$uuidRe   = '/^[a-f0-9-]{36}$/i';
$fieldRe  = '/^[a-z0-9_-]+$/i';

if (!preg_match($uuidRe, $formUuid) || !preg_match($uuidRe, $entryUuid) || !preg_match($fieldRe, $fieldId)) {
    http_response_code(400);
    exit('Bad request');
}

$baseDir = "/opt/mka/storage/forms/$formUuid/$entryUuid";
if (!is_dir($baseDir)) {
    http_response_code(404);
    exit('Not found');
}

// TODO: Enforce HIPAA authorization here (ensure current user is allowed).
// denyIfNotAuthorized($formUuid, $entryUuid);

// === RESOLVE FILE =========================================================
$fullPath = null;

// Try exact filename if provided and safe
if ($filename !== null) {
    if (!preg_match('/^[A-Za-z0-9._-]+$/', $filename)) {
        http_response_code(400);
        exit('Bad filename');
    }
    $candidate = $baseDir . '/' . $filename;
    if (is_file($candidate)) {
        $fullPath = $candidate;
    }
}

// Fallback by field prefix/pattern
if ($fullPath === null) {
    $matches = array_merge(
        glob($baseDir . '/' . $fieldId . '_*', GLOB_NOSORT) ?: [],
        glob($baseDir . '/' . $fieldId . '.*', GLOB_NOSORT) ?: []
    );

    if (!$matches) {
        http_response_code(404);
        exit('File not found for field');
    }

    // Keep only common/allowed types (prefer images)
    $allowed = ['png','webp','jpeg','jpg','gif','pdf'];
    $candidates = [];
    foreach ($matches as $m) {
        $ext = strtolower(pathinfo($m, PATHINFO_EXTENSION));
        if (in_array($ext, $allowed, true)) {
            $candidates[] = $m;
        }
    }
    if (!$candidates) {
        // If nothing matched allowed list, use all matches
        $candidates = $matches;
    }

    // Prefer extension (images first) then newest by mtime
    $extRank = ['png'=>1,'webp'=>2,'jpeg'=>3,'jpg'=>3,'gif'=>4,'pdf'=>5];
    usort($candidates, function($a, $b) use ($extRank) {
        $ea = strtolower(pathinfo($a, PATHINFO_EXTENSION));
        $eb = strtolower(pathinfo($b, PATHINFO_EXTENSION));
        $ra = $extRank[$ea] ?? 99;
        $rb = $extRank[$eb] ?? 99;
        if ($ra === $rb) {
            return filemtime($b) <=> filemtime($a); // newer first
        }
        return $ra <=> $rb;
    });

    $fullPath = $candidates[0];
}

// Debug headers (safe; no PHI)
header('X-MKA-Served-Basename: ' . basename($fullPath));
header('X-MKA-Served-Ext: ' . strtolower(pathinfo($fullPath, PATHINFO_EXTENSION)));

// === OPTIONAL ON-THE-FLY RENDERING =======================================
// If caller asks for PNG and source is JPEG, composite onto white (or transparent) background.
// This fixes old signatures saved as JPEG from a transparent canvas (alpha -> black).
if ($render === 'png' && preg_match('/\.(jpe?g)$/i', $fullPath)) {
    if (function_exists('imagecreatefromjpeg')) {
        $src = @imagecreatefromjpeg($fullPath);
        if ($src) {
            $w = imagesx($src);
            $h = imagesy($src);
            $dst = imagecreatetruecolor($w, $h);

            // Background fill
            $bgColor = imagecolorallocate($dst, 255, 255, 255); // default white
            if (strtolower($bg) === 'transparent') {
                imagesavealpha($dst, true);
                $bgColor = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            }
            imagefill($dst, 0, 0, $bgColor);

            // Composite
            imagecopy($dst, $src, 0, 0, 0, 0, $w, $h);

            // Headers for PNG stream
            header('Content-Type: image/png');
            header('Content-Disposition: inline');
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: 0');
            header('X-MKA-Rendered-From: ' . basename($fullPath));

            imagepng($dst);
            imagedestroy($dst);
            imagedestroy($src);
            exit;
        }
        // If GD fails, fall through to normal serving.
    }
}

// === NORMAL STREAMING =====================================================
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = $finfo ? finfo_file($finfo, $fullPath) : null;
if ($finfo) finfo_close($finfo);

if (!$mime) {
    $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
    $mime = match ($ext) {
        'png'  => 'image/png',
        'jpg', 'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'webp' => 'image/webp',
        'pdf'  => 'application/pdf',
        default => 'application/octet-stream',
    };
}

header('Content-Type: ' . $mime);
header('Content-Disposition: inline'); // no filename to avoid PHI in headers
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$fp = fopen($fullPath, 'rb');
if ($fp === false) {
    http_response_code(500);
    exit('Failed to open file');
}
fpassthru($fp);
fclose($fp);