<?php
namespace MKA\Admin;
require_once '/opt/mka/core/Log/MKALogger.php';
use PDO;
use MKA\Log\MKALogger;

/**
 * VideoManagement
 *
 * Manages success celebration videos with upload, permissions, and playback settings
 */
class VideoManagement {

    const MAX_FILE_SIZE = 10 * 1024 * 1024; // 4MB in bytes
    const MAX_DURATION = 5; // 5 seconds
    const ALLOWED_MIME_TYPES = ['video/mp4', 'video/webm', 'video/quicktime'];
    const UPLOAD_PATH = '/opt/mka/public/assets/portal/exercises/videos/';
    const WEB_PATH = '/assets/portal/exercises/videos/';

    /**
     * Get available videos for a user based on their role
     *
     * Rules:
     * 1. Super users: ALL videos (system + their own)
     * 2. Enterprise admins: System videos + their own
     * 3. End users: Their parent admin's videos + system videos
     */
    public static function getAvailableVideos(string $userUuid): array {
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
        $isEndUser = ($user['user_type'] === 'end_user');

        // Determine which videos to show
        if ($isSuperUser || $isEnterpriseAdmin) {
            // Show system videos + their own
            $stmt = $pdo->prepare("
                SELECT exercise_success_media, owner_user_uuid, video_name, video_filename, 
                       video_path, file_size_bytes, duration_seconds,
                       allow_sound, autoplay_loop, display_order, created_at
                FROM exercise_success_videos
                WHERE is_active = 1 
                  AND (owner_user_uuid IS NULL OR owner_user_uuid = ?)
                ORDER BY display_order ASC, video_name ASC
            ");
            $stmt->execute([$userUuid]);
        } else {
            // End users see their parent's videos + system videos
            $parentUuid = $user['parent_user_uuid'];
            $stmt = $pdo->prepare("
                SELECT exercise_success_media, owner_user_uuid, video_name, video_filename, 
                       video_path, file_size_bytes, duration_seconds,
                       allow_sound, autoplay_loop, display_order, created_at
                FROM exercise_success_videos
                WHERE is_active = 1 
                  AND (owner_user_uuid IS NULL OR owner_user_uuid = ?)
                ORDER BY display_order ASC, video_name ASC
            ");
            $stmt->execute([$parentUuid]);
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Upload and create a new success video
     */
    public static function uploadVideo(array $fileData, array $videoInfo, string $createdBy): array {
        global $pdo;

        // Check permissions
        if (!self::canManageVideos($createdBy)) {
            return ['status' => 'fail', 'message' => 'Permission denied'];
        }

        // Validate file
        $validation = self::validateVideoFile($fileData);
        if ($validation['status'] !== 'success') {
            return $validation;
        }

        // Validate metadata
        $videoName = trim($videoInfo['video_name'] ?? '');
        $allowSound = (bool)($videoInfo['allow_sound'] ?? true);
        $autoplayLoop = (bool)($videoInfo['autoplay_loop'] ?? false);

        if (empty($videoName) || strlen($videoName) > 255) {
            return ['status' => 'fail', 'message' => 'Invalid video name'];
        }

        try {
            // Generate unique filename
            $ext = pathinfo($fileData['name'], PATHINFO_EXTENSION);
            $uniqueFilename = uniqid('success_video_', true) . '.' . $ext;
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
                INSERT INTO exercise_success_videos 
                (owner_user_uuid, video_name, video_filename, video_path, 
                 file_size_bytes, allow_sound, autoplay_loop, display_order, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, 999, 1)
            ");

            $stmt->execute([
                $createdBy,
                $videoName,
                $uniqueFilename,
                $webPath,
                $fileSize,
                $allowSound ? 1 : 0,
                $autoplayLoop ? 1 : 0
            ]);

            $videoId = $pdo->lastInsertId();

            MKALogger::log('success_video_uploaded', [
                'created_by' => $createdBy,
                'exercise_success_media' => $videoId,
                'video_name' => $videoName,
                'file_size' => $fileSize
            ]);

            return [
                'status' => 'success',
                'message' => 'Video uploaded successfully',
                'exercise_success_media' => $videoId
            ];

        } catch (\Exception $e) {
            // Clean up file if database insert failed
            if (isset($uploadPath) && file_exists($uploadPath)) {
                unlink($uploadPath);
            }

            error_log("Video upload error: " . $e->getMessage());
            return ['status' => 'fail', 'message' => 'Database error occurred'];
        }
    }

    /**
     * Update video settings
     */
    public static function updateVideo(int $videoId, array $updates, string $userUuid): array {
        global $pdo;

        // Check ownership
        $stmt = $pdo->prepare("
            SELECT owner_user_uuid FROM exercise_success_videos WHERE exercise_success_media = ?
        ");
        $stmt->execute([$videoId]);
        $video = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$video) {
            return ['status' => 'fail', 'message' => 'Video not found'];
        }

        if (!self::canEditVideo($videoId, $userUuid)) {
            return ['status' => 'fail', 'message' => 'Permission denied'];
        }

        try {
            $allowed = ['video_name', 'allow_sound', 'autoplay_loop', 'display_order'];
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

            $values[] = $videoId;
            $sql = "UPDATE exercise_success_videos SET " . implode(', ', $fields) . " WHERE exercise_success_media = ?";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);

            MKALogger::log('success_video_updated', [
                'updated_by' => $userUuid,
                'exercise_success_media' => $videoId
            ]);

            return ['status' => 'success', 'message' => 'Video updated successfully'];

        } catch (\Exception $e) {
            error_log("Video update error: " . $e->getMessage());
            return ['status' => 'fail', 'message' => 'Database error occurred'];
        }
    }

    /**
     * Delete a video
     */
    public static function deleteVideo(int $videoId, string $userUuid): array {
        global $pdo;

        if (!self::canEditVideo($videoId, $userUuid)) {
            return ['status' => 'fail', 'message' => 'Permission denied'];
        }

        try {
            // Get video info
            $stmt = $pdo->prepare("
                SELECT video_filename FROM exercise_success_videos WHERE exercise_success_media = ?
            ");
            $stmt->execute([$videoId]);
            $video = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$video) {
                return ['status' => 'fail', 'message' => 'Video not found'];
            }

            // Soft delete
            $stmt = $pdo->prepare("
                UPDATE exercise_success_videos SET is_active = 0 WHERE exercise_success_media = ?
            ");
            $stmt->execute([$videoId]);

            // Optionally delete physical file
            $filePath = self::UPLOAD_PATH . $video['video_filename'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            MKALogger::log('success_video_deleted', [
                'deleted_by' => $userUuid,
                'exercise_success_media' => $videoId
            ]);

            return ['status' => 'success', 'message' => 'Video deleted successfully'];

        } catch (\Exception $e) {
            error_log("Video delete error: " . $e->getMessage());
            return ['status' => 'fail', 'message' => 'Database error occurred'];
        }
    }

    /**
     * Validate uploaded video file
     */
    private static function validateVideoFile(array $fileData): array {
        // Check for upload errors
        if ($fileData['error'] !== UPLOAD_ERR_OK) {
            return ['status' => 'fail', 'message' => 'File upload failed'];
        }

        // Check file size
        if ($fileData['size'] > self::MAX_FILE_SIZE) {
            $maxMB = self::MAX_FILE_SIZE / (1024 * 1024);
            return ['status' => 'fail', 'message' => "File size exceeds {$maxMB}MB limit"];
        }

        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $fileData['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES)) {
            return ['status' => 'fail', 'message' => 'Invalid file type. Only MP4, WebM, and MOV allowed'];
        }

        return ['status' => 'success'];
    }

    /**
     * Check if user can manage videos
     */
    private static function canManageVideos(string $userUuid): bool {
        global $pdo;

        $stmt = $pdo->prepare("
            SELECT user_type FROM mka_users WHERE UserUUID = ?
        ");
        $stmt->execute([$userUuid]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return in_array($user['user_type'] ?? '', ['super_user', 'enterprise_admin']);
    }

    /**
     * Check if user can edit a specific video
     */
    private static function canEditVideo(int $videoId, string $userUuid): bool {
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
                SELECT COUNT(*) FROM exercise_success_videos 
                WHERE exercise_success_media = ? AND owner_user_uuid = ?
            ");
            $stmt->execute([$videoId, $userUuid]);
            return $stmt->fetchColumn() > 0;
        }

        return false;
    }
}
