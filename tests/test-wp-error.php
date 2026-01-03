<?php
/**
 * WP_Error tests for Peanut Suite.
 *
 * Tests error handling functionality.
 *
 * @package Peanut_Suite
 */

class Test_WP_Error extends Peanut_Suite_TestCase {

    /**
     * Test WP_Error can be created with code and message.
     */
    public function test_wp_error_creation() {
        $error = new WP_Error('test_error', 'This is an error message');

        $this->assertInstanceOf(WP_Error::class, $error);
        $this->assertEquals('test_error', $error->get_error_code());
        $this->assertEquals('This is an error message', $error->get_error_message());
    }

    /**
     * Test is_wp_error returns true for WP_Error objects.
     */
    public function test_is_wp_error_returns_true() {
        $error = new WP_Error('error_code', 'Error message');
        $this->assertTrue(is_wp_error($error));
    }

    /**
     * Test is_wp_error returns false for non-WP_Error objects.
     */
    public function test_is_wp_error_returns_false() {
        $this->assertFalse(is_wp_error('string'));
        $this->assertFalse(is_wp_error(123));
        $this->assertFalse(is_wp_error(['array']));
        $this->assertFalse(is_wp_error(new stdClass()));
        $this->assertFalse(is_wp_error(null));
        $this->assertFalse(is_wp_error(false));
    }

    /**
     * Test WP_Error can store error data.
     */
    public function test_wp_error_with_data() {
        $data = ['field' => 'email', 'value' => 'invalid'];
        $error = new WP_Error('validation_error', 'Invalid email', $data);

        $this->assertEquals($data, $error->get_error_data());
    }

    /**
     * Test WP_Error can have multiple errors added.
     */
    public function test_wp_error_multiple_errors() {
        $error = new WP_Error('first_error', 'First error message');
        $error->add('second_error', 'Second error message');

        // First error code should still be returned.
        $this->assertEquals('first_error', $error->get_error_code());

        // Both errors should exist.
        $this->assertArrayHasKey('first_error', $error->errors);
        $this->assertArrayHasKey('second_error', $error->errors);
    }

    /**
     * Test WP_Error handles empty constructor.
     */
    public function test_wp_error_empty_constructor() {
        $error = new WP_Error();

        $this->assertEquals('', $error->get_error_code());
        $this->assertEquals('', $error->get_error_message());
    }

    /**
     * Test get_error_message with specific code.
     */
    public function test_get_error_message_with_code() {
        $error = new WP_Error('error1', 'Message 1');
        $error->add('error2', 'Message 2');

        $this->assertEquals('Message 1', $error->get_error_message('error1'));
        $this->assertEquals('Message 2', $error->get_error_message('error2'));
    }
}
