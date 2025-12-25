<?php
/**
 * Popups Triggers
 *
 * Handles popup display rules and trigger conditions.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Popups_Triggers {

    /**
     * Available trigger types
     */
    public const TRIGGER_TYPES = [
        'time_delay' => 'Time Delay',
        'time_on_page' => 'Time on Page',
        'scroll_percent' => 'Scroll Percentage',
        'scroll_depth' => 'Scroll Depth (Advanced)',
        'scroll_element' => 'Scroll to Element',
        'exit_intent' => 'Exit Intent',
        'aggressive_exit' => 'Aggressive Exit Detection',
        'click' => 'Click Element',
        'page_views' => 'Page Views Count',
        'inactivity' => 'User Inactivity',
        'engagement' => 'Engagement Score',
    ];

    /**
     * Available positions by popup type
     */
    public const POSITIONS = [
        'modal' => ['center', 'top', 'bottom'],
        'slide-in' => ['bottom-right', 'bottom-left', 'top-right', 'top-left'],
        'bar' => ['top', 'bottom'],
        'fullscreen' => ['center'],
    ];

    /**
     * Check if popup should display on current page
     */
    public function should_display(object $popup): bool {
        // Check status
        if ($popup->status !== 'active') {
            return false;
        }

        // Check scheduling
        if (!$this->is_within_schedule($popup)) {
            return false;
        }

        // Check display rules
        $rules = json_decode($popup->display_rules, true) ?? [];

        // Check page rules
        if (!$this->matches_page_rules($rules)) {
            return false;
        }

        // Check device rules
        if (!$this->matches_device_rules($rules)) {
            return false;
        }

        // Check user status rules
        if (!$this->matches_user_rules($rules)) {
            return false;
        }

        // Check referrer rules
        if (!$this->matches_referrer_rules($rules)) {
            return false;
        }

        return true;
    }

    /**
     * Check if within scheduled dates
     */
    private function is_within_schedule(object $popup): bool {
        $now = current_time('timestamp');

        if ($popup->start_date) {
            $start = strtotime($popup->start_date);
            if ($now < $start) {
                return false;
            }
        }

        if ($popup->end_date) {
            $end = strtotime($popup->end_date);
            if ($now > $end) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if current page matches display rules
     */
    private function matches_page_rules(array $rules): bool {
        $pages = $rules['pages'] ?? 'all';

        if ($pages === 'all') {
            return true;
        }

        if ($pages === 'homepage' && is_front_page()) {
            return true;
        }

        // Specific pages
        if (is_array($pages)) {
            $mode = $pages['mode'] ?? 'include';
            $page_ids = $pages['ids'] ?? [];
            $page_types = $pages['types'] ?? [];
            $url_patterns = $pages['url_patterns'] ?? [];

            $current_page_id = get_queried_object_id();
            $current_url = $_SERVER['REQUEST_URI'] ?? '';

            // Check page IDs
            if (!empty($page_ids)) {
                $matches = in_array($current_page_id, $page_ids);
                if ($mode === 'include' && !$matches) {
                    return false;
                }
                if ($mode === 'exclude' && $matches) {
                    return false;
                }
            }

            // Check page types
            if (!empty($page_types)) {
                $type_match = false;

                if (in_array('post', $page_types) && is_single()) {
                    $type_match = true;
                }
                if (in_array('page', $page_types) && is_page()) {
                    $type_match = true;
                }
                if (in_array('archive', $page_types) && is_archive()) {
                    $type_match = true;
                }
                if (in_array('category', $page_types) && is_category()) {
                    $type_match = true;
                }
                if (in_array('product', $page_types) && function_exists('is_product') && is_product()) {
                    $type_match = true;
                }
                if (in_array('shop', $page_types) && function_exists('is_shop') && is_shop()) {
                    $type_match = true;
                }

                if ($mode === 'include' && !$type_match) {
                    return false;
                }
                if ($mode === 'exclude' && $type_match) {
                    return false;
                }
            }

            // Check URL patterns
            if (!empty($url_patterns)) {
                $url_match = false;

                foreach ($url_patterns as $pattern) {
                    if (fnmatch($pattern, $current_url)) {
                        $url_match = true;
                        break;
                    }
                }

                if ($mode === 'include' && !$url_match) {
                    return false;
                }
                if ($mode === 'exclude' && $url_match) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Check if current device matches rules
     */
    private function matches_device_rules(array $rules): bool {
        $devices = $rules['devices'] ?? ['desktop', 'tablet', 'mobile'];

        if (empty($devices)) {
            return true;
        }

        $current_device = $this->detect_device();

        return in_array($current_device, $devices);
    }

    /**
     * Detect current device type
     */
    private function detect_device(): string {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        // Simple detection - could be enhanced with a library
        if (preg_match('/Mobile|Android|iPhone|iPod/i', $user_agent)) {
            if (preg_match('/iPad|Tablet/i', $user_agent)) {
                return 'tablet';
            }
            return 'mobile';
        }

        if (preg_match('/iPad/i', $user_agent)) {
            return 'tablet';
        }

        return 'desktop';
    }

    /**
     * Check if user status matches rules
     */
    private function matches_user_rules(array $rules): bool {
        $user_status = $rules['user_status'] ?? 'all';

        if ($user_status === 'all') {
            return true;
        }

        if ($user_status === 'logged_in' && !is_user_logged_in()) {
            return false;
        }

        if ($user_status === 'logged_out' && is_user_logged_in()) {
            return false;
        }

        // Check user roles
        if (is_array($user_status) && isset($user_status['roles'])) {
            if (!is_user_logged_in()) {
                return false;
            }

            $user = wp_get_current_user();
            $user_roles = $user->roles;
            $required_roles = $user_status['roles'];

            if (!array_intersect($user_roles, $required_roles)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if referrer matches rules
     */
    private function matches_referrer_rules(array $rules): bool {
        if (empty($rules['referrer'])) {
            return true;
        }

        $referrer = $_SERVER['HTTP_REFERER'] ?? '';

        if (empty($referrer)) {
            // Check if we should show to direct visitors
            return $rules['referrer']['include_direct'] ?? true;
        }

        $referrer_host = wp_parse_url($referrer, PHP_URL_HOST);
        $site_host = wp_parse_url(home_url(), PHP_URL_HOST);

        // Internal referrer
        if ($referrer_host === $site_host) {
            return $rules['referrer']['include_internal'] ?? true;
        }

        // Check specific referrer patterns
        if (!empty($rules['referrer']['patterns'])) {
            $mode = $rules['referrer']['mode'] ?? 'include';
            $matches = false;

            foreach ($rules['referrer']['patterns'] as $pattern) {
                if (stripos($referrer, $pattern) !== false) {
                    $matches = true;
                    break;
                }
            }

            if ($mode === 'include' && !$matches) {
                return false;
            }
            if ($mode === 'exclude' && $matches) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get trigger configuration for frontend
     */
    public function get_trigger_config(object $popup): array {
        $triggers = json_decode($popup->triggers, true) ?? [];
        $type = $triggers['type'] ?? 'time_delay';

        $config = [
            'type' => $type,
        ];

        switch ($type) {
            case 'time_delay':
                $config['delay'] = ($triggers['delay'] ?? 5) * 1000; // Convert to ms
                break;

            case 'time_on_page':
                $config['minTime'] = ($triggers['min_time'] ?? 30) * 1000;
                $config['requireScroll'] = $triggers['require_scroll'] ?? false;
                $config['requireEngagement'] = $triggers['require_engagement'] ?? false;
                break;

            case 'scroll_percent':
                $config['percent'] = $triggers['percent'] ?? 50;
                break;

            case 'scroll_depth':
                $config['percent'] = $triggers['percent'] ?? 50;
                $config['direction'] = $triggers['direction'] ?? 'down'; // down, up, both
                $config['minTime'] = ($triggers['min_time'] ?? 0) * 1000;
                $config['requireStop'] = $triggers['require_stop'] ?? false; // Wait for scroll pause
                break;

            case 'scroll_element':
                $config['selector'] = $triggers['selector'] ?? '';
                $config['offset'] = $triggers['offset'] ?? 0;
                break;

            case 'exit_intent':
                $config['sensitivity'] = $triggers['sensitivity'] ?? 20;
                $config['delay'] = ($triggers['delay'] ?? 0) * 1000;
                $config['mobileEnabled'] = $triggers['mobile_enabled'] ?? true;
                break;

            case 'aggressive_exit':
                $config['sensitivity'] = $triggers['sensitivity'] ?? 10;
                $config['delay'] = ($triggers['delay'] ?? 0) * 1000;
                $config['trackMouse'] = true;
                $config['trackTabs'] = $triggers['track_tabs'] ?? true;
                $config['trackBack'] = $triggers['track_back'] ?? true;
                $config['trackIdle'] = $triggers['track_idle'] ?? false;
                $config['idleTimeout'] = ($triggers['idle_timeout'] ?? 60) * 1000;
                break;

            case 'click':
                $config['selector'] = $triggers['selector'] ?? '';
                break;

            case 'page_views':
                $config['count'] = $triggers['count'] ?? 3;
                break;

            case 'inactivity':
                $config['timeout'] = ($triggers['timeout'] ?? 30) * 1000;
                break;

            case 'engagement':
                $config['minScrollPercent'] = $triggers['min_scroll'] ?? 25;
                $config['minTime'] = ($triggers['min_time'] ?? 15) * 1000;
                $config['minClicks'] = $triggers['min_clicks'] ?? 0;
                break;
        }

        return $config;
    }

    /**
     * Get available trigger types
     */
    public static function get_trigger_types(): array {
        return self::TRIGGER_TYPES;
    }

    /**
     * Get positions for popup type
     */
    public static function get_positions(string $type): array {
        return self::POSITIONS[$type] ?? ['center'];
    }
}
