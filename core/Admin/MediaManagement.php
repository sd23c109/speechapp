<?php
namespace MKA\Admin;
require_once '/opt/mka/core/Log/MKALogger.php';
use PDO;
use MKA\Log\MKALogger;

/**
 * MediaManagement
 *
 * Manages success celebration media (videos and images) with upload, permissions, and playback settings
 */
class MediaManagement {

    const MAX_VIDEO_SIZE = 10 * 1024 * 1024; // 10MB
    const MAX_IMAGE_SIZE = 5 * 1024 * 1024;  // 5MB
    const MAX_VIDEO_DURATION = 5; // 5 seconds

    const ALLOWED_VIDEO_TYPES = ['video/mp4', 'video/webm', 'video/quicktime'];
    const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    const UPLOAD_PATH = '/opt/mka/public/assets/portal/exercises/media/';
    const WEB_PATH = '/assets/portal/exercises/media/';

    /**
     * Get available media for a user based on their role
     */
    public static function getAvailableMedia(string $userUuid, ?string $mediaType = null): array {
        global $pdo;

        // Get user info
        $stmt = $pdo->prepare("
            SELECT user_type, parent_user_uuid 
            FROM mka_users 
            WHERE UserUUID = ?
        ");
        $stmt->execute([$userUuid]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return [];
        }

        $isSuperUser = ($user['user_type'] === 'super_user');
        $isEnterpriseAdmin = ($user['user_type'] === 'enterprise_admin');

        // Build query based on user type
        $sql = "
            SELECT media_id, media_type, owner_user_uuid, 
                   media_name, media_filename, media_path, 
                   file_size_bytes, duration_seconds,
                   allow_sound, autoplay_loop, display_order, created_at
            FROM exercise_success_media
            WHERE is_active = 1 
              AND (owner_user_uuid IS NULL OR owner_user_uuid = ?)
        ";

        // Add media type filter if specified
        if ($mediaType) {
            $sql .= " AND media_type = ?";
        }

        $sql .= " ORDER BY display_order ASC, media_name ASC";

        $stmt = $pdo->prepare($sql);

        $params = [$isSuperUser || $isEnterpriseAdmin ? $userUuid : $user['parent_user_uuid']];
        if ($mediaType) {
            $params[] = $mediaType;
        }

        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Upload media (video or image)
     */
    public static function uploadMedia(array $fileData, array $mediaInfo, string $createdBy): array {
        global $pdo;

        // Check permissions
        if (!self::canManageMedia($createdBy)) {
            return ['status' => 'fail', 'message' => 'Permission denied'];
        }

        // Detect media type from MIME
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $fileData['tmp_name']);
        finfo_close($finfo);

        $isVideo = in_array($mimeType, self::ALLOWED_VIDEO_TYPES);
        $isImage = in_array($mimeType, self::ALLOWED_IMAGE_TYPES);

        if (!$isVideo && !$isImage) {
            return ['status' => 'fail', 'message' => 'Invalid file type. Only videos (MP4, WebM, MOV) and images (JPEG, PNG, GIF, WebP) are allowed'];
        }

        $mediaType = $isVideo ? 'video' : 'image';

        // Validate file
        $validation = self::validateMediaFile($fileData, $mediaType);
        if ($validation['status'] !== 'success') {
            return $validation;
        }

        // Validate metadata
        $mediaName = trim($mediaInfo['media_name'] ?? '');

        // For videos: use provided values; for images: set to NULL
        $allowSound = $isVideo ? (bool)($mediaInfo['allow_sound'] ?? true) : null;
        $autoplayLoop = $isVideo ? (bool)($mediaInfo['autoplay_loop'] ?? false) : null;

        if (empty($mediaName) || strlen($mediaName) > 255) {
            return ['status' => 'fail', 'message' => 'Invalid media name'];
        }

        try {
            // Generate unique filename
            $ext = pathinfo($fileData['name'], PATHINFO_EXTENSION);
            $prefix = $isVideo ? 'success_video_' : 'success_image_';
            $uniqueFilename = uniqid($prefix, true) . '.' . $ext;
            $uploadPath = self::UPLOAD_PATH . $uniqueFilename;
            $webPath = self::WEB_PATH . $uniqueFilename;

            // Create directory if it doesn't exist
            if (!is_dir(self::UPLOAD_PATH)) {
                mkdir(self::UPLOAD_PATH, 0755, true);
            }

            // Move uploaded file
            if (!move_uploaded_file($fileData['tmp_name'], $uploadPath)) {
                return ['status' => 'fail', 'message' => 'Failed to upload file'];
            }

            // Get actual file size
            $fileSize = filesize($uploadPath);

            // Insert into database
            $stmt = $pdo->prepare("
                INSERT INTO exercise_success_media 
                (owner_user_uuid, media_type, media_name, media_filename, media_path, 
                 file_size_bytes, allow_sound, autoplay_loop, display_order, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 999, 1)
            ");

            $stmt->execute([
                $createdBy,
                $mediaType,
                $mediaName,
                $uniqueFilename,
                $webPath,
                $fileSize,
                $allowSound,
                $autoplayLoop
            ]);

            $mediaId = $pdo->lastInsertId();

            MKALogger::log('success_media_uploaded', [
                'created_by' => $createdBy,
                'media_id' => $mediaId,
                'media_type' => $mediaType,
                'media_name' => $mediaName,
                'file_size' => $fileSize
            ]);

            return [
                'status' => 'success',
                'message' => ucfirst($mediaType) . ' uploaded successfully',
                'media_id' => $mediaId,
                'media_type' => $mediaType
            ];

        } catch (\Exception $e) {
            // Clean up file if database insert failed
            if (isset($uploadPath) && file_exists($uploadPath)) {
                unlink($uploadPath);
            }

            error_log("Media upload error: " . $e->getMessage());
            return ['status' => 'fail', 'message' => 'Database error occurred'];
        }
    }

    /**
     * Update media settings
     */
    public static function updateMedia(int $mediaId, array $updates, string $userUuid): array {
        global $pdo;

        if (!self::canEditMedia($mediaId, $userUuid)) {
            return ['status' => 'fail', 'message' => 'Permission denied'];
        }

        try {
            $allowed = ['media_name', 'allow_sound', 'autoplay_loop', 'display_order'];
            $fields = [];
            $values = [];

            foreach ($updates as $key => $value) {
                if (in_array($key, $allowed)) {
                    $fields[] = "$key = ?";
                    $values[] = $value;
                }
            }

            if (empty($fields)) {
                return ['status' => 'fail', 'message' => 'No valid fields to update'];
            }

            $values[] = $mediaId;
            $sql = "UPDATE exercise_success_media SET " . implode(', ', $fields) . " WHERE media_id = ?";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);

            MKALogger::log('success_media_updated', [
                'updated_by' => $userUuid,
                'media_id' => $mediaId
            ]);

            return ['status' => 'success', 'message' => 'Media updated successfully'];

        } catch (\Exception $e) {
            error_log("Media update error: " . $e->getMessage());
            return ['status' => 'fail', 'message' => 'Database error occurred'];
        }
    }

    /**
     * Delete media
     */
    public static function deleteMedia(int $mediaId, string $userUuid): array {
        global $pdo;

        if (!self::canEditMedia($mediaId, $userUuid)) {
            return ['status' => 'fail', 'message' => 'Permission denied'];
        }

        try {
            // Get media info
            $stmt = $pdo->prepare("
                SELECT media_filename FROM exercise_success_media WHERE media_id = ?
            ");
            $stmt->execute([$mediaId]);
            $media = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$media) {
                return ['status' => 'fail', 'message' => 'Media not found'];
            }

            // Soft delete
            $stmt = $pdo->prepare("
                UPDATE exercise_success_media SET is_active = 0 WHERE media_id = ?
            ");
            $stmt->execute([$mediaId]);

            // Optionally delete physical file
            $filePath = self::UPLOAD_PATH . $media['media_filename'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            MKALogger::log('success_media_deleted', [
                'deleted_by' => $userUuid,
                'media_id' => $mediaId
            ]);

            return ['status' => 'success', 'message' => 'Media deleted successfully'];

        } catch (\Exception $e) {
            error_log("Media delete error: " . $e->getMessage());
            return ['status' => 'fail', 'message' => 'Database error occurred'];
        }
    }

    /**
     * Validate uploaded media file
     */
    private static function validateMediaFile(array $fileData, string $mediaType): array {
        // Check for upload errors
        if ($fileData['error'] !== UPLOAD_ERR_OK) {
            $errors = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize in php.ini',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE in HTML form',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the upload'
            ];

            $errorMsg = $errors[$fileData['error']] ?? 'Unknown upload error';
            return ['status' => 'fail', 'message' => 'File upload error: ' . $errorMsg];
        }

        // Check file size based on type
        $maxSize = ($mediaType === 'video') ? self::MAX_VIDEO_SIZE : self::MAX_IMAGE_SIZE;
        if ($fileData['size'] > $maxSize) {
            $maxMB = $maxSize / (1024 * 1024);
            return ['status' => 'fail', 'message' => "File size exceeds {$maxMB}MB limit"];
        }

        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $fileData['tmp_name']);
        finfo_close($finfo);

        $allowedTypes = ($mediaType === 'video') ? self::ALLOWED_VIDEO_TYPES : self::ALLOWED_IMAGE_TYPES;
        if (!in_array($mimeType, $allowedTypes)) {
            $typeList = ($mediaType === 'video')
                ? 'MP4, WebM, or MOV'
                : 'JPEG, PNG, GIF, or WebP';
            return ['status' => 'fail', 'message' => "Invalid file type. Only $typeList allowed"];
        }

        return ['status' => 'success'];
    }

    /**
     * Check if user can manage media
     */
    private static function canManageMedia(string $userUuid): bool {
        global $pdo;

        $stmt = $pdo->prepare("
            SELECT user_type FROM mka_users WHERE UserUUID = ?
        ");
        $stmt->execute([$userUuid]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return in_array($user['user_type'] ?? '', ['super_user', 'enterprise_admin']);
    }

    /**
     * Check if user can edit specific media
     */
    private static function canEditMedia(int $mediaId, string $userUuid): bool {
        global $pdo;

        // Get user type
        $stmt = $pdo->prepare("SELECT user_type FROM mka_users WHERE UserUUID = ?");
        $stmt->execute([$userUuid]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) return false;

        // Super users can edit anything
        if ($user['user_type'] === 'super_user') {
            return true;
        }

        // Enterprise admins can only edit their own
        if ($user['user_type'] === 'enterprise_admin') {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM exercise_success_media 
                WHERE media_id = ? AND owner_user_uuid = ?
            ");
            $stmt->execute([$mediaId, $userUuid]);
            return $stmt->fetchColumn() > 0;
        }

        return false;
    }

    /**
     * Set user's default success media
     */
    public static function setUserDefaultMedia(string $userUuid, int $mediaId): array {
        global $pdo;

        // Verify media exists and user can access it
        $stmt = $pdo->prepare("
            SELECT media_id FROM exercise_success_media 
            WHERE media_id = ? AND is_active = 1
        ");
        $stmt->execute([$mediaId]);

        if (!$stmt->fetch()) {
            return ['status' => 'fail', 'message' => 'Media not found'];
        }

        try {
            // Use INSERT ... ON DUPLICATE KEY UPDATE to set or update default
            $stmt = $pdo->prepare("
                INSERT INTO user_default_success_media (user_uuid, media_id)
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE media_id = ?, updated_at = CURRENT_TIMESTAMP
            ");
            $stmt->execute([$userUuid, $mediaId, $mediaId]);

            MKALogger::log('default_success_media_set', [
                'user_uuid' => $userUuid,
                'media_id' => $mediaId
            ]);

            return ['status' => 'success', 'message' => 'Default success media set'];

        } catch (\Exception $e) {
            error_log("Set default media error: " . $e->getMessage());
            return ['status' => 'fail', 'message' => 'Database error occurred'];
        }
    }

    /**
     * Get user's default success media
     */
    public static function getUserDefaultMedia(string $userUuid): ?array {
        global $pdo;

        $stmt = $pdo->prepare("
            SELECT m.media_id as media_id, m.media_type, m.media_name, 
                   m.media_path, m.allow_sound, m.autoplay_loop
            FROM user_default_success_media d
            INNER JOIN exercise_success_media m ON d.media_id = m.media_id
            WHERE d.user_uuid = ? AND m.is_active = 1
        ");
        $stmt->execute([$userUuid]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Clear user's default success media
     */
    public static function clearUserDefaultMedia(string $userUuid): array {
        global $pdo;

        try {
            $stmt = $pdo->prepare("DELETE FROM user_default_success_media WHERE user_uuid = ?");
            $stmt->execute([$userUuid]);

            return ['status' => 'success', 'message' => 'Default cleared'];
        } catch (\Exception $e) {
            error_log("Clear default media error: " . $e->getMessage());
            return ['status' => 'fail', 'message' => 'Database error occurred'];
        }
    }

    /**
     * Assign media to an assignment
     */
    public static function assignMediaToAssignment(
        int $assignmentGroupId,
        array $mediaIds,
        string $selectionType = 'sequential',
        string $userUuid
    ): array {
        global $pdo;

        // Verify user owns or can edit this assignment
        $stmt = $pdo->prepare("
            SELECT created_by FROM assignment_groups WHERE assignment_group_id = ?
        ");
        $stmt->execute([$assignmentGroupId]);
        $assignment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$assignment) {
            return ['status' => 'fail', 'message' => 'Assignment not found'];
        }

        // Check permission (super_user can edit any, enterprise_admin can edit their own)
        $stmt = $pdo->prepare("SELECT user_type FROM mka_users WHERE UserUUID = ?");
        $stmt->execute([$userUuid]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user['user_type'] !== 'super_user' && $assignment['created_by'] !== $userUuid) {
            return ['status' => 'fail', 'message' => 'Permission denied'];
        }

        try {
            $pdo->beginTransaction();

            // Clear existing assignments
            $stmt = $pdo->prepare("DELETE FROM assignment_success_media WHERE assignment_group_id = ?");
            $stmt->execute([$assignmentGroupId]);

            // Add new assignments
            $stmt = $pdo->prepare("
                INSERT INTO assignment_success_media 
                (assignment_group_id, media_id, display_order, selection_type)
                VALUES (?, ?, ?, ?)
            ");

            foreach ($mediaIds as $index => $mediaId) {
                $stmt->execute([$assignmentGroupId, $mediaId, $index, $selectionType]);
            }

            $pdo->commit();

            MKALogger::log('assignment_media_assigned', [
                'assignment_id' => $assignmentGroupId,
                'media_count' => count($mediaIds),
                'selection_type' => $selectionType
            ]);

            return ['status' => 'success', 'message' => 'Media assigned to assignment'];

        } catch (\Exception $e) {
            $pdo->rollBack();
            error_log("Assign media to assignment error: " . $e->getMessage());
            return ['status' => 'fail', 'message' => 'Database error occurred'];
        }
    }

    /**
     * Get media assigned to an assignment
     */
    public static function getAssignmentMedia(int $assignmentGroupId): array {
        global $pdo;

        $stmt = $pdo->prepare("
            SELECT am.id, am.media_id, am.selection_type, am.specific_exercise_index,
                   m.media_type, m.media_name, m.media_path, 
                   m.allow_sound, m.autoplay_loop
            FROM assignment_success_media am
            INNER JOIN exercise_success_media m ON am.media_id = m.media_id
            WHERE am.assignment_group_id = ? AND m.is_active = 1
            ORDER BY am.display_order ASC
        ");
        $stmt->execute([$assignmentGroupId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Remove media from assignment
     */
    public static function removeMediaFromAssignment(int $assignmentGroupId, int $mediaId, string $userUuid): array {
        global $pdo;

        // Verify permission
        $stmt = $pdo->prepare("
            SELECT created_by FROM assignment_groups WHERE assignment_group_id = ?
        ");
        $stmt->execute([$assignmentGroupId]);
        $assignment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$assignment) {
            return ['status' => 'fail', 'message' => 'Assignment not found'];
        }

        $stmt = $pdo->prepare("SELECT user_type FROM mka_users WHERE UserUUID = ?");
        $stmt->execute([$userUuid]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user['user_type'] !== 'super_user' && $assignment['created_by'] !== $userUuid) {
            return ['status' => 'fail', 'message' => 'Permission denied'];
        }

        try {
            $stmt = $pdo->prepare("
                DELETE FROM assignment_success_media 
                WHERE assignment_group_id = ? AND media_id = ?
            ");
            $stmt->execute([$assignmentGroupId, $mediaId]);

            return ['status' => 'success', 'message' => 'Media removed from assignment'];

        } catch (\Exception $e) {
            error_log("Remove media from assignment error: " . $e->getMessage());
            return ['status' => 'fail', 'message' => 'Database error occurred'];
        }
    }

}
