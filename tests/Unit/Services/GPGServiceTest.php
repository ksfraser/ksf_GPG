<?php
declare(strict_types=1);

namespace Ksf\GPG\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Ksf\GPG\Services\GPGService;
use Ksf\GPG\Contracts\KeyManagerInterface;
use Ksf\GPG\Contracts\GnuPGAdapterInterface;
use Ksf\GPG\Entity\GPGKey;
use Ksf\GPG\ValueObject\KeyId;
use Ksf\GPG\ValueObject\Fingerprint;
use Ksf\GPG\ValueObject\EmailAddress;
use Ksf\GPG\Exception\KeyNotFoundException;

class GPGServiceTest extends TestCase
{
    private GPGService $service;
    private KeyManagerInterface $keyManager;
    private GnuPGAdapterInterface $adapter;

    protected function setUp(): void
    {
        $this->keyManager = $this->createMock(KeyManagerInterface::class);
        $this->adapter = $this->createMock(GnuPGAdapterInterface::class);
        
        $this->service = new GPGService(
            $this->keyManager,
            $this->adapter
        );
    }

    public function testHasKeyForEmailReturnsTrue(): void
    {
        $key = $this->createMock(GPGKey::class);
        
        $this->keyManager->expects($this->once())
            ->method('getKeyByEmail')
            ->with('test@example.com')
            ->willReturn($key);
        
        $this->assertTrue($this->service->hasKeyForEmail('test@example.com'));
    }

    public function testHasKeyForEmailReturnsFalse(): void
    {
        $this->keyManager->expects($this->once())
            ->method('getKeyByEmail')
            ->with('test@example.com')
            ->willReturn(null);
        
        $this->assertFalse($this->service->hasKeyForEmail('test@example.com'));
    }

    public function testGetKeyByEmailReturnsKey(): void
    {
        $key = $this->createMock(GPGKey::class);
        
        $this->keyManager->expects($this->once())
            ->method('getKeyByEmail')
            ->with('test@example.com')
            ->willReturn($key);
        
        $this->assertSame($key, $this->service->getKeyByEmail('test@example.com'));
    }

    public function testGetKeyByEmailReturnsNull(): void
    {
        $this->keyManager->expects($this->once())
            ->method('getKeyByEmail')
            ->with('test@example.com')
            ->willReturn(null);
        
        $this->assertNull($this->service->getKeyByEmail('test@example.com'));
    }

    public function testEncryptForContactThrowsWhenKeyNotFound(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'gpg_test_');
        file_put_contents($tempFile, 'test content');
        
        $this->keyManager->expects($this->once())
            ->method('getKeyByEmail')
            ->with('test@example.com')
            ->willReturn(null);
        
        $this->expectException(KeyNotFoundException::class);
        $this->service->encryptForContact($tempFile, 'test@example.com');
        
        unlink($tempFile);
    }

    public function testSignFileThrowsWhenKeyNotFound(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'gpg_test_');
        file_put_contents($tempFile, 'test content');
        
        $this->keyManager->expects($this->once())
            ->method('getKeyByEmail')
            ->with('test@example.com')
            ->willReturn(null);
        
        $this->expectException(KeyNotFoundException::class);
        $this->service->signFile($tempFile, 'test@example.com');
        
        unlink($tempFile);
    }

    public function testSignFileDelegatesToAdapter(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'gpg_test_');
        file_put_contents($tempFile, 'test content');
        $signedPath = $tempFile . '.sig';
        
        $fingerprint = new Fingerprint(str_repeat('AB', 20));
        $key = $this->createMock(GPGKey::class);
        $key->method('getFingerprint')->willReturn($fingerprint);
        
        $this->keyManager->expects($this->once())
            ->method('getKeyByEmail')
            ->with('test@example.com')
            ->willReturn($key);
        
        $this->adapter->expects($this->once())
            ->method('signFile')
            ->with($tempFile, $fingerprint->getValue())
            ->willReturn($signedPath);
        
        $result = $this->service->signFile($tempFile, 'test@example.com');
        
        $this->assertSame($signedPath, $result->getSignedPath());
        $this->assertTrue($result->isSigned());
        $this->assertSame('test@example.com', $result->getRecipientEmail());
        
        unlink($tempFile);
    }

    public function testEncryptForContactDelegatesToAdapter(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'gpg_test_');
        file_put_contents($tempFile, 'test content');
        $encryptedPath = $tempFile . '.gpg';
        
        $fingerprint = new Fingerprint(str_repeat('AB', 20));
        $key = $this->createMock(GPGKey::class);
        $key->method('getFingerprint')->willReturn($fingerprint);
        
        $this->keyManager->expects($this->once())
            ->method('getKeyByEmail')
            ->with('test@example.com')
            ->willReturn($key);
        
        $this->adapter->expects($this->once())
            ->method('encryptForRecipient')
            ->with($tempFile, $fingerprint->getValue())
            ->willReturn($encryptedPath);
        
        $result = $this->service->encryptForContact($tempFile, 'test@example.com');
        
        $this->assertSame($encryptedPath, $result->getEncryptedPath());
        $this->assertTrue($result->isEncrypted());
        $this->assertSame('test@example.com', $result->getRecipientEmail());
        
        unlink($tempFile);
    }

    public function testSignAndEncryptDelegatesToAdapter(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'gpg_test_');
        file_put_contents($tempFile, 'test content');
        $encryptedPath = $tempFile . '.gpg';
        $signedPath = $encryptedPath . '.sig';
        
        // Create the encrypted file so signFile's file_exists check passes
        file_put_contents($encryptedPath, 'encrypted content');
        
        $fingerprint = new Fingerprint(str_repeat('AB', 20));
        $key = $this->createMock(GPGKey::class);
        $key->method('getFingerprint')->willReturn($fingerprint);
        
        $this->keyManager->expects($this->exactly(2))
            ->method('getKeyByEmail')
            ->with('test@example.com')
            ->willReturn($key);
        
        $this->adapter->expects($this->once())
            ->method('encryptForRecipient')
            ->with($tempFile, $fingerprint->getValue())
            ->willReturn($encryptedPath);
        
        $this->adapter->expects($this->once())
            ->method('signFile')
            ->with($encryptedPath, $fingerprint->getValue())
            ->willReturn($signedPath);
        
        $result = $this->service->signAndEncrypt($tempFile, 'test@example.com');
        
        $this->assertSame($encryptedPath, $result->getEncryptedPath());
        $this->assertSame($signedPath, $result->getSignedPath());
        $this->assertTrue($result->isEncrypted());
        $this->assertTrue($result->isSigned());
        
        unlink($tempFile);
        unlink($encryptedPath);
    }

    public function testEncryptWithPasswordDelegatesToAdapter(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'gpg_test_');
        file_put_contents($tempFile, 'test content');
        $encryptedPath = $tempFile . '.gpg';
        
        $this->adapter->expects($this->once())
            ->method('encryptWithPassword')
            ->with($tempFile, 'secret-password')
            ->willReturn($encryptedPath);
        
        $result = $this->service->encryptWithPassword($tempFile, 'secret-password');
        
        $this->assertSame($encryptedPath, $result->getEncryptedPath());
        $this->assertTrue($result->isEncrypted());
        $this->assertTrue($result->isPasswordProtected());
        
        unlink($tempFile);
    }

    public function testDecryptWithPasswordDelegatesToAdapter(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'gpg_test_');
        $encryptedFile = $tempFile . '.gpg';
        file_put_contents($encryptedFile, 'encrypted content');
        
        $this->adapter->expects($this->once())
            ->method('decryptWithPassword')
            ->with($encryptedFile, 'secret-password')
            ->willReturn($tempFile);
        
        $result = $this->service->decryptWithPassword($encryptedFile, 'secret-password');
        
        $this->assertSame($tempFile, $result);
        
        unlink($encryptedFile);
    }
}
