<?php
require_once '/opt/mka/bootstrap.php'; // Adjust to your database config

header('Content-Type: application/json');



// Check authentication
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_user', 'slp'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Ensure POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get content type and ID
    $content_type = $_POST['content_type'] ?? '';
    $content_id = $_POST['content_id'] ?? null;
    $existing_image_path = $_POST['existing_image_path'] ?? '';
    $delete_old_image = ($_POST['delete_old_image'] ?? '0') === '1';

    // Validate content type
    $valid_types = ['3cv_blend', 'cv_blend', 'consonant', 'vowel'];
    if (!in_array($content_type, $valid_types)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid content type']);
        exit;
    }

    // Map content types to database tables and field names
    $type_config = [
        '3cv_blend' => [
            'table' => 'three_cv_blends',
            'field' => 'blend',
            'name' => '3-Letter CV Blend'
        ],
        'cv_blend' => [
            'table' => 'cv_blends',
            'field' => 'blend',
            'name' => 'CV Blend'
        ],
        'consonant' => [
            'table' => 'consonants',
            'field' => 'consonant',
            'name' => 'Consonant'
        ],
        'vowel' => [
            'table' => 'vowels',
            'field' => 'vowel',
            'name' => 'Vowel'
        ]
    ];

    $config = $type_config[$content_type];
    $table = $config['table'];
    $field_name = $config['field'];
    $content_name = $config['name'];

    // Get field value
    $field_value = trim($_POST[$field_name] ?? '');
    $category = trim($_POST['category'] ?? '');

    // Validate required field
    if (empty($field_value)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $content_name . ' is required']);
        exit;
    }

    // Define upload directory
    $upload_base = '/opt/mka/public/uploads/';
    $upload_dir = $upload_base . $content_type . '/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $image_filename = null;

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $image = $_FILES['image'];

        // Check for upload errors
        if ($image['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'File upload error']);
            exit;
        }

        // Validate file type
        $allowed_types = ['image/png', 'image/jpeg', 'image/jpg'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $image['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime_type, $allowed_types)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Only PNG and JPEG are allowed.']);
            exit;
        }

        // Validate file size (3MB)
        $max_size = 3 * 1024 * 1024;
        if ($image['size'] > $max_size) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Image size must not exceed 3MB.']);
            exit;
        }

        // Get file extension
        $extension = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
        if ($extension === 'jpg') {
            $extension = 'jpeg';
        }

        // Delete old image if updating
        if (!empty($existing_image_path) && $delete_old_image) {
            $old_file = $upload_base . $existing_image_path;
            if (file_exists($old_file)) {
                unlink($old_file);
            }
        }

        // Generate unique filename
        $safe_field_value = preg_replace('/[^a-z0-9]/i', '_', $field_value);
        $unique_name = $safe_field_value . '_' . uniqid() . '.' . $extension;
        $image_path = $upload_dir . $unique_name;

        // Move uploaded file
        if (!move_uploaded_file($image['tmp_name'], $image_path)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to save image file.']);
            exit;
        }

        // Store relative path for database
        $image_filename = 'uploads/' . $content_type . '/' . $unique_name;

    } elseif (!empty($existing_image_path) && $delete_old_image) {
        // User removed image without uploading new one
        $old_file = $upload_base . $existing_image_path;
        if (file_exists($old_file)) {
            unlink($old_file);
        }
        $image_filename = null;
    } elseif (!empty($existing_image_path)) {
        // Keep existing image
        $image_filename = $existing_image_path;
    }

    // Database operations
    if ($content_id) {
        // UPDATE existing content
        $sql = "UPDATE $table SET 
                $field_name = :field_value, 
                category = :category";

        if ($image_filename !== null || $delete_old_image) {
            $sql .= ", image_path = :image_path";
        }

        $sql .= ", updated_at = NOW(), updated_by = :user_id 
                WHERE id = :content_id";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':field_value', $field_value);
        $stmt->bindParam(':category', $category);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->bindParam(':content_id', $content_id);

        if ($image_filename !== null || $delete_old_image) {
            $stmt->bindParam(':image_path', $image_filename);
        }

        $stmt->execute();
        $message = $content_name . ' updated successfully';

    } else {
        // INSERT new content
        $sql = "INSERT INTO $table ($field_name, category, image_path, created_by, created_at) 
                VALUES (:field_value, :category, :image_path, :user_id, NOW())";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':field_value', $field_value);
        $stmt->bindParam(':category', $category);
        $stmt->bindParam(':image_path', $image_filename);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);

        $stmt->execute();
        $content_id = $pdo->lastInsertId();
        $message = $content_name . ' added successfully';
    }

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => $message,
        'content_id' => $content_id,
        'content_type' => $content_type,
        'image_path' => $image_filename,
        'image_url' => $image_filename ? '/' . $image_filename : null
    ]);

} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);

} catch (Exception $e) {
    error_log('Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred while processing your request']);
}
?>
