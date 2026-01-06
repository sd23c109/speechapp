<?php

namespace MKA\Log;

class MKALogger
{
    protected static string $logDir = '/opt/mka/logs';

    public static function log(string $action, array $details = []): void
    {
        // Auto-create log directory if missing
        if (!is_dir(self::$logDir)) {
            mkdir(self::$logDir, 0700, true);
        }

        $logFile = self::$logDir . '/audit-' . date('Y-m-d') . '.log';

        // Build the base log entry
        $entry = [
            'timestamp' => gmdate('c'), // ISO 8601 UTC
            'action' => $action,
            'ip' => self::getClientIP() ?? 'CLI',
        ];

        // Add user UUID if available
        if (!empty($_SESSION['user_data']['uuid'])) {
            $entry['user_uuid'] = $_SESSION['user_data']['uuid'];
        }

        // Merge additional details
        $entry = array_merge($entry, $details);

        // Encode and write with locking
        $line = json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
        file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    }
    
    private static function getClientIP(): string {
            $headers = [
                'HTTP_CF_CONNECTING_IP',      // Cloudflare
                'HTTP_X_FORWARDED_FOR',       // Standard proxy/load balancer
                'HTTP_CLIENT_IP',
                'REMOTE_ADDR'
            ];

            foreach ($headers as $key) {
                if (!empty($_SERVER[$key])) {
                    $ipList = explode(',', $_SERVER[$key]);
                    return trim($ipList[0]); // Return first IP in list
                }
            }

            return 'UNKNOWN';
        }
}
