<?php
declare(strict_types=1);

namespace Ksf\GPG\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Ksf\GPG\Services\PasswordEncryptionService;
use Ksf\GPG\Exception\EncryptionFailedException;

class PasswordEncryptionServiceTest extends TestCase
{
    private PasswordEncryptionService $service;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->service = new PasswordEncryptionService();
        $this->tempDir = sys_get_temp_dir() . '/gpg_test_' . uniqid();
        mkdir($this->tempDir, 0700, true);
    }

    protected function tearDown(): void
    {
        // Clean up temp files
        if (is_dir($this->tempDir)) {
            $files = glob($this->tempDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->tempDir);
        }
    }

    public function testEncryptFile(): void
    {
        $testFile = $this->tempDir . '/test.txt';
        file_put_contents($testFile, 'Hello, World!');
        
        $result = $this->service->encrypt($testFile, 'test-password-123');
        
        $this->assertFileExists($testFile . '.gpg');
        $this->assertTrue($result->isEncrypted());
        $this->assertTrue($result->isPasswordProtected());
        $this->assertSame($testFile . '.gpg', $result->getEncryptedPath());
    }

    public function testDecryptFile(): void
    {
        $testFile = $this->tempDir . '/test.txt';
        file_put_contents($testFile, 'Hello, World!');
        
        // Encrypt
        $this->service->encrypt($testFile, 'test-password-123');
        
        // Decrypt
        $decryptedPath = $this->service->decrypt($testFile . '.gpg', 'test-password-123');
        
        $this->assertFileExists($decryptedPath);
        $this->assertSame('Hello, World!', file_get_contents($decryptedPath));
    }

    public function testEncryptNonexistentFile(): void
    {
        $this->expectException(EncryptionFailedException::class);
        $this->service->encrypt('/nonexistent/file.txt', 'password');
    }

    public function testDecryptNonexistentFile(): void
    {
        $this->expectException(EncryptionFailedException::class);
        $this->service->decrypt('/nonexistent/file.txt', 'password');
    }

    public function testDecryptWithWrongPassword(): void
    {
        $testFile = $this->tempDir . '/test.txt';
        file_put_contents($testFile, 'Hello, World!');
        
        // Encrypt
        $this->service->encrypt($testFile, 'correct-password');
        
        // Try to decrypt with wrong password
        $this->expectException(EncryptionFailedException::class);
        $this->service->decrypt($testFile . '.gpg', 'wrong-password');
    }

    public function testGeneratePassword(): void
    {
        $password = $this->service->generatePassword(32);
        $this->assertIsString($password);
        $this->assertGreaterThan(0, strlen($password));
    }

    public function testGeneratePasswordLength(): void
    {
        $password = $this->service->generatePassword(64);
        $this->assertGreaterThan(0, strlen($password));
    }
}
