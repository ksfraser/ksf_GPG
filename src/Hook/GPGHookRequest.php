<?php
declare(strict_types=1);

namespace ksfraser\GPG\Hook;

/**
 * Request DTO for GPG hook operations.
 *
 * Calling modules (CRM, HRM, Suppliers) construct this to request
 * signing, encryption, or combined operations on files.
 *
 * @since 1.0.0
 */
class GPGHookRequest
{
    /** Operation type constants */
    const OPERATION_SIGN = 'sign';
    const OPERATION_ENCRYPT = 'encrypt';
    const OPERATION_SIGN_ENCRYPT = 'sign_encrypt';
    const OPERATION_PASSWORD_ENCRYPT = 'password_encrypt';

    /** @var string Path to the file to operate on */
    private $filePath;

    /** @var string Operation type (one of OPERATION_* constants) */
    private $operation;

    /** @var GPGTarget[] Recipients */
    private $targets = [];

    /** @var string|null Sender contact type (for signing key resolution) */
    private $senderContactType;

    /** @var int|null Sender contact ID (for signing key resolution) */
    private $senderContactId;

    /** @var string|null Password for password-based encryption */
    private $password;

    /** @var bool Whether to use ASCII armor (default true) */
    private $asciiArmor = true;

    /**
     * @param string $filePath  Path to the file to operate on
     * @param string $operation Operation type (one of OPERATION_* constants)
     *
     * @since 1.0.0
     */
    public function __construct(string $filePath, string $operation)
    {
        $this->filePath = $filePath;
        $this->operation = $operation;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function getOperation(): string
    {
        return $this->operation;
    }

    /**
     * Add a recipient target.
     *
     * @param GPGTarget $target
     * @return self
     *
     * @since 1.0.0
     */
    public function addTarget(GPGTarget $target): self
    {
        $this->targets[] = $target;
        return $this;
    }

    /**
     * @return GPGTarget[]
     */
    public function getTargets(): array
    {
        return $this->targets;
    }

    public function getSenderContactType(): ?string
    {
        return $this->senderContactType;
    }

    public function setSenderContactType(string $type): self
    {
        $this->senderContactType = $type;
        return $this;
    }

    public function getSenderContactId(): ?int
    {
        return $this->senderContactId;
    }

    public function setSenderContactId(int $id): self
    {
        $this->senderContactId = $id;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function isAsciiArmor(): bool
    {
        return $this->asciiArmor;
    }

    public function setAsciiArmor(bool $asciiArmor): self
    {
        $this->asciiArmor = $asciiArmor;
        return $this;
    }
}
