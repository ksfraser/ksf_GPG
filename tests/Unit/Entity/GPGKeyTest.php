<?php
declare(strict_types=1);

namespace ksfraser\GPG\Tests\Unit\Entity;

use PHPUnit\Framework\TestCase;
use ksfraser\GPG\Entity\GPGKey;
use ksfraser\GPG\ValueObject\KeyId;
use ksfraser\GPG\ValueObject\Fingerprint;
use ksfraser\GPG\ValueObject\EmailAddress;

class GPGKeyTest extends TestCase
{
    public function testConstructor(): void
    {
        $keyId = new KeyId('12345678');
        $fingerprint = new Fingerprint('1234567890ABCDEF1234567890ABCDEF12345678');
        $email = new EmailAddress('test@example.com');
        $publicKey = '-----BEGIN PGP PUBLIC KEY BLOCK-----';

        $key = new GPGKey($keyId, $fingerprint, $email, $publicKey);

        $this->assertSame($keyId, $key->getKeyId());
        $this->assertSame($fingerprint, $key->getFingerprint());
        $this->assertSame($email, $key->getEmail());
        $this->assertSame($publicKey, $key->getPublicKey());
        $this->assertNull($key->getEncryptedPrivateKey());
        $this->assertFalse($key->isPublished());
        $this->assertFalse($key->isPrimary());
        $this->assertInstanceOf(\DateTimeImmutable::class, $key->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $key->getModifiedAt());
    }

    public function testSetEncryptedPrivateKey(): void
    {
        $keyId = new KeyId('12345678');
        $fingerprint = new Fingerprint('1234567890ABCDEF1234567890ABCDEF12345678');
        $email = new EmailAddress('test@example.com');
        $publicKey = '-----BEGIN PGP PUBLIC KEY BLOCK-----';
        $privateKey = '-----BEGIN PGP PRIVATE KEY BLOCK-----';

        $key = new GPGKey($keyId, $fingerprint, $email, $publicKey);
        $result = $key->setEncryptedPrivateKey($privateKey);

        $this->assertSame($privateKey, $key->getEncryptedPrivateKey());
        $this->assertSame($result, $key);
    }

    public function testMarkAsPublished(): void
    {
        $keyId = new KeyId('12345678');
        $fingerprint = new Fingerprint('1234567890ABCDEF1234567890ABCDEF12345678');
        $email = new EmailAddress('test@example.com');
        $publicKey = '-----BEGIN PGP PUBLIC KEY BLOCK-----';

        $key = new GPGKey($keyId, $fingerprint, $email, $publicKey);
        $result = $key->markAsPublished();

        $this->assertTrue($key->isPublished());
        $this->assertSame($result, $key);
    }

    public function testMarkAsPrimary(): void
    {
        $keyId = new KeyId('12345678');
        $fingerprint = new Fingerprint('1234567890ABCDEF1234567890ABCDEF12345678');
        $email = new EmailAddress('test@example.com');
        $publicKey = '-----BEGIN PGP PUBLIC KEY BLOCK-----';

        $key = new GPGKey($keyId, $fingerprint, $email, $publicKey);
        $result = $key->markAsPrimary();

        $this->assertTrue($key->isPrimary());
        $this->assertSame($result, $key);
    }

    public function testFluentInterface(): void
    {
        $keyId = new KeyId('12345678');
        $fingerprint = new Fingerprint('1234567890ABCDEF1234567890ABCDEF12345678');
        $email = new EmailAddress('test@example.com');
        $publicKey = '-----BEGIN PGP PUBLIC KEY BLOCK-----';
        $privateKey = '-----BEGIN PGP PRIVATE KEY BLOCK-----';

        $key = (new GPGKey($keyId, $fingerprint, $email, $publicKey))
            ->setEncryptedPrivateKey($privateKey)
            ->markAsPublished()
            ->markAsPrimary();

        $this->assertSame($privateKey, $key->getEncryptedPrivateKey());
        $this->assertTrue($key->isPublished());
        $this->assertTrue($key->isPrimary());
    }
}
