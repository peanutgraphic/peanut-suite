<?php
/**
 * Unit tests for Peanut_Security class
 */

require_once dirname(__DIR__) . '/bootstrap.php';
require_once PEANUT_PLUGIN_DIR . 'core/services/class-peanut-security.php';

class SecurityTest extends Peanut_Test_Case {

    // =========================================
    // Field Sanitization Tests
    // =========================================

    /**
     * Test email field sanitization
     *
     * @dataProvider emailDataProvider
     */
    public function test_sanitize_email_field(string $key, string $input, string $expected): void {
        $result = Peanut_Security::sanitize_field($key, $input);
        $this->assertEquals($expected, $result);
    }

    public static function emailDataProvider(): array {
        return [
            'valid email' => ['email', 'test@example.com', 'test@example.com'],
            // Email with leading/trailing spaces fails validation (spaces not trimmed before validation)
            'email with spaces returns empty' => ['user_email', '  test@example.com  ', ''],
            'invalid email returns empty' => ['contact_email', 'not-an-email', ''],
            'email key case insensitive' => ['EMAIL', 'valid@test.org', 'valid@test.org'],
            'email with plus addressing' => ['email', 'user+tag@example.com', 'user+tag@example.com'],
        ];
    }

    /**
     * Test phone field sanitization
     *
     * @dataProvider phoneDataProvider
     */
    public function test_sanitize_phone_field(string $key, string $input, string $expected): void {
        $result = Peanut_Security::sanitize_field($key, $input);
        $this->assertEquals($expected, $result);
    }

    public static function phoneDataProvider(): array {
        return [
            'simple phone' => ['phone', '555-1234', '555-1234'],
            'international phone' => ['mobile', '+1 (555) 123-4567', '+1 (555) 123-4567'],
            'phone with letters removed' => ['tel', '555-CALL', '555-'],
            'phone with special chars removed' => ['phone_number', '555@123#456', '555123456'],
        ];
    }

    /**
     * Test URL field sanitization
     *
     * @dataProvider urlDataProvider
     */
    public function test_sanitize_url_field(string $key, string $input, string $expected): void {
        $result = Peanut_Security::sanitize_field($key, $input);
        $this->assertEquals($expected, $result);
    }

    public static function urlDataProvider(): array {
        return [
            'valid url' => ['website', 'https://example.com', 'https://example.com'],
            'url with path' => ['site_url', 'https://example.com/page', 'https://example.com/page'],
            'link field' => ['link', 'http://test.com', 'http://test.com'],
        ];
    }

    /**
     * Test ZIP/postal code sanitization
     */
    public function test_sanitize_zip_field(): void {
        $this->assertEquals('12345', Peanut_Security::sanitize_field('zip', '12345'));
        $this->assertEquals('12345-6789', Peanut_Security::sanitize_field('zip_code', '12345-6789'));
        $this->assertEquals('K1A 0B1', Peanut_Security::sanitize_field('postal_code', 'K1A 0B1'));
        $this->assertEquals('123456789', Peanut_Security::sanitize_field('zip', '12345!@#$6789'));
    }

    /**
     * Test state/province sanitization
     */
    public function test_sanitize_state_field(): void {
        $this->assertEquals('CA', Peanut_Security::sanitize_field('state', 'California'));
        $this->assertEquals('NY', Peanut_Security::sanitize_field('state', 'NY'));
        $this->assertEquals('ON', Peanut_Security::sanitize_field('province', 'Ontario'));
        $this->assertEquals('TX', Peanut_Security::sanitize_field('state', 'TX123'));
    }

    /**
     * Test account/ID field sanitization
     */
    public function test_sanitize_account_id_field(): void {
        $this->assertEquals('ABC-123', Peanut_Security::sanitize_field('account_id', 'ABC-123'));
        $this->assertEquals('12345', Peanut_Security::sanitize_field('customer_number', '12345'));
        $this->assertEquals('ACC123', Peanut_Security::sanitize_field('account', 'ACC@#$123'));
    }

    /**
     * Test UTM parameter sanitization
     *
     * @dataProvider utmDataProvider
     */
    public function test_sanitize_utm_field(string $key, string $input, string $expected): void {
        $result = Peanut_Security::sanitize_field($key, $input);
        $this->assertEquals($expected, $result);
    }

    public static function utmDataProvider(): array {
        return [
            'utm_source' => ['utm_source', 'google', 'google'],
            'utm_medium' => ['utm_medium', 'cpc', 'cpc'],
            'utm_campaign' => ['utm_campaign', 'summer-2024', 'summer-2024'],
            'utm with underscore' => ['utm_content', 'ad_variation_1', 'ad_variation_1'],
            'utm with plus' => ['utm_term', 'keyword+phrase', 'keyword+phrase'],
            'utm with percent' => ['utm_source', 'test%20value', 'test%20value'],
            'utm special chars removed' => ['utm_source', 'test<script>', 'testscript'],
        ];
    }

    /**
     * Test generic text sanitization
     */
    public function test_sanitize_generic_text_field(): void {
        $result = Peanut_Security::sanitize_field('name', 'John Doe');
        $this->assertEquals('John Doe', $result);

        $result = Peanut_Security::sanitize_field('description', '  Trimmed  ');
        $this->assertEquals('Trimmed', $result);
    }

    /**
     * Test non-string values return empty
     */
    public function test_sanitize_non_string_returns_empty(): void {
        $this->assertEquals('', Peanut_Security::sanitize_field('test', []));
        $this->assertEquals('', Peanut_Security::sanitize_field('test', (object) []));
        $this->assertEquals('', Peanut_Security::sanitize_field('test', null));
    }

    /**
     * Test numeric values are converted to string
     */
    public function test_sanitize_numeric_values(): void {
        $this->assertEquals('123', Peanut_Security::sanitize_field('number', 123));
        $this->assertEquals('123.45', Peanut_Security::sanitize_field('price', 123.45));
    }

    // =========================================
    // Array Sanitization Tests
    // =========================================

    /**
     * Test sanitize_fields with array
     */
    public function test_sanitize_fields_array(): void {
        $data = [
            'email' => 'test@example.com',
            'phone' => '555-1234',
            'name' => 'John Doe',
        ];

        $result = Peanut_Security::sanitize_fields($data);

        $this->assertEquals('test@example.com', $result['email']);
        $this->assertEquals('555-1234', $result['phone']);
        $this->assertEquals('John Doe', $result['name']);
    }

    /**
     * Test sanitize_fields with nested array
     */
    public function test_sanitize_fields_nested_array(): void {
        $data = [
            'user' => [
                'email' => 'test@example.com',
                'phone' => '555-1234',
            ],
            'name' => 'Test',
        ];

        $result = Peanut_Security::sanitize_fields($data);

        $this->assertEquals('test@example.com', $result['user']['email']);
        $this->assertEquals('555-1234', $result['user']['phone']);
    }

    /**
     * Test sanitize_fields with skip_keys
     */
    public function test_sanitize_fields_with_skip_keys(): void {
        $data = [
            'email' => 'should@sanitize.com',
            'raw_html' => '<script>alert("xss")</script>',
        ];

        $result = Peanut_Security::sanitize_fields($data, ['raw_html']);

        $this->assertEquals('should@sanitize.com', $result['email']);
        $this->assertEquals('<script>alert("xss")</script>', $result['raw_html']);
    }

    // =========================================
    // Rate Limiting Tests
    // =========================================

    /**
     * Test first request is not rate limited
     */
    public function test_first_request_not_rate_limited(): void {
        $result = Peanut_Security::check_rate_limit('test_action', 10, 60, '192.168.1.1');
        $this->assertTrue($result);
    }

    /**
     * Test requests within limit are allowed
     */
    public function test_requests_within_limit_allowed(): void {
        $identifier = '192.168.1.2';

        for ($i = 0; $i < 5; $i++) {
            $result = Peanut_Security::check_rate_limit('test_action', 10, 60, $identifier);
            $this->assertTrue($result, "Request $i should be allowed");
        }
    }

    /**
     * Test requests exceeding limit are blocked
     */
    public function test_requests_exceeding_limit_blocked(): void {
        $identifier = '192.168.1.3';

        // Make 10 requests (the limit)
        for ($i = 0; $i < 10; $i++) {
            Peanut_Security::check_rate_limit('block_test', 10, 60, $identifier);
        }

        // 11th request should be blocked
        $result = Peanut_Security::check_rate_limit('block_test', 10, 60, $identifier);
        $this->assertFalse($result);
    }

    /**
     * Test get_rate_limit_remaining
     */
    public function test_get_rate_limit_remaining(): void {
        $identifier = '192.168.1.4';

        // Initially should have full limit
        $remaining = Peanut_Security::get_rate_limit_remaining('remaining_test', 10, $identifier);
        $this->assertEquals(10, $remaining);

        // After 3 requests
        Peanut_Security::check_rate_limit('remaining_test', 10, 60, $identifier);
        Peanut_Security::check_rate_limit('remaining_test', 10, 60, $identifier);
        Peanut_Security::check_rate_limit('remaining_test', 10, 60, $identifier);

        $remaining = Peanut_Security::get_rate_limit_remaining('remaining_test', 10, $identifier);
        $this->assertEquals(7, $remaining);
    }

    /**
     * Test rate limiting is per action
     */
    public function test_rate_limit_per_action(): void {
        $identifier = '192.168.1.5';

        // Hit limit on action1
        for ($i = 0; $i < 10; $i++) {
            Peanut_Security::check_rate_limit('action1', 10, 60, $identifier);
        }

        // action1 should be blocked
        $this->assertFalse(Peanut_Security::check_rate_limit('action1', 10, 60, $identifier));

        // action2 should still work
        $this->assertTrue(Peanut_Security::check_rate_limit('action2', 10, 60, $identifier));
    }

    /**
     * Test rate limiting is per identifier
     */
    public function test_rate_limit_per_identifier(): void {
        // Hit limit for identifier1
        for ($i = 0; $i < 10; $i++) {
            Peanut_Security::check_rate_limit('per_id_test', 10, 60, 'identifier1');
        }

        // identifier1 blocked
        $this->assertFalse(Peanut_Security::check_rate_limit('per_id_test', 10, 60, 'identifier1'));

        // identifier2 still works
        $this->assertTrue(Peanut_Security::check_rate_limit('per_id_test', 10, 60, 'identifier2'));
    }

    // =========================================
    // URL Safety Tests
    // =========================================

    /**
     * Test is_safe_url with valid URLs
     *
     * @dataProvider safeUrlDataProvider
     */
    public function test_is_safe_url_valid(string $url, bool $expected): void {
        $result = Peanut_Security::is_safe_url($url);
        $this->assertEquals($expected, $result, "URL '$url' safety check failed");
    }

    public static function safeUrlDataProvider(): array {
        return [
            'https url' => ['https://example.com', true],
            'http url' => ['http://example.com', true],
            'with path' => ['https://example.com/path/to/page', true],
            'with query' => ['https://example.com?foo=bar', true],
            'localhost blocked' => ['http://localhost', false],
            'localhost ip blocked' => ['http://127.0.0.1', false],
            '0.0.0.0 blocked' => ['http://0.0.0.0', false],
            'ftp not allowed' => ['ftp://example.com', false],
            'invalid url' => ['not-a-url', false],
            'empty string' => ['', false],
        ];
    }

    /**
     * Test is_safe_url with allowed hosts
     */
    public function test_is_safe_url_with_allowed_hosts(): void {
        $allowed = ['example.com', 'trusted.org'];

        $this->assertTrue(Peanut_Security::is_safe_url('https://example.com', $allowed));
        $this->assertTrue(Peanut_Security::is_safe_url('https://trusted.org/page', $allowed));
        $this->assertFalse(Peanut_Security::is_safe_url('https://evil.com', $allowed));
    }

    // =========================================
    // Client IP Tests
    // =========================================

    /**
     * Test get_client_ip with various headers
     */
    public function test_get_client_ip_from_cloudflare(): void {
        $_SERVER['HTTP_CF_CONNECTING_IP'] = '1.2.3.4';
        $ip = Peanut_Security::get_client_ip();
        $this->assertEquals('1.2.3.4', $ip);
        unset($_SERVER['HTTP_CF_CONNECTING_IP']);
    }

    public function test_get_client_ip_from_forwarded(): void {
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '5.6.7.8, 9.10.11.12';
        $ip = Peanut_Security::get_client_ip();
        $this->assertEquals('5.6.7.8', $ip);
        unset($_SERVER['HTTP_X_FORWARDED_FOR']);
    }

    public function test_get_client_ip_from_remote_addr(): void {
        $_SERVER['REMOTE_ADDR'] = '203.0.113.50';
        $ip = Peanut_Security::get_client_ip();
        $this->assertEquals('203.0.113.50', $ip);
        unset($_SERVER['REMOTE_ADDR']);
    }

    public function test_get_client_ip_returns_default(): void {
        // Clear all IP headers
        unset(
            $_SERVER['HTTP_CF_CONNECTING_IP'],
            $_SERVER['HTTP_X_FORWARDED_FOR'],
            $_SERVER['HTTP_X_REAL_IP'],
            $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'],
            $_SERVER['HTTP_CLIENT_IP'],
            $_SERVER['REMOTE_ADDR']
        );

        $ip = Peanut_Security::get_client_ip();
        $this->assertEquals('0.0.0.0', $ip);
    }

    // =========================================
    // Nonce/CSRF Tests
    // =========================================

    /**
     * Test verify_nonce
     */
    public function test_verify_nonce_valid(): void {
        // Mock wp_verify_nonce to return 1 (valid)
        $result = Peanut_Security::verify_nonce('valid_nonce', 'test_action');
        $this->assertTrue($result);
    }

    /**
     * Test CSRF token creation
     */
    public function test_create_csrf_token(): void {
        $token = Peanut_Security::create_csrf_token('test_action');
        $this->assertNotEmpty($token);
        $this->assertIsString($token);
    }

    /**
     * Test CSRF token verification
     */
    public function test_verify_csrf_token(): void {
        // With mocked wp_verify_nonce, this should work
        $result = Peanut_Security::verify_csrf_token('test_token', 'test_action');
        $this->assertTrue($result);
    }
}
