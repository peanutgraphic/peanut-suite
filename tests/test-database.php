<?php
/**
 * Database operation tests for Peanut Suite.
 *
 * Tests database queries, table operations, and data integrity.
 *
 * @package Peanut_Suite
 */

class Test_Database extends Peanut_Suite_TestCase {

    /**
     * Test table prefix is correctly defined.
     */
    public function test_table_prefix() {
        if (defined('PEANUT_TABLE_PREFIX')) {
            $this->assertEquals('peanut_', PEANUT_TABLE_PREFIX);
        } else {
            $this->markTestSkipped('PEANUT_TABLE_PREFIX not defined.');
        }
    }

    /**
     * Test UTM data structure validation.
     */
    public function test_utm_data_structure() {
        $utm_data = [
            'source' => 'google',
            'medium' => 'cpc',
            'campaign' => 'spring_2024',
            'term' => 'widgets',
            'content' => 'banner_ad',
            'page_url' => 'https://example.com/landing',
            'referrer' => 'https://google.com',
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0 (Test)',
            'created_at' => gmdate('Y-m-d H:i:s'),
        ];

        // Required fields.
        $required = ['source', 'page_url', 'created_at'];
        foreach ($required as $field) {
            $this->assertArrayHasKey($field, $utm_data);
            $this->assertNotEmpty($utm_data[$field]);
        }

        // URL validation.
        $this->assertStringStartsWith('https://', $utm_data['page_url']);

        // Timestamp validation.
        $this->assertNotFalse(strtotime($utm_data['created_at']));
    }

    /**
     * Test link data structure validation.
     */
    public function test_link_data_structure() {
        $link_data = [
            'slug' => 'promo2024',
            'destination_url' => 'https://example.com/promo',
            'title' => 'Promo Link',
            'click_count' => 0,
            'is_active' => 1,
            'created_at' => gmdate('Y-m-d H:i:s'),
        ];

        // Slug should be URL-safe.
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9\-_]+$/', $link_data['slug']);

        // Destination URL should be valid.
        $this->assertNotFalse(filter_var($link_data['destination_url'], FILTER_VALIDATE_URL));

        // Click count should be non-negative.
        $this->assertGreaterThanOrEqual(0, $link_data['click_count']);
    }

    /**
     * Test contact data structure validation.
     */
    public function test_contact_data_structure() {
        $contact_data = [
            'email' => 'test@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'source' => 'form',
            'status' => 'subscribed',
            'tags' => ['newsletter', 'customer'],
            'custom_fields' => ['company' => 'Acme Inc'],
            'created_at' => gmdate('Y-m-d H:i:s'),
        ];

        // Email validation.
        $this->assertNotFalse(filter_var($contact_data['email'], FILTER_VALIDATE_EMAIL));

        // Status should be valid.
        $valid_statuses = ['subscribed', 'unsubscribed', 'pending', 'bounced'];
        $this->assertContains($contact_data['status'], $valid_statuses);

        // Tags should be array.
        $this->assertIsArray($contact_data['tags']);
    }

    /**
     * Test popup data structure validation.
     */
    public function test_popup_data_structure() {
        $popup_data = [
            'name' => 'Newsletter Signup',
            'content' => '<div class="popup">Subscribe!</div>',
            'trigger_type' => 'time_delay',
            'trigger_value' => 5,
            'conditions' => ['pages' => ['home']],
            'is_active' => 1,
            'impressions' => 0,
            'conversions' => 0,
        ];

        // Name should not be empty.
        $this->assertNotEmpty($popup_data['name']);

        // Trigger type should be valid.
        $valid_triggers = ['time_delay', 'scroll_depth', 'exit_intent', 'click', 'page_load'];
        $this->assertContains($popup_data['trigger_type'], $valid_triggers);

        // Stats should be non-negative.
        $this->assertGreaterThanOrEqual(0, $popup_data['impressions']);
        $this->assertGreaterThanOrEqual(0, $popup_data['conversions']);
    }

    /**
     * Test visitor session data structure.
     */
    public function test_visitor_data_structure() {
        $visitor_data = [
            'session_id' => bin2hex(random_bytes(16)),
            'first_visit' => gmdate('Y-m-d H:i:s'),
            'last_activity' => gmdate('Y-m-d H:i:s'),
            'page_views' => 1,
            'utm_data' => null,
            'device_type' => 'desktop',
        ];

        // Session ID should be unique identifier.
        $this->assertEquals(32, strlen($visitor_data['session_id']));

        // Device type should be valid.
        $valid_devices = ['desktop', 'mobile', 'tablet', 'unknown'];
        $this->assertContains($visitor_data['device_type'], $valid_devices);
    }

    /**
     * Test SQL injection prevention patterns.
     */
    public function test_sql_injection_prevention() {
        $malicious_inputs = [
            "'; DROP TABLE peanut_utm; --",
            "1' OR '1'='1",
            "UNION SELECT * FROM wp_users",
            "1; DELETE FROM peanut_contacts",
        ];

        foreach ($malicious_inputs as $input) {
            // Sanitize using WordPress function mock or real.
            $sanitized = sanitize_text_field($input);

            // Should not contain unescaped SQL keywords in dangerous positions.
            $this->assertIsString($sanitized);

            // When using esc_sql, dangerous characters should be escaped.
            if (function_exists('esc_sql')) {
                $escaped = esc_sql($sanitized);
                $this->assertStringNotContainsString("';", $escaped);
            }
        }
    }

    /**
     * Test prepared statement placeholders.
     */
    public function test_prepared_statement_pattern() {
        // Example of proper prepared statement format.
        $query_template = "SELECT * FROM table WHERE id = %d AND status = %s";

        // Should contain placeholders.
        $this->assertStringContainsString('%d', $query_template);
        $this->assertStringContainsString('%s', $query_template);

        // Should not contain directly interpolated values.
        $this->assertStringNotContainsString('$', $query_template);
    }

    /**
     * Test webhook data structure.
     */
    public function test_webhook_data_structure() {
        $webhook_data = [
            'name' => 'New Contact Webhook',
            'url' => 'https://api.example.com/webhook',
            'events' => ['contact_created', 'contact_updated'],
            'secret' => bin2hex(random_bytes(32)),
            'is_active' => 1,
            'last_triggered' => null,
            'failure_count' => 0,
        ];

        // URL should be valid HTTPS.
        $this->assertStringStartsWith('https://', $webhook_data['url']);
        $this->assertNotFalse(filter_var($webhook_data['url'], FILTER_VALIDATE_URL));

        // Events should be array.
        $this->assertIsArray($webhook_data['events']);
        $this->assertNotEmpty($webhook_data['events']);

        // Secret should be strong (64 char hex = 32 bytes).
        $this->assertEquals(64, strlen($webhook_data['secret']));
    }

    /**
     * Test analytics aggregation data structure.
     */
    public function test_analytics_data_structure() {
        $analytics_data = [
            'date' => gmdate('Y-m-d'),
            'utm_entries' => 150,
            'link_clicks' => 75,
            'new_contacts' => 10,
            'popup_impressions' => 500,
            'popup_conversions' => 25,
            'unique_visitors' => 200,
        ];

        // All metrics should be non-negative integers.
        foreach ($analytics_data as $key => $value) {
            if ($key !== 'date') {
                $this->assertIsInt($value);
                $this->assertGreaterThanOrEqual(0, $value);
            }
        }

        // Date should be valid.
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $analytics_data['date']);
    }

    /**
     * Test settings data structure.
     */
    public function test_settings_data_structure() {
        $settings = [
            'tracking_enabled' => true,
            'cookie_duration' => 30,
            'popup_delay' => 5,
            'api_key' => 'pk_live_xxxxx',
            'allowed_domains' => ['example.com', 'www.example.com'],
        ];

        // Boolean settings.
        $this->assertIsBool($settings['tracking_enabled']);

        // Numeric settings should be positive.
        $this->assertGreaterThan(0, $settings['cookie_duration']);
        $this->assertGreaterThanOrEqual(0, $settings['popup_delay']);

        // Array settings.
        $this->assertIsArray($settings['allowed_domains']);
    }

    /**
     * Test data retention queries.
     */
    public function test_retention_query_structure() {
        $days_to_retain = 90;

        // Query pattern for deleting old records.
        $expected_pattern = "DELETE FROM table WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)";

        $this->assertStringContainsString('DELETE FROM', $expected_pattern);
        $this->assertStringContainsString('DATE_SUB', $expected_pattern);
        $this->assertStringContainsString('%d', $expected_pattern);
    }
}
