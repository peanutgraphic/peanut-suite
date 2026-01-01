<?php
/**
 * Unit tests for Attribution_Models class
 *
 * Tests attribution calculation models.
 */

require_once dirname(__DIR__) . '/bootstrap.php';
require_once PEANUT_PLUGIN_DIR . 'modules/attribution/class-attribution-models.php';

use PeanutSuite\Attribution\Attribution_Models;

class AttributionModelsTest extends Peanut_Test_Case {

    // =========================================
    // Constants Tests
    // =========================================

    /**
     * Test models constant is defined
     */
    public function test_models_constant_defined(): void {
        $models = Attribution_Models::MODELS;

        $this->assertIsArray($models);
        $this->assertCount(5, $models);
        $this->assertArrayHasKey('first_touch', $models);
        $this->assertArrayHasKey('last_touch', $models);
        $this->assertArrayHasKey('linear', $models);
        $this->assertArrayHasKey('time_decay', $models);
        $this->assertArrayHasKey('position_based', $models);
    }

    /**
     * Test time decay half-life constant
     */
    public function test_time_decay_half_life_constant(): void {
        $this->assertEquals(7, Attribution_Models::TIME_DECAY_HALF_LIFE);
    }

    /**
     * Test position-based weight constants
     */
    public function test_position_based_weight_constants(): void {
        $this->assertEquals(0.40, Attribution_Models::POSITION_FIRST_WEIGHT);
        $this->assertEquals(0.40, Attribution_Models::POSITION_LAST_WEIGHT);
        $this->assertEquals(0.20, Attribution_Models::POSITION_MIDDLE_WEIGHT);

        // Weights should sum to 1.0
        $total = Attribution_Models::POSITION_FIRST_WEIGHT
               + Attribution_Models::POSITION_LAST_WEIGHT
               + Attribution_Models::POSITION_MIDDLE_WEIGHT;
        $this->assertEquals(1.0, $total);
    }

    // =========================================
    // First Touch Model Tests
    // =========================================

    /**
     * Test first touch with single touch
     */
    public function test_first_touch_single_touch(): void {
        $touches = [
            ['id' => 1, 'touch_time' => '2024-01-01 10:00:00'],
        ];

        $result = Attribution_Models::first_touch($touches);

        $this->assertEquals(1.0, $result[1]);
    }

    /**
     * Test first touch with multiple touches
     */
    public function test_first_touch_multiple_touches(): void {
        $touches = [
            ['id' => 1, 'touch_time' => '2024-01-01 10:00:00'],
            ['id' => 2, 'touch_time' => '2024-01-02 10:00:00'],
            ['id' => 3, 'touch_time' => '2024-01-03 10:00:00'],
        ];

        $result = Attribution_Models::first_touch($touches);

        $this->assertEquals(1.0, $result[1]);
        $this->assertEquals(0.0, $result[2]);
        $this->assertEquals(0.0, $result[3]);
    }

    /**
     * Test first touch with unordered touches
     */
    public function test_first_touch_unordered_touches(): void {
        // Touches provided in wrong order - should still identify first by time
        $touches = [
            ['id' => 3, 'touch_time' => '2024-01-03 10:00:00'],
            ['id' => 1, 'touch_time' => '2024-01-01 10:00:00'],
            ['id' => 2, 'touch_time' => '2024-01-02 10:00:00'],
        ];

        $result = Attribution_Models::first_touch($touches);

        $this->assertEquals(1.0, $result[1]); // ID 1 is first by time
        $this->assertEquals(0.0, $result[2]);
        $this->assertEquals(0.0, $result[3]);
    }

    /**
     * Test first touch with empty touches
     */
    public function test_first_touch_empty_touches(): void {
        $result = Attribution_Models::first_touch([]);
        $this->assertEmpty($result);
    }

    // =========================================
    // Last Touch Model Tests
    // =========================================

    /**
     * Test last touch with single touch
     */
    public function test_last_touch_single_touch(): void {
        $touches = [
            ['id' => 1, 'touch_time' => '2024-01-01 10:00:00'],
        ];

        $result = Attribution_Models::last_touch($touches);

        $this->assertEquals(1.0, $result[1]);
    }

    /**
     * Test last touch with multiple touches
     */
    public function test_last_touch_multiple_touches(): void {
        $touches = [
            ['id' => 1, 'touch_time' => '2024-01-01 10:00:00'],
            ['id' => 2, 'touch_time' => '2024-01-02 10:00:00'],
            ['id' => 3, 'touch_time' => '2024-01-03 10:00:00'],
        ];

        $result = Attribution_Models::last_touch($touches);

        $this->assertEquals(0.0, $result[1]);
        $this->assertEquals(0.0, $result[2]);
        $this->assertEquals(1.0, $result[3]);
    }

    /**
     * Test last touch with unordered touches
     */
    public function test_last_touch_unordered_touches(): void {
        $touches = [
            ['id' => 1, 'touch_time' => '2024-01-01 10:00:00'],
            ['id' => 3, 'touch_time' => '2024-01-03 10:00:00'],
            ['id' => 2, 'touch_time' => '2024-01-02 10:00:00'],
        ];

        $result = Attribution_Models::last_touch($touches);

        $this->assertEquals(0.0, $result[1]);
        $this->assertEquals(0.0, $result[2]);
        $this->assertEquals(1.0, $result[3]); // ID 3 is last by time
    }

    /**
     * Test last touch with empty touches
     */
    public function test_last_touch_empty_touches(): void {
        $result = Attribution_Models::last_touch([]);
        $this->assertEmpty($result);
    }

    // =========================================
    // Linear Model Tests
    // =========================================

    /**
     * Test linear with single touch
     */
    public function test_linear_single_touch(): void {
        $touches = [
            ['id' => 1, 'touch_time' => '2024-01-01 10:00:00'],
        ];

        $result = Attribution_Models::linear($touches);

        $this->assertEquals(1.0, $result[1]);
    }

    /**
     * Test linear with two touches
     */
    public function test_linear_two_touches(): void {
        $touches = [
            ['id' => 1, 'touch_time' => '2024-01-01 10:00:00'],
            ['id' => 2, 'touch_time' => '2024-01-02 10:00:00'],
        ];

        $result = Attribution_Models::linear($touches);

        $this->assertEquals(0.5, $result[1]);
        $this->assertEquals(0.5, $result[2]);
    }

    /**
     * Test linear with multiple touches
     */
    public function test_linear_multiple_touches(): void {
        $touches = [
            ['id' => 1, 'touch_time' => '2024-01-01 10:00:00'],
            ['id' => 2, 'touch_time' => '2024-01-02 10:00:00'],
            ['id' => 3, 'touch_time' => '2024-01-03 10:00:00'],
            ['id' => 4, 'touch_time' => '2024-01-04 10:00:00'],
        ];

        $result = Attribution_Models::linear($touches);

        $this->assertEquals(0.25, $result[1]);
        $this->assertEquals(0.25, $result[2]);
        $this->assertEquals(0.25, $result[3]);
        $this->assertEquals(0.25, $result[4]);
    }

    /**
     * Test linear credits sum to 1.0
     */
    public function test_linear_credits_sum_to_one(): void {
        $touches = [
            ['id' => 1, 'touch_time' => '2024-01-01 10:00:00'],
            ['id' => 2, 'touch_time' => '2024-01-02 10:00:00'],
            ['id' => 3, 'touch_time' => '2024-01-03 10:00:00'],
        ];

        $result = Attribution_Models::linear($touches);

        $this->assertEquals(1.0, array_sum($result), '', 0.0001);
    }

    /**
     * Test linear with empty touches
     */
    public function test_linear_empty_touches(): void {
        $result = Attribution_Models::linear([]);
        $this->assertEmpty($result);
    }

    // =========================================
    // Time Decay Model Tests
    // =========================================

    /**
     * Test time decay with single touch
     */
    public function test_time_decay_single_touch(): void {
        $touches = [
            ['id' => 1, 'touch_time' => '2024-01-01 10:00:00'],
        ];
        $conversion_time = '2024-01-08 10:00:00';

        $result = Attribution_Models::time_decay($touches, $conversion_time);

        $this->assertEquals(1.0, $result[1]);
    }

    /**
     * Test time decay gives more credit to recent touches
     */
    public function test_time_decay_recent_touch_more_credit(): void {
        $touches = [
            ['id' => 1, 'touch_time' => '2024-01-01 10:00:00'], // 7 days before conversion
            ['id' => 2, 'touch_time' => '2024-01-07 10:00:00'], // 1 day before conversion
        ];
        $conversion_time = '2024-01-08 10:00:00';

        $result = Attribution_Models::time_decay($touches, $conversion_time);

        $this->assertGreaterThan($result[1], $result[2]);
    }

    /**
     * Test time decay with touches at half-life intervals
     */
    public function test_time_decay_half_life(): void {
        $touches = [
            ['id' => 1, 'touch_time' => '2024-01-08 10:00:00'], // At conversion
            ['id' => 2, 'touch_time' => '2024-01-01 10:00:00'], // 7 days before (1 half-life)
        ];
        $conversion_time = '2024-01-08 10:00:00';

        $result = Attribution_Models::time_decay($touches, $conversion_time);

        // Touch at conversion should have roughly 2x the credit of touch at 1 half-life
        $ratio = $result[1] / $result[2];
        $this->assertEqualsWithDelta(2.0, $ratio, 0.1);
    }

    /**
     * Test time decay credits sum to 1.0
     */
    public function test_time_decay_credits_sum_to_one(): void {
        $touches = [
            ['id' => 1, 'touch_time' => '2024-01-01 10:00:00'],
            ['id' => 2, 'touch_time' => '2024-01-03 10:00:00'],
            ['id' => 3, 'touch_time' => '2024-01-06 10:00:00'],
        ];
        $conversion_time = '2024-01-08 10:00:00';

        $result = Attribution_Models::time_decay($touches, $conversion_time);

        $this->assertEqualsWithDelta(1.0, array_sum($result), 0.0001);
    }

    /**
     * Test time decay with empty touches
     */
    public function test_time_decay_empty_touches(): void {
        $result = Attribution_Models::time_decay([], '2024-01-08 10:00:00');
        $this->assertEmpty($result);
    }

    // =========================================
    // Position-Based Model Tests
    // =========================================

    /**
     * Test position-based with single touch
     */
    public function test_position_based_single_touch(): void {
        $touches = [
            ['id' => 1, 'touch_time' => '2024-01-01 10:00:00'],
        ];

        $result = Attribution_Models::position_based($touches);

        $this->assertEquals(1.0, $result[1]);
    }

    /**
     * Test position-based with two touches
     */
    public function test_position_based_two_touches(): void {
        $touches = [
            ['id' => 1, 'touch_time' => '2024-01-01 10:00:00'],
            ['id' => 2, 'touch_time' => '2024-01-02 10:00:00'],
        ];

        $result = Attribution_Models::position_based($touches);

        $this->assertEquals(0.5, $result[1]);
        $this->assertEquals(0.5, $result[2]);
    }

    /**
     * Test position-based with three touches
     */
    public function test_position_based_three_touches(): void {
        $touches = [
            ['id' => 1, 'touch_time' => '2024-01-01 10:00:00'],
            ['id' => 2, 'touch_time' => '2024-01-02 10:00:00'],
            ['id' => 3, 'touch_time' => '2024-01-03 10:00:00'],
        ];

        $result = Attribution_Models::position_based($touches);

        $this->assertEquals(0.40, $result[1]); // First
        $this->assertEquals(0.20, $result[2]); // Middle (20% / 1)
        $this->assertEquals(0.40, $result[3]); // Last
    }

    /**
     * Test position-based with five touches
     */
    public function test_position_based_five_touches(): void {
        $touches = [
            ['id' => 1, 'touch_time' => '2024-01-01 10:00:00'],
            ['id' => 2, 'touch_time' => '2024-01-02 10:00:00'],
            ['id' => 3, 'touch_time' => '2024-01-03 10:00:00'],
            ['id' => 4, 'touch_time' => '2024-01-04 10:00:00'],
            ['id' => 5, 'touch_time' => '2024-01-05 10:00:00'],
        ];

        $result = Attribution_Models::position_based($touches);

        $this->assertEquals(0.40, $result[1]); // First
        $this->assertEqualsWithDelta(0.0667, $result[2], 0.001); // Middle (20% / 3)
        $this->assertEqualsWithDelta(0.0667, $result[3], 0.001); // Middle
        $this->assertEqualsWithDelta(0.0667, $result[4], 0.001); // Middle
        $this->assertEquals(0.40, $result[5]); // Last
    }

    /**
     * Test position-based credits sum to 1.0
     */
    public function test_position_based_credits_sum_to_one(): void {
        $touches = [
            ['id' => 1, 'touch_time' => '2024-01-01 10:00:00'],
            ['id' => 2, 'touch_time' => '2024-01-02 10:00:00'],
            ['id' => 3, 'touch_time' => '2024-01-03 10:00:00'],
            ['id' => 4, 'touch_time' => '2024-01-04 10:00:00'],
        ];

        $result = Attribution_Models::position_based($touches);

        $this->assertEqualsWithDelta(1.0, array_sum($result), 0.0001);
    }

    /**
     * Test position-based with empty touches
     */
    public function test_position_based_empty_touches(): void {
        $result = Attribution_Models::position_based([]);
        $this->assertEmpty($result);
    }

    // =========================================
    // Calculate Method Tests
    // =========================================

    /**
     * Test calculate dispatches to correct model
     *
     * @dataProvider modelProvider
     */
    public function test_calculate_dispatches_to_model(string $model): void {
        $touches = [
            ['id' => 1, 'touch_time' => '2024-01-01 10:00:00'],
            ['id' => 2, 'touch_time' => '2024-01-02 10:00:00'],
        ];
        $conversion_time = '2024-01-03 10:00:00';

        $result = Attribution_Models::calculate($model, $touches, $conversion_time);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEqualsWithDelta(1.0, array_sum($result), 0.0001);
    }

    public static function modelProvider(): array {
        return [
            'first_touch' => ['first_touch'],
            'last_touch' => ['last_touch'],
            'linear' => ['linear'],
            'time_decay' => ['time_decay'],
            'position_based' => ['position_based'],
        ];
    }

    /**
     * Test calculate with unknown model defaults to last touch
     */
    public function test_calculate_unknown_model_defaults(): void {
        $touches = [
            ['id' => 1, 'touch_time' => '2024-01-01 10:00:00'],
            ['id' => 2, 'touch_time' => '2024-01-02 10:00:00'],
        ];
        $conversion_time = '2024-01-03 10:00:00';

        $unknown_result = Attribution_Models::calculate('unknown_model', $touches, $conversion_time);
        $last_touch_result = Attribution_Models::last_touch($touches);

        $this->assertEquals($last_touch_result, $unknown_result);
    }

    // =========================================
    // Calculate All Tests
    // =========================================

    /**
     * Test calculate_all returns all models
     */
    public function test_calculate_all_returns_all_models(): void {
        $touches = [
            ['id' => 1, 'touch_time' => '2024-01-01 10:00:00'],
            ['id' => 2, 'touch_time' => '2024-01-02 10:00:00'],
        ];
        $conversion_time = '2024-01-03 10:00:00';

        $results = Attribution_Models::calculate_all($touches, $conversion_time);

        $this->assertArrayHasKey('first_touch', $results);
        $this->assertArrayHasKey('last_touch', $results);
        $this->assertArrayHasKey('linear', $results);
        $this->assertArrayHasKey('time_decay', $results);
        $this->assertArrayHasKey('position_based', $results);
    }

    // =========================================
    // Model Description Tests
    // =========================================

    /**
     * Test get_description returns string for valid models
     *
     * @dataProvider modelProvider
     */
    public function test_get_description_returns_string(string $model): void {
        $description = Attribution_Models::get_description($model);

        $this->assertIsString($description);
        $this->assertNotEmpty($description);
    }

    /**
     * Test get_description returns empty for unknown model
     */
    public function test_get_description_unknown_model(): void {
        $description = Attribution_Models::get_description('unknown_model');
        $this->assertEquals('', $description);
    }

    // =========================================
    // Get Models Tests
    // =========================================

    /**
     * Test get_models returns all models
     */
    public function test_get_models(): void {
        $models = Attribution_Models::get_models();

        $this->assertEquals(Attribution_Models::MODELS, $models);
    }
}
