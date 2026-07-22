<?php
declare(strict_types=1);

namespace Ksf\GPG\Tests\Unit\Entity;

use PHPUnit\Framework\TestCase;
use Ksf\GPG\Entity\EncryptedFile;

class EncryptedFileTest extends TestCase
{
    public function testConstructor(): void
    {
        $file = new EncryptedFile('/tmp/test.txt');
        $this->assertSame('/tmp/test.txt', $file->getOriginalPath());
        $this->assertNull($file->getEncryptedPath());
        $this->assertNull($file->getSignedPath());
        $this->assertFalse($file->isSigned());
        $this->assertFalse($file->isEncrypted());
        $this->assertFalse($file->isPasswordProtected());
        $this->assertNull($file->getRecipientEmail());
        $this->assertInstanceOf(\DateTimeImmutable::class, $file->getCreatedAt());
    }

    public function testSetEncryptedPath(): void
    {
        $file = new EncryptedFile('/tmp/test.txt');
        $result = $file->setEncryptedPath('/tmp/test.txt.gpg');
        
        $this->assertSame('/tmp/test.txt.gpg', $file->getEncryptedPath());
        $this->assertTrue($file->isEncrypted());
        $this->assertSame($result, $file);
    }

    public function testSetSignedPath(): void
    {
        $file = new EncryptedFile('/tmp/test.txt');
        $result = $file->setSignedPath('/tmp/test.txt.sig');
        
        $this->assertSame('/tmp/test.txt.sig', $file->getSignedPath());
        $this->assertTrue($file->isSigned());
        $this->assertSame($result, $file);
    }

    public function testSetPasswordProtected(): void
    {
        $file = new EncryptedFile('/tmp/test.txt');
        $result = $file->setPasswordProtected(true);
        
        $this->assertTrue($file->isPasswordProtected());
        $this->assertSame($result, $file);
    }

    public function testSetRecipientEmail(): void
    {
        $file = new EncryptedFile('/tmp/test.txt');
        $result = $file->setRecipientEmail('test@example.com');
        
        $this->assertSame('test@example.com', $file->getRecipientEmail());
        $this->assertSame($result, $file);
    }

    public function testFluentInterface(): void
    {
        $file = new EncryptedFile('/tmp/test.txt');
        $result = $file
            ->setEncryptedPath('/tmp/test.txt.gpg')
            ->setSignedPath('/tmp/test.txt.sig')
            ->setPasswordProtected(true)
            ->setRecipientEmail('test@example.com');
        
        $this->assertSame($result, $file);
        $this->assertTrue($file->isEncrypted());
        $this->assertTrue($file->isSigned());
        $this->assertTrue($file->isPasswordProtected());
        $this->assertSame('test@example.com', $file->getRecipientEmail());
    }

    public function testGetAttachmentPathReturnsEncryptedWhenEncrypted(): void
    {
        $file = new EncryptedFile('/tmp/test.txt');
        $file->setEncryptedPath('/tmp/test.txt.gpg');
        
        $this->assertSame('/tmp/test.txt.gpg', $file->getAttachmentPath());
    }

    public function testGetAttachmentPathReturnsOriginalWhenNotEncrypted(): void
    {
        $file = new EncryptedFile('/tmp/test.txt');
        
        $this->assertSame('/tmp/test.txt', $file->getAttachmentPath());
    }
}
