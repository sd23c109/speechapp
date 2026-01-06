<?php
namespace MKA\Tasks;

class Utils {

    public static function flattenArray($data): string {
        $result = [];

        $flatten = function($item) use (&$flatten, &$result) {
            if (is_array($item) || is_object($item)) {
                foreach ((array)$item as $subItem) {
                    $flatten($subItem);
                }
            } elseif (is_string($item) || is_numeric($item)) {
                $result[] = (string)$item;
            }
        };

        $flatten($data);
        return implode(' ', $result);
    }

    public static function maskEmail($email): string {
        return preg_replace('/(?<=.).(?=.*@)/u','*',$email);
    }

    public static function extractDomain($website) {
        // Ensure it has a scheme
        if (!preg_match('~^https?://~i', $website)) {
            $website = 'https://' . $website;
        }

        $parsed = parse_url($website);
        $host = $parsed['host'] ?? $website;
        $host = strtolower($host);

        // Split into parts
        $parts = explode('.', $host);

        // Handle cases like mydomain.com or sub.mydomain.com
        if (count($parts) >= 2) {
            $domain = $parts[count($parts)-2] . '.' . $parts[count($parts)-1];
            return $domain;
        }

        return $host;
    }

}
