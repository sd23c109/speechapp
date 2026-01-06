<?php
namespace MKA\Security;

class CSRFHelper {
    public static function generateToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function getToken(): string {
        return $_SESSION['csrf_token'] ?? '';
    }

    public static function validateRequest(): bool {
        $clientToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
        $serverToken = $_SESSION['csrf_token'] ?? '';
        return hash_equals($serverToken, $clientToken);
    }

    public static function enforce(): void {
        if (!self::validateRequest()) {
            http_response_code(403);
            echo json_encode(['error' => 'CSRF token mismatch']);
            exit;
        }
    }

    public static function inputField(): string {
        return '<input type="hidden" name="csrf_token" value="' . self::getToken() . '">';
    }
}

