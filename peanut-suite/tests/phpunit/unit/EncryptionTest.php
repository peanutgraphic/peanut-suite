<?php
/**
 * Unit tests for Peanut_Encryption class
 */

require_once dirname(__DIR__) . '/bootstrap.php';
require_once PEANUT_PLUGIN_DIR . 'core/services/class-peanut-encryption.php';

class EncryptionTest extends Peanut_Test_Case {

    private Peanut_Encryption $encryption;
    private string $test_key;

    protected function setUp(): void {
        parent::setUp();
        // Use a fixed test key for reproducible tests
        $this->test_key = hash('sha256', 'test-encryption-key', true);
        $this->encryption = new Peanut_Encryption($this->test_key);
    }

    public function test_encrypt_decrypt_string(): void {
        $original = 'This is a secret message';
        $encrypted = $this->encryption->encrypt($original);

        $this->assertNotEmpty($encrypted);
        $this->assertNotEquals($original, $encrypted);

        $decrypted = $this->encryption->decrypt($encrypted);
        $this->assertEquals($original, $decrypted);
    }

    public function test_encrypt_empty_string_returns_empty(): void {
        $encrypted = $this->encryption->encrypt('');
        $this->assertEmpty($encrypted);
    }

    public function test_decrypt_empty_string_returns_empty(): void {
        $decrypted = $this->encryption->decrypt('');
        $this->assertEmpty($decrypted);
    }

    public function test_decrypt_invalid_data_returns_empty(): void {
        $decrypted = $this->encryption->decrypt('not-valid-encrypted-data');
        $this->assertEmpty($decrypted);
    }

    public function test_encrypt_decrypt_special_characters(): void {
        $original = 'Test with émojis 🎉 and spëcial chars: <>&"\'';
        $encrypted = $this->encryption->encrypt($original);
        $decrypted = $this->encryption->decrypt($encrypted);

        $this->assertEquals($original, $decrypted);
    }

    public function test_encrypt_produces_different_output_each_time(): void {
        $original = 'Same message';
        $encrypted1 = $this->encryption->encrypt($original);
        $encrypted2 = $this->encryption->encrypt($original);

        // Due to random IV, same input produces different output
        $this->assertNotEquals($encrypted1, $encrypted2);

        // But both decrypt to the same value
        $this->assertEquals($original, $this->encryption->decrypt($encrypted1));
        $this->assertEquals($original, $this->encryption->decrypt($encrypted2));
    }

    public function test_encrypt_decrypt_array(): void {
        $original = [
            'key1' => 'value1',
            'key2' => 123,
            'nested' => ['a' => 'b'],
        ];

        $encrypted = $this->encryption->encrypt_array($original);
        $this->assertNotEmpty($encrypted);

        $decrypted = $this->encryption->decrypt_array($encrypted);
        $this->assertEquals($original, $decrypted);
    }

    public function test_decrypt_array_invalid_data_returns_null(): void {
        $result = $this->encryption->decrypt_array('invalid-data');
        $this->assertNull($result);
    }

    public function test_is_available_returns_bool(): void {
        $result = Peanut_Encryption::is_available();
        $this->assertIsBool($result);
        // OpenSSL should be available in most PHP installations
        $this->assertTrue($result);
    }

    public function test_mask_hides_middle_of_string(): void {
        $result = Peanut_Encryption::mask('1234567890', 2, 2);
        $this->assertEquals('12******90', $result);
    }

    public function test_mask_short_string_returns_all_stars(): void {
        $result = Peanut_Encryption::mask('abc', 2, 2);
        $this->assertEquals('***', $result);
    }

    public function test_mask_with_no_visible_characters(): void {
        $result = Peanut_Encryption::mask('secret', 0, 0);
        $this->assertEquals('******', $result);
    }

    public function test_generate_token_returns_correct_length(): void {
        $token = Peanut_Encryption::generate_token(16);
        // bin2hex doubles the byte length
        $this->assertEquals(32, strlen($token));

        $token = Peanut_Encryption::generate_token(32);
        $this->assertEquals(64, strlen($token));
    }

    public function test_generate_token_is_hex(): void {
        $token = Peanut_Encryption::generate_token(16);
        $this->assertMatchesRegularExpression('/^[0-9a-f]+$/', $token);
    }

    public function test_generate_url_safe_token_is_url_safe(): void {
        $token = Peanut_Encryption::generate_url_safe_token(32);
        $this->assertEquals(32, strlen($token));
        // Should only contain URL-safe characters
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $token);
    }

    public function test_different_keys_cannot_decrypt_each_others_data(): void {
        $encryption1 = new Peanut_Encryption(hash('sha256', 'key1', true));
        $encryption2 = new Peanut_Encryption(hash('sha256', 'key2', true));

        $original = 'Secret data';
        $encrypted = $encryption1->encrypt($original);

        $decrypted = $encryption2->decrypt($encrypted);
        // Should fail to decrypt or return garbage
        $this->assertNotEquals($original, $decrypted);
    }
}
