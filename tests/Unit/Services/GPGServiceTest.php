<?php
declare(strict_types=1);

namespace Ksf\GPG\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Ksf\GPG\Services\GPGService;
use Ksf\GPG\Contracts\KeyManagerInterface;
use Ksf\GPG\Contracts\EncryptionInterface;
use Ksf\GPG\Contracts\KeyserverInterface;
use Ksf\GPG\Entity\GPGKey;
use Ksf\GPG\ValueObject\KeyId;
use Ksf\GPG\ValueObject\Fingerprint;
use Ksf\GPG\ValueObject\EmailAddress;
use Ksf\GPG\Exception\KeyNotFoundException;

class GPGServiceTest extends TestCase
{
    private GPGService $service;
    private KeyManagerInterface $keyManager;
    private EncryptionInterface $encryption;
    private KeyserverInterface $keyserver;

    protected function setUp(): void
    {
        $this->keyManager = $this->createMock(KeyManagerInterface::class);
        $this->encryption = $this->createMock(EncryptionInterface::class);
        $this->keyserver = $this->createMock(KeyserverInterface::class);
        
        $this->service = new GPGService(
            $this->keyManager,
            $this->encryption,
            $this->keyserver
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
}
