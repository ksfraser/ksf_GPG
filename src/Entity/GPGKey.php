<?php
declare(strict_types=1);

namespace Ksf\GPG\Entity;

use Ksf\GPG\ValueObject\EmailAddress;
use Ksf\GPG\ValueObject\Fingerprint;
use Ksf\GPG\ValueObject\KeyId;

/**
 * GPG Key Entity
 * 
 * Represents a GPG key with public and optional encrypted private key.
 * 
 * @UML Note: Class diagram in ProjectDocs/UML.md
 * @BABOK Related: FR-GPG-003 Key Entity
 * 
 * @since 1.0.0
 */
class GPGKey
{
    /**
     * @var KeyId
     */
    private KeyId $keyId;

    /**
     * @var Fingerprint
     */
    private Fingerprint $fingerprint;

    /**
     * @var EmailAddress
     */
    private EmailAddress $email;

    /**
     * @var string
     */
    private string $publicKey;

    /**
     * @var string|null
     */
    private ?string $encryptedPrivateKey = null;

    /**
     * @var bool
     */
    private bool $isPublished = false;

    /**
     * @var bool
     */
    private bool $isPrimary = false;

    /**
     * @var \DateTimeImmutable
     */
    private \DateTimeImmutable $createdAt;

    /**
     * @var \DateTimeImmutable
     */
    private \DateTimeImmutable $modifiedAt;

    /**
     * Constructor
     *
     * @param KeyId $keyId
     * @param Fingerprint $fingerprint
     * @param EmailAddress $email
     * @param string $publicKey
     *
     * @since 1.0.0
     */
    public function __construct(
        KeyId $keyId,
        Fingerprint $fingerprint,
        EmailAddress $email,
        string $publicKey
    ) {
        $this->keyId = $keyId;
        $this->fingerprint = $fingerprint;
        $this->email = $email;
        $this->publicKey = $publicKey;
        $this->createdAt = new \DateTimeImmutable();
        $this->modifiedAt = new \DateTimeImmutable();
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
        return $this->keyId;
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
        return $this->fingerprint;
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
        return $this->email;
    }

    /**
     * Get public key
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    /**
     * Get encrypted private key
     *
     * @return string|null
     *
     * @since 1.0.0
     */
    public function getEncryptedPrivateKey(): ?string
    {
        return $this->encryptedPrivateKey;
    }

    /**
     * Check if key is published
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function isPublished(): bool
    {
        return $this->isPublished;
    }

    /**
     * Check if key is primary
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function isPrimary(): bool
    {
        return $this->isPrimary;
    }

    /**
     * Get created at
     *
     * @return \DateTimeImmutable
     *
     * @since 1.0.0
     */
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Get modified at
     *
     * @return \DateTimeImmutable
     *
     * @since 1.0.0
     */
    public function getModifiedAt(): \DateTimeImmutable
    {
        return $this->modifiedAt;
    }

    /**
     * Set encrypted private key
     *
     * @param string $key
     * @return self
     *
     * @since 1.0.0
     */
    public function setEncryptedPrivateKey(string $key): self
    {
        $this->encryptedPrivateKey = $key;
        $this->modifiedAt = new \DateTimeImmutable();
        return $this;
    }

    /**
     * Mark as published
     *
     * @return self
     *
     * @since 1.0.0
     */
    public function markAsPublished(): self
    {
        $this->isPublished = true;
        $this->modifiedAt = new \DateTimeImmutable();
        return $this;
    }

    /**
     * Mark as primary
     *
     * @return self
     *
     * @since 1.0.0
     */
    public function markAsPrimary(): self
    {
        $this->isPrimary = true;
        $this->modifiedAt = new \DateTimeImmutable();
        return $this;
    }
}
