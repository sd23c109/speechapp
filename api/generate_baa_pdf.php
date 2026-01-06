<?php
  <?php
require_once '../vendor/autoload.php'; // Adjust path if needed
require_once '../bootstrap.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$user_uuid = $_SESSION['user_data']['user_uuid'] ?? null;

if (!$user_uuid) {
    http_response_code(403);
    exit('Unauthorized');
}

// Fetch BAA data
$stmt = $GLOBALS['pdo_hipaa']->prepare("SELECT * FROM baa_acceptance WHERE user_uuid = :uuid LIMIT 1");
$stmt->execute([':uuid' => $user_uuid]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    exit('No BAA on file.');
}

// Render signature as base64
$signatureDataUrl = 'data:image/png;base64,' . base64_encode($data['signature_data']);

// Create PDF HTML
$html = "
<h2>Business Associate Agreement</h2>
<p><strong>Covered Entity:</strong> {$data['covered_entity']}</p>
<p><strong>Business Type:</strong> {$data['business_type']}</p>
<p><strong>State/Province:</strong> {$data['state_province']}</p>
<p><strong>Representative Name:</strong> {$data['representative_name']}</p>
<p><strong>Representative Title:</strong> {$data['representative_title']}</p>
<p><strong>Accepted At:</strong> {$data['accepted_at']}</p>
<p><strong>Signature:</strong></p>
<img src='{$signatureDataUrl}' style='max-width:300px; border:1px solid #ccc;' />
";

// Generate PDF
$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Stream to browser
$dompdf->stream("Signed_BAA.pdf", ["Attachment" => false]);

?>
