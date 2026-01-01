<?php
/**
 * Unit tests for Attribution_Calculator class
 *
 * Tests channel determination and pure functions.
 */

require_once dirname(__DIR__) . '/bootstrap.php';
require_once PEANUT_PLUGIN_DIR . 'modules/attribution/class-attribution-calculator.php';

use PeanutSuite\Attribution\Attribution_Calculator;

class AttributionCalculatorTest extends Peanut_Test_Case {

    // =========================================
    // Constants Tests
    // =========================================

    /**
     * Test default lookback days constant
     */
    public function test_default_lookback_days(): void {
        $reflection = new ReflectionClass(Attribution_Calculator::class);
        $constant = $reflection->getConstant('DEFAULT_LOOKBACK_DAYS');

        $this->assertEquals(30, $constant);
    }

    // =========================================
    // Channel Determination Tests - UTM Medium
    // =========================================

    /**
     * Test determine_channel with paid search UTM
     *
     * @dataProvider paidSearchMediumProvider
     */
    public function test_determine_channel_paid_search_medium(string $medium): void {
        $channel = Attribution_Calculator::determine_channel(null, [
            'utm_medium' => $medium,
        ]);

        $this->assertEquals('Paid Search', $channel);
    }

    public static function paidSearchMediumProvider(): array {
        return [
            'cpc' => ['cpc'],
            'CPC uppercase' => ['CPC'],
            'ppc' => ['ppc'],
            'paid' => ['paid'],
            'paidsearch' => ['paidsearch'],
        ];
    }

    /**
     * Test determine_channel with display UTM
     *
     * @dataProvider displayMediumProvider
     */
    public function test_determine_channel_display_medium(string $medium): void {
        $channel = Attribution_Calculator::determine_channel(null, [
            'utm_medium' => $medium,
        ]);

        $this->assertEquals('Display', $channel);
    }

    public static function displayMediumProvider(): array {
        return [
            'display' => ['display'],
            'banner' => ['banner'],
            'cpm' => ['cpm'],
        ];
    }

    /**
     * Test determine_channel with social UTM
     */
    public function test_determine_channel_social_medium(): void {
        $channel = Attribution_Calculator::determine_channel(null, [
            'utm_medium' => 'social',
        ]);

        $this->assertEquals('Social', $channel);
    }

    /**
     * Test determine_channel with paid social UTM
     */
    public function test_determine_channel_paid_social(): void {
        $channel = Attribution_Calculator::determine_channel(null, [
            'utm_medium' => 'social',
            'utm_source' => 'facebook-paid',
        ]);

        $this->assertEquals('Paid Social', $channel);
    }

    /**
     * Test determine_channel with email UTM
     *
     * @dataProvider emailMediumProvider
     */
    public function test_determine_channel_email_medium(string $medium): void {
        $channel = Attribution_Calculator::determine_channel(null, [
            'utm_medium' => $medium,
        ]);

        $this->assertEquals('Email', $channel);
    }

    public static function emailMediumProvider(): array {
        return [
            'email' => ['email'],
            'e-mail' => ['e-mail'],
            'newsletter' => ['newsletter'],
        ];
    }

    /**
     * Test determine_channel with affiliate UTM
     *
     * @dataProvider affiliateMediumProvider
     */
    public function test_determine_channel_affiliate_medium(string $medium): void {
        $channel = Attribution_Calculator::determine_channel(null, [
            'utm_medium' => $medium,
        ]);

        $this->assertEquals('Affiliate', $channel);
    }

    public static function affiliateMediumProvider(): array {
        return [
            'affiliate' => ['affiliate'],
            'partner' => ['partner'],
            'referral' => ['referral'],
        ];
    }

    /**
     * Test determine_channel with organic UTM
     */
    public function test_determine_channel_organic_medium(): void {
        $channel = Attribution_Calculator::determine_channel(null, [
            'utm_medium' => 'organic',
        ]);

        $this->assertEquals('Organic Search', $channel);
    }

    // =========================================
    // Channel Determination Tests - UTM Source
    // =========================================

    /**
     * Test determine_channel with search engine source
     *
     * @dataProvider searchEngineSourceProvider
     */
    public function test_determine_channel_search_engine_source(string $source): void {
        $channel = Attribution_Calculator::determine_channel(null, [
            'utm_source' => $source,
        ]);

        $this->assertEquals('Organic Search', $channel);
    }

    public static function searchEngineSourceProvider(): array {
        return [
            'google' => ['google'],
            'bing' => ['bing'],
            'yahoo' => ['yahoo'],
            'duckduckgo' => ['duckduckgo'],
        ];
    }

    /**
     * Test determine_channel with social source
     *
     * @dataProvider socialSourceProvider
     */
    public function test_determine_channel_social_source(string $source): void {
        $channel = Attribution_Calculator::determine_channel(null, [
            'utm_source' => $source,
        ]);

        $this->assertEquals('Social', $channel);
    }

    public static function socialSourceProvider(): array {
        return [
            'facebook' => ['facebook'],
            'twitter' => ['twitter'],
            'linkedin' => ['linkedin'],
            'instagram' => ['instagram'],
            'tiktok' => ['tiktok'],
        ];
    }

    // =========================================
    // Channel Determination Tests - Referrer
    // =========================================

    /**
     * Test determine_channel with no referrer returns Direct
     */
    public function test_determine_channel_no_referrer(): void {
        $channel = Attribution_Calculator::determine_channel(null, []);
        $this->assertEquals('Direct', $channel);
    }

    /**
     * Test determine_channel with empty referrer returns Direct
     */
    public function test_determine_channel_empty_referrer(): void {
        $channel = Attribution_Calculator::determine_channel('', []);
        $this->assertEquals('Direct', $channel);
    }

    /**
     * Test determine_channel with search engine referrer
     *
     * @dataProvider searchEngineReferrerProvider
     */
    public function test_determine_channel_search_engine_referrer(string $referrer): void {
        $channel = Attribution_Calculator::determine_channel($referrer, []);
        $this->assertEquals('Organic Search', $channel);
    }

    public static function searchEngineReferrerProvider(): array {
        return [
            'google' => ['https://www.google.com/search?q=test'],
            'bing' => ['https://www.bing.com/search?q=test'],
            'yahoo' => ['https://search.yahoo.com/search?p=test'],
            'duckduckgo' => ['https://duckduckgo.com/?q=test'],
            'baidu' => ['https://www.baidu.com/s?wd=test'],
            'yandex' => ['https://yandex.com/search/?text=test'],
        ];
    }

    /**
     * Test determine_channel with social network referrer
     *
     * @dataProvider socialNetworkReferrerProvider
     */
    public function test_determine_channel_social_network_referrer(string $referrer): void {
        $channel = Attribution_Calculator::determine_channel($referrer, []);
        $this->assertEquals('Social', $channel);
    }

    public static function socialNetworkReferrerProvider(): array {
        return [
            'facebook' => ['https://www.facebook.com/post/123'],
            'fb.com' => ['https://fb.com/page'],
            'twitter' => ['https://twitter.com/user/status/123'],
            'x.com' => ['https://x.com/user/status/123'],
            't.co' => ['https://t.co/abc123'],
            'linkedin' => ['https://www.linkedin.com/feed'],
            'instagram' => ['https://www.instagram.com/p/abc'],
            'pinterest' => ['https://www.pinterest.com/pin/123'],
            'youtube' => ['https://www.youtube.com/watch?v=abc'],
            'tiktok' => ['https://www.tiktok.com/@user/video/123'],
            'reddit' => ['https://www.reddit.com/r/programming'],
        ];
    }

    /**
     * Test determine_channel with unknown referrer returns Referral
     */
    public function test_determine_channel_unknown_referrer(): void {
        $channel = Attribution_Calculator::determine_channel('https://some-blog.com/article', []);
        $this->assertEquals('Referral', $channel);
    }

    /**
     * Test determine_channel with invalid referrer URL returns Direct
     */
    public function test_determine_channel_invalid_referrer(): void {
        $channel = Attribution_Calculator::determine_channel('not-a-url', []);
        $this->assertEquals('Direct', $channel);
    }

    // =========================================
    // Channel Determination Priority Tests
    // =========================================

    /**
     * Test UTM medium takes priority over referrer
     */
    public function test_utm_medium_priority_over_referrer(): void {
        // Even with a Google referrer, if utm_medium is 'email', channel should be Email
        $channel = Attribution_Calculator::determine_channel(
            'https://www.google.com/search?q=test',
            ['utm_medium' => 'email']
        );

        $this->assertEquals('Email', $channel);
    }

    /**
     * Test UTM source takes priority over referrer when no medium
     */
    public function test_utm_source_priority_when_no_medium(): void {
        // With Facebook source and random referrer
        $channel = Attribution_Calculator::determine_channel(
            'https://some-blog.com/article',
            ['utm_source' => 'facebook']
        );

        $this->assertEquals('Social', $channel);
    }

    // =========================================
    // Case Insensitivity Tests
    // =========================================

    /**
     * Test UTM medium is case insensitive
     */
    public function test_utm_medium_case_insensitive(): void {
        $channel1 = Attribution_Calculator::determine_channel(null, ['utm_medium' => 'CPC']);
        $channel2 = Attribution_Calculator::determine_channel(null, ['utm_medium' => 'cpc']);
        $channel3 = Attribution_Calculator::determine_channel(null, ['utm_medium' => 'Cpc']);

        $this->assertEquals('Paid Search', $channel1);
        $this->assertEquals('Paid Search', $channel2);
        $this->assertEquals('Paid Search', $channel3);
    }

    /**
     * Test UTM source is case insensitive
     */
    public function test_utm_source_case_insensitive(): void {
        $channel1 = Attribution_Calculator::determine_channel(null, ['utm_source' => 'GOOGLE']);
        $channel2 = Attribution_Calculator::determine_channel(null, ['utm_source' => 'google']);
        $channel3 = Attribution_Calculator::determine_channel(null, ['utm_source' => 'Google']);

        $this->assertEquals('Organic Search', $channel1);
        $this->assertEquals('Organic Search', $channel2);
        $this->assertEquals('Organic Search', $channel3);
    }

    // =========================================
    // Edge Cases
    // =========================================

    /**
     * Test with null UTM params
     */
    public function test_null_utm_params(): void {
        $channel = Attribution_Calculator::determine_channel(null, [
            'utm_source' => null,
            'utm_medium' => null,
        ]);

        $this->assertEquals('Direct', $channel);
    }

    /**
     * Test with empty UTM strings
     */
    public function test_empty_utm_strings(): void {
        $channel = Attribution_Calculator::determine_channel(null, [
            'utm_source' => '',
            'utm_medium' => '',
        ]);

        $this->assertEquals('Direct', $channel);
    }

    /**
     * Test referrer with port number
     */
    public function test_referrer_with_port(): void {
        $channel = Attribution_Calculator::determine_channel('https://www.facebook.com:443/post/123', []);
        $this->assertEquals('Social', $channel);
    }

    /**
     * Test referrer with subdomain
     */
    public function test_referrer_with_subdomain(): void {
        $channel = Attribution_Calculator::determine_channel('https://m.facebook.com/post/123', []);
        $this->assertEquals('Social', $channel);
    }
}
