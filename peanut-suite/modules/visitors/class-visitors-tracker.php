<?php
/**
 * Visitors Tracker Utilities
 *
 * @package PeanutSuite\Visitors
 */

namespace PeanutSuite\Visitors;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Utility class for visitor tracking operations.
 */
class Visitors_Tracker {

    /**
     * Rate limit window in seconds.
     */
    const RATE_LIMIT_WINDOW = 60;

    /**
     * Maximum requests per window.
     */
    const RATE_LIMIT_MAX = 60;

    /**
     * Parse user agent string.
     *
     * @param string $user_agent User agent string.
     * @return array
     */
    public static function parse_user_agent(string $user_agent): array {
        $result = [
            'device_type' => 'desktop',
            'browser' => 'Unknown',
            'os' => 'Unknown',
        ];

        // Device detection
        if (preg_match('/Mobile|Android.*Mobile|iPhone|iPod/i', $user_agent)) {
            $result['device_type'] = 'mobile';
        } elseif (preg_match('/iPad|Android(?!.*Mobile)|Tablet/i', $user_agent)) {
            $result['device_type'] = 'tablet';
        }

        // Browser detection
        if (preg_match('/Chrome\/[\d.]+/i', $user_agent) && !preg_match('/Edge|Edg/i', $user_agent)) {
            $result['browser'] = 'Chrome';
        } elseif (preg_match('/Firefox\/[\d.]+/i', $user_agent)) {
            $result['browser'] = 'Firefox';
        } elseif (preg_match('/Safari\/[\d.]+/i', $user_agent) && !preg_match('/Chrome/i', $user_agent)) {
            $result['browser'] = 'Safari';
        } elseif (preg_match('/Edge|Edg/i', $user_agent)) {
            $result['browser'] = 'Edge';
        } elseif (preg_match('/MSIE|Trident/i', $user_agent)) {
            $result['browser'] = 'IE';
        } elseif (preg_match('/Opera|OPR/i', $user_agent)) {
            $result['browser'] = 'Opera';
        }

        // OS detection
        if (preg_match('/Windows NT/i', $user_agent)) {
            $result['os'] = 'Windows';
        } elseif (preg_match('/Mac OS X/i', $user_agent)) {
            $result['os'] = 'macOS';
        } elseif (preg_match('/Linux/i', $user_agent)) {
            $result['os'] = 'Linux';
        } elseif (preg_match('/iPhone|iPad|iPod/i', $user_agent)) {
            $result['os'] = 'iOS';
        } elseif (preg_match('/Android/i', $user_agent)) {
            $result['os'] = 'Android';
        }

        return $result;
    }

    /**
     * Get client IP address.
     *
     * @param bool $anonymize Whether to anonymize the IP.
     * @return string
     */
    public static function get_ip_address(bool $anonymize = false): string {
        $ip = '';

        // Check various headers
        $headers = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                // Handle comma-separated IPs (X-Forwarded-For)
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
                break;
            }
        }

        // Validate IP
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $ip = '0.0.0.0';
        }

        // Anonymize if requested
        if ($anonymize && $ip !== '0.0.0.0') {
            $ip = self::anonymize_ip($ip);
        }

        return $ip;
    }

    /**
     * Anonymize IP address by zeroing last octet(s).
     *
     * @param string $ip IP address.
     * @return string
     */
    public static function anonymize_ip(string $ip): string {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            // IPv4: Zero last octet (e.g., 192.168.1.100 -> 192.168.1.0)
            return preg_replace('/\.\d+$/', '.0', $ip);
        } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            // IPv6: Zero last 80 bits
            $parts = explode(':', $ip);
            $parts = array_slice($parts, 0, 3);
            return implode(':', $parts) . '::';
        }

        return $ip;
    }

    /**
     * Hash an IP address for storage.
     *
     * @param string $ip IP address.
     * @return string
     */
    public static function hash_ip(string $ip): string {
        $salt = wp_salt('auth');
        return hash('sha256', $ip . $salt);
    }

    /**
     * Check rate limit for a visitor.
     *
     * @param string $visitor_id Visitor identifier.
     * @return bool True if within limit, false if exceeded.
     */
    public static function check_rate_limit(string $visitor_id): bool {
        $key = 'peanut_rate_' . md5($visitor_id);
        $count = get_transient($key);

        if ($count === false) {
            set_transient($key, 1, self::RATE_LIMIT_WINDOW);
            return true;
        }

        if ($count >= self::RATE_LIMIT_MAX) {
            return false;
        }

        set_transient($key, $count + 1, self::RATE_LIMIT_WINDOW);
        return true;
    }

    /**
     * Validate visitor ID format.
     *
     * @param string $visitor_id Visitor identifier.
     * @return bool
     */
    public static function validate_visitor_id(string $visitor_id): bool {
        // UUID v4 format
        return (bool) preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $visitor_id
        );
    }

    /**
     * Validate session ID format.
     *
     * @param string $session_id Session identifier.
     * @return bool
     */
    public static function validate_session_id(string $session_id): bool {
        return self::validate_visitor_id($session_id);
    }

    /**
     * Sanitize event type.
     *
     * @param string $event_type Event type string.
     * @return string
     */
    public static function sanitize_event_type(string $event_type): string {
        // Allow alphanumeric, underscore, dot, hyphen
        return preg_replace('/[^a-zA-Z0-9_.\-]/', '', $event_type);
    }

    /**
     * Sanitize URL.
     *
     * @param string $url URL to sanitize.
     * @return string
     */
    public static function sanitize_url(string $url): string {
        return esc_url_raw($url);
    }

    /**
     * Extract UTM parameters from URL.
     *
     * @param string $url URL with possible UTM params.
     * @return array
     */
    public static function extract_utm_params(string $url): array {
        $params = [];
        $query = wp_parse_url($url, PHP_URL_QUERY);

        if ($query) {
            parse_str($query, $parsed);

            $utm_keys = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];
            foreach ($utm_keys as $key) {
                if (!empty($parsed[$key])) {
                    $params[$key] = sanitize_text_field($parsed[$key]);
                }
            }
        }

        return $params;
    }

    /**
     * Determine traffic channel from referrer and UTM params.
     *
     * @param string|null $referrer    Referrer URL.
     * @param array       $utm_params  UTM parameters.
     * @return string
     */
    public static function determine_channel(?string $referrer, array $utm_params = []): string {
        // Check UTM source first
        if (!empty($utm_params['utm_medium'])) {
            $medium = strtolower($utm_params['utm_medium']);

            if (in_array($medium, ['cpc', 'ppc', 'paid', 'paidsearch'], true)) {
                return 'Paid Search';
            }
            if (in_array($medium, ['display', 'banner', 'cpm'], true)) {
                return 'Display';
            }
            if (in_array($medium, ['social', 'social-media'], true)) {
                return 'Paid Social';
            }
            if (in_array($medium, ['email', 'e-mail', 'newsletter'], true)) {
                return 'Email';
            }
            if (in_array($medium, ['affiliate', 'partner'], true)) {
                return 'Affiliate';
            }
        }

        // No referrer = Direct
        if (empty($referrer)) {
            return 'Direct';
        }

        // Parse referrer
        $ref_host = wp_parse_url($referrer, PHP_URL_HOST);
        if (!$ref_host) {
            return 'Direct';
        }

        $ref_host = strtolower($ref_host);

        // Search engines
        $search_engines = [
            'google' => 'Organic Search',
            'bing' => 'Organic Search',
            'yahoo' => 'Organic Search',
            'duckduckgo' => 'Organic Search',
            'baidu' => 'Organic Search',
            'yandex' => 'Organic Search',
        ];

        foreach ($search_engines as $engine => $channel) {
            if (strpos($ref_host, $engine) !== false) {
                return $channel;
            }
        }

        // Social networks
        $social_networks = [
            'facebook.com' => 'Social',
            'fb.com' => 'Social',
            'twitter.com' => 'Social',
            'x.com' => 'Social',
            't.co' => 'Social',
            'linkedin.com' => 'Social',
            'instagram.com' => 'Social',
            'pinterest.com' => 'Social',
            'youtube.com' => 'Social',
            'tiktok.com' => 'Social',
            'reddit.com' => 'Social',
        ];

        foreach ($social_networks as $domain => $channel) {
            if (strpos($ref_host, $domain) !== false) {
                return $channel;
            }
        }

        return 'Referral';
    }

    /**
     * Get geo location from IP (basic implementation).
     *
     * @param string $ip IP address.
     * @return array|null
     */
    public static function get_geo_location(string $ip): ?array {
        // This is a placeholder - in production you'd use a geo IP service
        // like MaxMind GeoLite2, ipinfo.io, or similar

        // For now, just return null
        // Could be extended with a filter for custom implementations
        return apply_filters('peanut_get_geo_location', null, $ip);
    }

    /**
     * Clean old events (for maintenance).
     *
     * @param int $days_to_keep Number of days to keep.
     * @return int Number of deleted rows.
     */
    public static function cleanup_old_events(int $days_to_keep = 90): int {
        global $wpdb;
        $events_table = Visitors_Database::get_events_table();

        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$events_table} WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $days_to_keep
            )
        );

        return (int) $deleted;
    }
}
