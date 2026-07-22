<?php
declare(strict_types=1);

namespace Ksf\GPG\Tests\Unit\Entity;

use PHPUnit\Framework\TestCase;
use Ksf\GPG\Entity\KeyPair;
use Ksf\GPG\Entity\GPGKey;
use Ksf\GPG\ValueObject\KeyId;
use Ksf\GPG\ValueObject\Fingerprint;
use Ksf\GPG\ValueObject\EmailAddress;

class KeyPairTest extends TestCase
{
    public function testConstructor(): void
    {
        $keyId = new KeyId('12345678');
        $fingerprint = new Fingerprint('1234567890ABCDEF1234567890ABCDEF12345678');
        $email = new EmailAddress('test@example.com');
        $publicKey = '-----BEGIN PGP PUBLIC KEY BLOCK-----';
        $privateKey = '-----BEGIN PGP PRIVATE KEY BLOCK-----';
        $passphrase = 'test-passphrase';

        $gpgKey = new GPGKey($keyId, $fingerprint, $email, $publicKey);
        $keyPair = new KeyPair($gpgKey, $privateKey, $passphrase);

        $this->assertSame($gpgKey, $keyPair->getPublicKey());
        $this->assertSame($privateKey, $keyPair->getPrivateKey());
        $this->assertSame($passphrase, $keyPair->getPassphrase());
        $this->assertSame($keyId, $keyPair->getKeyId());
        $this->assertSame($fingerprint, $keyPair->getFingerprint());
        $this->assertSame($email, $keyPair->getEmail());
    }
}
