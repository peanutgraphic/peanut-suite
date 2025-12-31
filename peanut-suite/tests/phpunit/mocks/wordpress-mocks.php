<?php
/**
 * WordPress Mock Functions
 *
 * Provides minimal mock implementations for WordPress functions
 * when running tests without the full WordPress test suite.
 */

if (!defined('ABSPATH')) {
    define('ABSPATH', '/tmp/wordpress/');
}

// Mock WordPress functions that are commonly used
if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
        global $wp_actions;
        if (!isset($wp_actions)) {
            $wp_actions = [];
        }
        $wp_actions[$hook][] = [
            'callback' => $callback,
            'priority' => $priority,
            'accepted_args' => $accepted_args,
        ];
    }
}

if (!function_exists('add_filter')) {
    function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {
        global $wp_filters;
        if (!isset($wp_filters)) {
            $wp_filters = [];
        }
        $wp_filters[$hook][] = [
            'callback' => $callback,
            'priority' => $priority,
            'accepted_args' => $accepted_args,
        ];
    }
}

if (!function_exists('do_action')) {
    function do_action($hook, ...$args) {
        global $wp_actions;
        if (isset($wp_actions[$hook])) {
            foreach ($wp_actions[$hook] as $action) {
                call_user_func_array($action['callback'], array_slice($args, 0, $action['accepted_args']));
            }
        }
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters($hook, $value, ...$args) {
        global $wp_filters;
        if (isset($wp_filters[$hook])) {
            foreach ($wp_filters[$hook] as $filter) {
                $value = call_user_func_array($filter['callback'], array_merge([$value], array_slice($args, 0, $filter['accepted_args'] - 1)));
            }
        }
        return $value;
    }
}

if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        global $wp_options;
        return $wp_options[$option] ?? $default;
    }
}

if (!function_exists('update_option')) {
    function update_option($option, $value, $autoload = null) {
        global $wp_options;
        if (!isset($wp_options)) {
            $wp_options = [];
        }
        $wp_options[$option] = $value;
        return true;
    }
}

if (!function_exists('delete_option')) {
    function delete_option($option) {
        global $wp_options;
        unset($wp_options[$option]);
        return true;
    }
}

if (!function_exists('get_current_user_id')) {
    function get_current_user_id() {
        return 1;
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can($capability) {
        return true;
    }
}

if (!function_exists('is_admin')) {
    function is_admin() {
        return true;
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) {
        return trim(strip_tags($str));
    }
}

if (!function_exists('sanitize_email')) {
    function sanitize_email($email) {
        return filter_var($email, FILTER_SANITIZE_EMAIL);
    }
}

if (!function_exists('sanitize_key')) {
    function sanitize_key($key) {
        return preg_replace('/[^a-z0-9_\-]/', '', strtolower($key));
    }
}

if (!function_exists('sanitize_textarea_field')) {
    function sanitize_textarea_field($str) {
        return trim(strip_tags($str));
    }
}

if (!function_exists('absint')) {
    function absint($value) {
        return abs((int) $value);
    }
}

if (!function_exists('wp_json_encode')) {
    function wp_json_encode($data, $options = 0, $depth = 512) {
        return json_encode($data, $options, $depth);
    }
}

if (!function_exists('is_email')) {
    function is_email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_url')) {
    function esc_url($url) {
        return filter_var($url, FILTER_SANITIZE_URL);
    }
}

if (!function_exists('__')) {
    function __($text, $domain = 'default') {
        return $text;
    }
}

if (!function_exists('_e')) {
    function _e($text, $domain = 'default') {
        echo $text;
    }
}

if (!function_exists('wp_send_json_success')) {
    function wp_send_json_success($data = null, $status_code = null) {
        $response = ['success' => true];
        if ($data !== null) {
            $response['data'] = $data;
        }
        return $response;
    }
}

if (!function_exists('wp_send_json_error')) {
    function wp_send_json_error($data = null, $status_code = null) {
        $response = ['success' => false];
        if ($data !== null) {
            $response['data'] = $data;
        }
        return $response;
    }
}

if (!class_exists('WP_Error')) {
    class WP_Error {
        private $errors = [];
        private $error_data = [];

        public function __construct($code = '', $message = '', $data = '') {
            if (!empty($code)) {
                $this->errors[$code][] = $message;
                if (!empty($data)) {
                    $this->error_data[$code] = $data;
                }
            }
        }

        public function get_error_message($code = '') {
            if (empty($code)) {
                $code = array_key_first($this->errors);
            }
            return $this->errors[$code][0] ?? '';
        }

        public function get_error_code() {
            return array_key_first($this->errors) ?? '';
        }

        public function get_error_data($code = '') {
            if (empty($code)) {
                $code = $this->get_error_code();
            }
            return $this->error_data[$code] ?? null;
        }
    }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error($thing) {
        return $thing instanceof WP_Error;
    }
}

if (!function_exists('wp_salt')) {
    function wp_salt($scheme = 'auth') {
        return 'test-salt-for-' . $scheme;
    }
}

if (!function_exists('wp_generate_password')) {
    function wp_generate_password($length = 12, $special_chars = true, $extra_special_chars = false) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        if ($special_chars) {
            $chars .= '!@#$%^&*()';
        }
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $password;
    }
}

if (!function_exists('wp_hash_password')) {
    function wp_hash_password($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
}

if (!function_exists('wp_check_password')) {
    function wp_check_password($password, $hash, $user_id = '') {
        return password_verify($password, $hash);
    }
}

// Mock database class
if (!class_exists('wpdb')) {
    class wpdb {
        public $prefix = 'wp_';
        public $insert_id = 0;
        private $results = [];

        public function prepare($query, ...$args) {
            return vsprintf(str_replace(['%s', '%d'], ["'%s'", '%d'], $query), $args);
        }

        public function get_results($query, $output = OBJECT) {
            return $this->results;
        }

        public function get_row($query, $output = OBJECT, $y = 0) {
            return $this->results[0] ?? null;
        }

        public function get_var($query, $x = 0, $y = 0) {
            return null;
        }

        public function insert($table, $data, $format = null) {
            $this->insert_id = rand(1, 10000);
            return 1;
        }

        public function update($table, $data, $where, $format = null, $where_format = null) {
            return 1;
        }

        public function delete($table, $where, $where_format = null) {
            return 1;
        }

        public function query($query) {
            return true;
        }

        public function set_mock_results($results) {
            $this->results = $results;
        }
    }
}

// Global wpdb instance
global $wpdb;
if (!isset($wpdb)) {
    $wpdb = new wpdb();
}

// Define OBJECT constant if not defined
if (!defined('OBJECT')) {
    define('OBJECT', 'OBJECT');
}

if (!defined('ARRAY_A')) {
    define('ARRAY_A', 'ARRAY_A');
}

if (!defined('ARRAY_N')) {
    define('ARRAY_N', 'ARRAY_N');
}
