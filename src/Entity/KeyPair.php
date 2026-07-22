<?php
declare(strict_types=1);

namespace Ksf\GPG\Entity;

use Ksf\GPG\ValueObject\EmailAddress;
use Ksf\GPG\ValueObject\Fingerprint;
use Ksf\GPG\ValueObject\KeyId;

/**
 * Key Pair Entity
 * 
 * Represents a GPG key pair (public + private).
 * 
 * @UML Note: Class diagram in ProjectDocs/UML.md
 * @BABOK Related: FR-GPG-002 Key Generation
 * 
 * @since 1.0.0
 */
class KeyPair
{
    /**
     * @var GPGKey
     */
    private GPGKey $publicKey;

    /**
     * @var string
     */
    private string $privateKey;

    /**
     * @var string
     */
    private string $passphrase;

    /**
     * Constructor
     *
     * @param GPGKey $publicKey
     * @param string $privateKey
     * @param string $passphrase
     *
     * @since 1.0.0
     */
    public function __construct(
        GPGKey $publicKey,
        string $privateKey,
        string $passphrase
    ) {
        $this->publicKey = $publicKey;
        $this->privateKey = $privateKey;
        $this->passphrase = $passphrase;
    }

    /**
     * Get public key entity
     *
     * @return GPGKey
     *
     * @since 1.0.0
     */
    public function getPublicKey(): GPGKey
    {
        return $this->publicKey;
    }

    /**
     * Get private key content
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function getPrivateKey(): string
    {
        return $this->privateKey;
    }

    /**
     * Get passphrase
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function getPassphrase(): string
    {
        return $this->passphrase;
    }

    /**
     * Get key ID
     *
     * @return KeyId
     *
     * @since 1.0.0
     */
    public function getKeyId(): KeyId
    {
        return $this->publicKey->getKeyId();
    }

    /**
     * Get fingerprint
     *
     * @return Fingerprint
     *
     * @since 1.0.0
     */
    public function getFingerprint(): Fingerprint
    {
        return $this->publicKey->getFingerprint();
    }

    /**
     * Get email
     *
     * @return EmailAddress
     *
     * @since 1.0.0
     */
    public function getEmail(): EmailAddress
    {
        return $this->publicKey->getEmail();
    }
}
