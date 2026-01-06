<?php
session_name('mka_public');
session_start();
require_once '../../bootstrap.php';
require_once '../../vendor/autoload.php'; 
use MKA\Tasks\MobileDetectHelper;

$mdetect = new \MKA\Tasks\MobileDetect();
$isMobile = $mdetect->isMobile();

error_log('IS MOBILE IS...');
error_log($isMobile);

$company = $_GET['company'] ?? '';
$formSlug = $_GET['form'] ?? '';


/*
if (empty($_SESSION['csrf_public_token'])) {
    $_SESSION['csrf_public_token'] = bin2hex(random_bytes(32));
    setcookie('csrf_public_token', $_SESSION['csrf_public_token'], [
        'expires' => time() + 7200,
        'path' => '/',
        'secure' => true,
        'samesite' => 'Lax',
        'httponly' => false // must be accessible to JS if you're using it client-side
    ]);
}  */
// Defensive: bail out early if missing parameters
if (empty($company) || empty($formSlug)) {
    
    http_response_code(400);
    echo json_encode(['message' => "Invalid request: missing company or form slug."]);
    //echo "Invalid request: missing company or form slug.";
    exit;
    
}

$stmt = $pdo_hipaa->prepare("
    SELECT * 
    FROM form_definitions 
    WHERE form_slug = :form_slug AND company_slug = :company_slug
    LIMIT 1
");
$stmt->execute([
    'form_slug' => $formSlug,
    'company_slug' => $company
]);
$form = $stmt->fetch(PDO::FETCH_ASSOC);   

if (!$form) {
    http_response_code(404);
    echo "Form not found.";
    exit;
}

$form_uuid = $form['form_uuid'];

if (empty($form_uuid)) {
    header("Location: https://app.mkadvantage.com/dashboards/500_disabled_form.php");
    exit;
}

try {
    // Check if form exists and is active
    $stmt = $GLOBALS['pdo_hipaa']->prepare("
        SELECT is_active 
        FROM mka_forms.form_definitions 
        WHERE form_uuid = ? 
        LIMIT 1
    ");
    $stmt->execute([$form_uuid]);
    $status = $stmt->fetchColumn();

    if (!$status || $status !== 'active') {
        // Form either doesn't exist or is disabled
        header("Location: https://app.mkadvantage.com/dashboards/500_disabled_form.php?status=disabled");
        exit;
    }

    // If active, continue loading the form as normal below
    // ...

} catch (Exception $e) {
    error_log("Form load error for {$form_uuid}: " . $e->getMessage());
    header("Location: /dashboards/500_disabled_form.php?status=error");
    exit;
}

$fields = json_decode($form['fields'], true);
$title = htmlspecialchars($form['form_title']);
$form_uuid = htmlspecialchars($form['form_uuid']);
$_SESSION['user_data']['user_uuid'] = htmlspecialchars($form['user_uuid']);

function extractGoogleFonts($fields) {
    $fonts = ["Anton", "Arimo", "Arial", "Asap", "Barlow", "Bebas Neue", "Cabin", "Cormorant Garamond",
            "Courier New", "Crimson Text", "DM Sans", "Exo 2", "Fira Sans", "Heebo", "Hind", "IBM Plex Sans",
            "Inconsolata", "Inter", "Josefin Sans", "Karla", "Lato", "Libre Franklin", "Manrope", "Merriweather",
            "Montserrat", "Mukta", "Muli", "Mulish", "Noto Sans", "Noto Serif", "Nunito", "Open Sans", "Oswald",
            "Overpass", "Playfair Display", "Poppins", "Prompt", "PT Sans", "Quicksand", "Raleway", "Righteous",
            "Roboto", "Rubik", "Signika", "Source Sans Pro", "Teko", "Titillium Web", "Ubuntu", "Varela Round",
            "Work Sans", "Zilla Slab"];
    $systemFonts = [
        'Arial', 'Courier New', 'Times New Roman', 'Georgia',
        'Trebuchet MS', 'Tahoma', 'Verdana', 'Segoe UI'
    ];

    foreach ($fields as $field) {
        if (!empty($field[0]['styles']['fontFamily'])) {
            $fonts[] = $field[0]['styles']['fontFamily'];
        }
        if (!empty($field['labelFont'])) {
            $fonts[] = $field['labelFont'];
        }
    }

    // Deduplicate, sanitize, and remove system fonts
    $fonts = array_unique(array_filter($fonts));
    $fonts = array_diff($fonts, $systemFonts); // 🚫 Remove system fonts

    return $fonts;
}


$usedFonts = extractGoogleFonts($fields);


?>

<!DOCTYPE html>
<html>
<head>
  <title><?= $title ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/assets/formbuilder/style.css">
  <link href="/dashboards/css/vendors.min.css" rel="stylesheet" type="text/css">

    <!-- App css -->
    <link href="/dashboards/css/app.min.css" rel="stylesheet" type="text/css">

    <!-- Favicon -->
    <link rel="shortcut icon" href="/dashboards/img/favicon.ico">

    <!-- Toastr css -->
    <link href="/dashboards/plugins/toastr/css/toastr.min.css" rel="stylesheet">
    
     <!-- Pickr css -->
    <link href="/dashboards/plugins/pickr/css/classic.min.css" rel="stylesheet">
    <script src="/dashboards/plugins/pickr/pickr.min.js"></script>
    
    

    <!-- Gritter -->
    <link href="/dashboards/plugins/gritter/css/jquery.gritter.css" rel="stylesheet">

    <!-- Bootstrap-->
    
    <link href="/dashboards/css/bootstrap.min.css" rel="stylesheet" type="text/css">
     <script src="/dashboards/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
     
    <!-- Icons css -->
    <link href="/dashboards/plugins/fontawesome/css/all.min.css" rel="stylesheet" type="text/css">

    <!-- Animate.css -->
    <link href="/dashboards/plugins/animate/css/animate.min.css" rel="stylesheet">
    
   
    
    <!-- Jquery mask -->
    <script src="/dashboards/plugins/jquery/js/jquery.min.js"></script>
    
         
    <script src="/dashboards/plugins/jquery-mask-plugin/js/jquery.mask.min.js"></script>
    
     <!-- Summernote -->
    <link href="/dashboards/plugins/summernote/summernote-bs5.min.css" rel="stylesheet">
    <script src="/dashboards/plugins/summernote/summernote-bs5.min.js"></script>
    
    <!-- Signature -->
    <script src="/dashboards/js/pages/signature_pad.umd.min.js"></script>

    <!-- Style css -->
    <link href="/dashboards/css/style.css" rel="stylesheet" type="text/css">

    <!-- Head.js -->
    <script src="/dashboards/js/head.js"></script>
    <script src="/assets/formbuilder/Sortable.min.js"></script>
  <script src="/assets/formbuilder/mka-js/configs.js"></script>



    <style>
   body {
    background: #ffffff;
    margin: 20px;   
   }
   body.mobile {
       background: #ffffff;
       overflow-y: scroll;
  -ms-overflow-style: none; /* IE/Edge */
  scrollbar-width: none;    /* Firefox */
       
   }
   
   body.mobile::-webkit-scrollbar {
  display: none;            /* Chrome, Safari, Opera */
}
   
   body.mobile label {
  font-size: 18px;
}

body.mobile input,
body.mobile textarea,
body.mobile select {
  font-size: 16px;
  padding: 12px;
}
#form-loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(255, 255, 255, 0.8);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
}

.sk-spinner-wave div {
    background-color: #1ab394; /* Inspinia green */
}
  </style>
  <?php if (!empty($usedFonts)): ?>
  <link href="https://fonts.googleapis.com/css2?family=<?= implode('&family=', array_map('urlencode', $usedFonts)) ?>&display=swap" rel="stylesheet">
<?php endif; ?>
</head>
<body class="<?= $isMobile ? 'mobile' : '' ?>">
  <div class="form-container">
    
    <form id="renderedForm" enctype="multipart/form-data" method="POST">
    <input type="hidden" name="form_uuid" value="<?= $form_uuid ?>">
        <input type="hidden" name="form_version" value="<?= (int)$formVersion ?>">

        <input type="hidden" name="csrf_token" id="csrf_token" value="<?= $_SESSION['csrf_public_token'] ?>">
      <div id="form-canvas"></div>
        <div class="text-center mt-4" style="padding: 20px;">
          <button id="submitFormBtn" class="btn btn-primary px-4 py-2">
            Submit
          </button>
        </div>
    </form>
  </div>

  <script>
     //csrf reset for slow form submissions
     
     setInterval(() => {
          fetch('https://app.mkadvantage.com/formbuilder/refresh_csrf.php')
            .then(res => res.json())
            .then(data => {
              if (data.csrf_token) {
                document.querySelector('#csrf_token').value = data.csrf_token;
                console.log('CSRF token refreshed');
              }
            });
        }, 10 * 60 * 1000); // every 10 minutes

 
    const formFields = <?= json_encode($fields) ?>;
    config.isMobile = <?= $isMobile ? 'true' : 'false' ?>;
    
    $(document).ready(function () {
        config.isPublic = true;
        if (config.isMobile == 'true' && Array.isArray(formFields)) {
              // Flatten all fields into a single array
              const flatFields = [];

              formFields.forEach(row => {
                if (Array.isArray(row)) {
                  row.forEach(field => {
                    // Force colSize to 12 for mobile
                    field.colSize = 12;
                    flatFields.push(field);
                  });
                }
              });

              // Rebuild rows: one field per row
              const mobileFields = flatFields.map(field => [field]);

              // Replace original with mobile-optimized layout
              formFields.length = 0; // clear existing
              formFields.push(...mobileFields);
            }


        config.renderForm(formFields);
        config.formPublic();

        config.wireSignatureValidation();
        //SPINNER:
        
        // Spinner & submit button handles
        const overlay   = document.getElementById('form-loading-overlay');
        const submitBtn = document.getElementById('submitFormBtn');

        // Simple show/hide helpers
        function showOverlay() { if (overlay) overlay.style.display = 'flex'; }
        function hideOverlay() { if (overlay) overlay.style.display = 'none'; }



        // Prevent duplicate submissions
        let isSubmitting = false;
        function lockSubmit() {
          if (!submitBtn) return;
          submitBtn.disabled = true;
          submitBtn.setAttribute('aria-busy', 'true');
          submitBtn.dataset.originalText = submitBtn.textContent;
          submitBtn.textContent = 'Submitting…';
        }
        function unlockSubmit() {
          if (!submitBtn) return;
          submitBtn.disabled = false;
          submitBtn.removeAttribute('aria-busy');
          submitBtn.textContent = submitBtn.dataset.originalText || 'Submit';
        }

        //form submission
       document.getElementById('submitFormBtn')?.addEventListener('click', function (e) {
          e.preventDefault();
          if (isSubmitting) return; // hard block on rapid re-clicks
          isSubmitting = true;

          const form = document.getElementById('renderedForm');
          if (!form) { isSubmitting = false; return; }

           const groupsOK = config.validateCheckboxGroups(form);
           if (!groupsOK) {
               form.reportValidity();
               isSubmitting = false;
               return;
           }


           if (!form.checkValidity()) {
            form.reportValidity();
            isSubmitting = false;
            return;
          }


          // UI: lock & show spinner
           // UI: lock & show spinner
           lockSubmit();
           showOverlay();

// Start EMPTY so we don't double-collect anything
           const formData = new FormData();   // <-- key change
           // essentials that are NOT data-field-id controls
           const mustHaveNames = ['form_uuid', 'form_version', 'account_uuid', 'user_uuid', 'company_slug'];
           mustHaveNames.forEach(n => {
               const el = document.querySelector(`#renderedForm [name="${n}"]`);
               if (el && typeof el.value !== 'undefined') formData.set(n, el.value);
           });

           formData.set('csrf_token', document.querySelector('#csrf_token')?.value || '');

// Append only what we want, once
           form.querySelectorAll('[data-field-id]').forEach(el => {
               const name = el.getAttribute('name') || el.getAttribute('data-field-id');
               if (!name) return;

               // Skip hidden signature proxy inputs; we'll append the canvas blob later
               if (el.type === 'hidden' && form.querySelector(`canvas[name="${name}"]`)) {
                   return;
               }

               // Handle checkboxes (append all checked)
               if (el.matches('input[type="checkbox"]')) {
                   if (el.checked) formData.append(name, el.value);
                   return;
               }

               document.addEventListener('DOMContentLoaded', () => {
                   document.querySelectorAll('.field[data-type="checkbox"]').forEach(normalizeCheckboxGroup);
               });


               // Handle radios (only the chosen one)
               if (el.matches('input[type="radio"]')) {
                   if (el.checked) formData.set(name, el.value);
                   return;
               }

               // Handle multi-select (append all selected)
               if (el.matches('select[multiple]')) {
                   Array.from(el.selectedOptions).forEach(opt => formData.append(name, opt.value));
                   return;
               }

               if (el.matches('input[type="file"]')) {
                   const files = el.files || [];
                   const isMultiple = el.hasAttribute('multiple');

                   if (isMultiple) {
                       // name must end with [] when multiple
                       const baseName = name.endsWith('[]') ? name : (name + '[]');
                       Array.from(files).forEach(f => {
                           formData.append(baseName, f, f.name);
                       });
                   } else {
                       if (files[0]) formData.set(name, files[0], files[0].name);
                   }
                   return;
               }

               // Everything else (single value)
               if (el.matches('input, select, textarea')) {
                   formData.set(name, el.value);
               }
           });



           // Collect signature/canvas blobs
          const canvasElements = document.querySelectorAll('canvas[name]');
          const canvasPromises = Array.from(canvasElements).map(canvas => {
            return new Promise(resolve => {
              const name = canvas.getAttribute('name');
              canvas.toBlob(blob => {
                if (blob) formData.append(name, blob, name + '.png');
                resolve();
              });
            });
          });

          // Optional: fail-safe to not leave UI locked forever (e.g., network hung)
          const failsafe = setTimeout(() => {
            hideOverlay();
            unlockSubmit();                                      
            isSubmitting = false;
          }, 120000); // 120s

          Promise.all(canvasPromises).then(() => {

              for (const [k, v] of formData.entries()) {
                  if (v instanceof File) {
                      console.log('[FD] file', k, v.name, v.type, v.size);
                  } else {
                      console.log('[FD] field', k, String(v).slice(0, 120));
                  }
              }

              return fetch('https://app.mkadvantage.com/formbuilder/submit_form.php', {
              method: 'POST',
              headers: {
                'X-CSRF-Token': document.querySelector('#csrf_token')?.value || ''
              },
              body: formData
            });
          })
          .then(res => res.json())
          .then(response => {
            clearTimeout(failsafe);

            if (response.success) {
              // Keep overlay up until reload so users don't click around
              alert(response.msg || 'Form submitted successfully!');
              location.reload();
              return; // no unlock needed (page will reload)
            }

            // Non-success: log + user-friendly message
            hideOverlay();
            unlockSubmit();
            isSubmitting = false;

            alert('Thank you for your form entry'); // your current UX choice

            fetch('https://app.mkadvantage.com/formbuilder/log_submit_failure.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({
                error: response.error || 'Unknown error response from server.',
                form_uuid: document.querySelector('input[name="form_uuid"]')?.value || 'unknown',
                timestamp: new Date().toISOString(),
                userAgent: navigator.userAgent,
                url: window.location.href
              })
            });
          })
          .catch(err => {
            clearTimeout(failsafe);

            hideOverlay();
            unlockSubmit();
            isSubmitting = false;

            console.error('Submit failed:', err);
            alert('Thank you for your form entry'); // your current UX choice

            // Email/log on error
            fetch('https://app.mkadvantage.com/formbuilder/log_submit_failure.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({
                error: err.toString(),
                form_uuid: document.querySelector('input[name="form_uuid"]')?.value || 'unknown',
                timestamp: new Date().toISOString(),
                userAgent: navigator.userAgent,
                url: window.location.href
              })
            });
          });
        });


    });
    
    
  </script>
  <script src="/mka-assets/formbuilder/mka-js/formhelpers.js"></script>
  <script>
        //formhelpers.gad7();
        //additional form helpers



  </script>
  
  <div id="form-loading-overlay" style="display:none;">
    <div class="sk-spinner sk-spinner-wave">
        <div class="sk-rect1"></div>
        <div class="sk-rect2"></div>
        <div class="sk-rect3"></div>
        <div class="sk-rect4"></div>
        <div class="sk-rect5"></div>
    </div>
</div>
</body>
</html>

