<?php
/**
 * Options and transients tests for Peanut Suite.
 *
 * Tests WordPress options and transient functionality.
 *
 * @package Peanut_Suite
 */

class Test_Options extends Peanut_Suite_TestCase {

    /**
     * Set up before each test.
     */
    protected function setUp(): void {
        parent::setUp();
        // Reset mock storage.
        global $mock_options, $mock_transients;
        $mock_options = [];
        $mock_transients = [];
    }

    /**
     * Test get_option returns default when option doesn't exist.
     */
    public function test_get_option_returns_default() {
        $result = get_option('nonexistent_option', 'default_value');
        $this->assertEquals('default_value', $result);
    }

    /**
     * Test update_option stores and retrieves value.
     */
    public function test_update_option_stores_value() {
        $result = update_option('test_option', 'test_value');
        $this->assertTrue($result);

        $retrieved = get_option('test_option');
        $this->assertEquals('test_value', $retrieved);
    }

    /**
     * Test delete_option removes the option.
     */
    public function test_delete_option_removes_value() {
        update_option('test_option', 'test_value');
        delete_option('test_option');

        $result = get_option('test_option', 'default');
        $this->assertEquals('default', $result);
    }

    /**
     * Test options can store arrays.
     */
    public function test_option_stores_array() {
        $array_value = [
            'key1' => 'value1',
            'key2' => 'value2',
            'nested' => [
                'a' => 1,
                'b' => 2,
            ],
        ];

        update_option('array_option', $array_value);
        $retrieved = get_option('array_option');

        $this->assertEquals($array_value, $retrieved);
        $this->assertEquals('value1', $retrieved['key1']);
        $this->assertEquals(1, $retrieved['nested']['a']);
    }

    /**
     * Test set_transient stores value.
     */
    public function test_set_transient_stores_value() {
        $result = set_transient('test_transient', 'transient_value', 3600);
        $this->assertTrue($result);

        $retrieved = get_transient('test_transient');
        $this->assertEquals('transient_value', $retrieved);
    }

    /**
     * Test transient returns false when expired.
     */
    public function test_transient_expires() {
        // Set transient with 1 second expiration.
        set_transient('expiring_transient', 'value', 1);

        // Should exist immediately.
        $this->assertEquals('value', get_transient('expiring_transient'));

        // Simulate expiration by manipulating the mock.
        global $mock_transients;
        $mock_transients['expiring_transient']['expiration'] = time() - 1;

        // Should return false after expiration.
        $this->assertFalse(get_transient('expiring_transient'));
    }

    /**
     * Test delete_transient removes value.
     */
    public function test_delete_transient_removes_value() {
        set_transient('delete_test', 'value', 3600);
        delete_transient('delete_test');

        $this->assertFalse(get_transient('delete_test'));
    }

    /**
     * Test transient can store complex data.
     */
    public function test_transient_stores_complex_data() {
        $complex_data = [
            'string' => 'test',
            'number' => 42,
            'boolean' => true,
            'array' => [1, 2, 3],
            'object' => (object) ['key' => 'value'],
        ];

        set_transient('complex_transient', $complex_data, 3600);
        $retrieved = get_transient('complex_transient');

        $this->assertEquals($complex_data['string'], $retrieved['string']);
        $this->assertEquals($complex_data['number'], $retrieved['number']);
        $this->assertEquals($complex_data['boolean'], $retrieved['boolean']);
        $this->assertEquals($complex_data['array'], $retrieved['array']);
    }
}
