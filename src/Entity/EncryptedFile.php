<?php
declare(strict_types=1);

namespace Ksf\GPG\Entity;

/**
 * EncryptedFile Entity
 * 
 * Represents a file that has been signed and/or encrypted.
 * 
 * @UML Note: Class diagram in ProjectDocs/UML.md
 * @BABOK Related: FR-GPG-009 EncryptedFile Entity
 * 
 * @since 1.0.0
 */
class EncryptedFile
{
    /**
     * @var string
     */
    private string $originalPath;

    /**
     * @var string|null
     */
    private ?string $encryptedPath = null;

    /**
     * @var string|null
     */
    private ?string $signedPath = null;

    /**
     * @var bool
     */
    private bool $isSigned = false;

    /**
     * @var bool
     */
    private bool $isEncrypted = false;

    /**
     * @var bool
     */
    private bool $isPasswordProtected = false;

    /**
     * @var string|null
     */
    private ?string $recipientEmail = null;

    /**
     * @var \DateTimeImmutable
     */
    private \DateTimeImmutable $createdAt;

    /**
     * Constructor
     *
     * @param string $originalPath
     *
     * @since 1.0.0
     */
    public function __construct(string $originalPath)
    {
        $this->originalPath = $originalPath;
        $this->createdAt = new \DateTimeImmutable();
    }

    /**
     * Get original file path
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function getOriginalPath(): string
    {
        return $this->originalPath;
    }

    /**
     * Get encrypted file path
     *
     * @return string|null
     *
     * @since 1.0.0
     */
    public function getEncryptedPath(): ?string
    {
        return $this->encryptedPath;
    }

    /**
     * Get signed file path
     *
     * @return string|null
     *
     * @since 1.0.0
     */
    public function getSignedPath(): ?string
    {
        return $this->signedPath;
    }

    /**
     * Check if file is signed
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function isSigned(): bool
    {
        return $this->isSigned;
    }

    /**
     * Check if file is encrypted
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function isEncrypted(): bool
    {
        return $this->isEncrypted;
    }

    /**
     * Check if file is password protected
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function isPasswordProtected(): bool
    {
        return $this->isPasswordProtected;
    }

    /**
     * Get recipient email
     *
     * @return string|null
     *
     * @since 1.0.0
     */
    public function getRecipientEmail(): ?string
    {
        return $this->recipientEmail;
    }

    /**
     * Get created at timestamp
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
     * Set encrypted path
     *
     * @param string $path
     * @return self
     *
     * @since 1.0.0
     */
    public function setEncryptedPath(string $path): self
    {
        $this->encryptedPath = $path;
        $this->isEncrypted = true;
        return $this;
    }

    /**
     * Set signed path
     *
     * @param string $path
     * @return self
     *
     * @since 1.0.0
     */
    public function setSignedPath(string $path): self
    {
        $this->signedPath = $path;
        $this->isSigned = true;
        return $this;
    }

    /**
     * Set password protected flag
     *
     * @param bool $protected
     * @return self
     *
     * @since 1.0.0
     */
    public function setPasswordProtected(bool $protected): self
    {
        $this->isPasswordProtected = $protected;
        return $this;
    }

    /**
     * Set recipient email
     *
     * @param string $email
     * @return self
     *
     * @since 1.0.0
     */
    public function setRecipientEmail(string $email): self
    {
        $this->recipientEmail = $email;
        return $this;
    }

    /**
     * Get the path to use for email attachment
     * 
     * Returns encrypted path if encrypted, otherwise original path.
     * This is what calling modules should pass to EmailManager.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function getAttachmentPath(): string
    {
        if ($this->isEncrypted && $this->encryptedPath !== null) {
            return $this->encryptedPath;
        }
        
        return $this->originalPath;
    }
}
