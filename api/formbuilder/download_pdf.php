<?php
session_start();
require_once '../../bootstrap.php';
require_once '/opt/mka/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;
use MKA\Formbuilder\FormFormatter;
use MKA\Log\MKALogger;

$form_uuid  = $_GET['form_uuid']  ?? '';
$entry_uuid = $_GET['entry_uuid'] ?? '';

if (!$form_uuid || !$entry_uuid) {
    http_response_code(400);
    die("Missing parameters.");
}

$formatter = new FormFormatter();

try {
    $pdo = $GLOBALS['pdo_hipaa'];

    // Fetch submission and form definition
    $stmt = $pdo->prepare("
        SELECT fe.submission_data, fd.form_title, fe.submitted_at, fd.fields
        FROM mka_forms.form_entries fe
        LEFT JOIN form_definitions fd ON fe.form_uuid = fd.form_uuid
        WHERE fe.form_uuid = :form_uuid AND fe.entry_uuid = :entry_uuid
        LIMIT 1
    ");

    $stmt->execute([
            ':form_uuid'  => $form_uuid,
            ':entry_uuid' => $entry_uuid
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        http_response_code(404);
        die("No submission found.");
    }

    $submission  = $formatter->formatSubmissionForReview($row['submission_data'], $row['fields']);
    $formTitle   = $row['form_title'] ?: 'Form Submission';
    $submitTime  = $row['submitted_at'] ?? '';

} catch (Throwable $e) {
    error_log("PDF load error: " . $e->getMessage());
    http_response_code(500);
    die("Error loading submission.");
}

MKALogger::log('form_submission_download', [
        'form_uuid'  => $form_uuid,
        'entry_uuid' => $entry_uuid,
        'user_uuid'  => $_SESSION['user_data']['user_uuid'] ?? null
]);

// --- DOMPDF OPTIONS: persistent cache, default font, chroot ---
$dompdfCacheRoot = '/opt/mka/storage/dompdf';
$dompdfTmp  = $dompdfCacheRoot . '/tmp';
$dompdfFonts = $dompdfCacheRoot . '/fonts';

// Ensure dirs exist (in case prep step wasn't run)
foreach ([$dompdfCacheRoot, $dompdfTmp, $dompdfFonts] as $p) {
    if (!is_dir($p)) {
        @mkdir($p, 0750, true);
    }
}

$options = new Options();
$options->set('isRemoteEnabled', true);             // allow remote CSS/images if used
$options->set('defaultFont', 'DejaVu Sans');        // robust built-in font
$options->set('chroot', '/opt/mka');                // allow access to /opt/mka/* paths
$options->set('tempDir',  $dompdfTmp);
$options->set('fontDir',  $dompdfFonts);
$options->set('fontCache',$dompdfFonts);

// --- Build HTML ---
ob_start();
?>
    <!doctype html>
    <html>
    <head>
        <meta charset="utf-8">
        <style>
            body { font-family: "DejaVu Sans", sans-serif; font-size: 12px; color: #111; }
            h2 { text-align: center; margin: 0 0 12px; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #ccc; padding: 8px; vertical-align: top; }
            th { width: 28%; background: #f7f7f7; text-align: left; }
            img.signature { max-height: 80px; }
        </style>
    </head>
    <body>
    <h2><?= htmlspecialchars($formTitle) ?></h2>
    <table>
        <?php foreach ($submission as $field): ?>
            <tr>
                <th><?= htmlspecialchars($field['label'] ?? '') ?></th>
                <td>
                    <?php
                    $type  = $field['type']  ?? '';
                    $value = $field['value'] ?? '';

                    // Files & signatures saved locally under /opt/mka/storage
                    if (in_array($type, ['file', 'signature'], true) && is_array($value)) {
                        $storedPath   = $value['stored_path']   ?? '';
                        $originalName = $value['original_name'] ?? ($storedPath ? basename($storedPath) : 'file');

                        if ($storedPath && is_string($storedPath) && file_exists($storedPath)) {
                            if ($type === 'signature') {
                                // Embed signature image inline (base64)
                                $data = @file_get_contents($storedPath);
                                if ($data !== false) {
                                    $ext = strtolower(pathinfo($storedPath, PATHINFO_EXTENSION));
                                    $mime = ($ext === 'jpg') ? 'jpeg' : $ext; // jpg->jpeg
                                    $b64  = base64_encode($data);
                                    echo "<img class='signature' src='data:image/{$mime};base64,{$b64}' alt='signature'>";
                                } else {
                                    echo "<em>Signature could not be read</em>";
                                }
                            } else {
                                // Regular file: show filename only (no link in PDF)
                                echo htmlspecialchars($originalName);
                            }
                        } else {
                            echo "<em>File not found</em>";
                        }
                    } elseif (is_array($value)) {
                        echo nl2br(htmlspecialchars(implode(', ', $value)));
                    } else {
                        echo nl2br(htmlspecialchars((string)$value));
                    }
                    ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <tr>
            <th>Submit Time</th>
            <td><?= htmlspecialchars($submitTime) ?></td>
        </tr>
    </table>
    </body>
    </html>
<?php
$html = ob_get_clean();

// --- Render & stream ---
try {
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $filename = "submission_" . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $entry_uuid) . ".pdf";
    $dompdf->stream($filename, ['Attachment' => true]);

} catch (Throwable $e) {
    error_log("PDF render/stream error: " . $e->getMessage());
    http_response_code(500);
    echo "PDF generation failed.";
}
