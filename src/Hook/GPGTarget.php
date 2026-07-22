<?php
declare(strict_types=1);

namespace Ksf\GPG\Hook;

/**
 * Represents a single recipient target for a GPG operation.
 *
 * Used in hook requests to specify who the operation is for.
 * Calling modules construct these to identify recipients.
 *
 * @since 1.0.0
 */
class GPGTarget
{
    /** @var string Contact type (customer, supplier, employee, user) */
    private $contactType;

    /** @var int Contact ID */
    private $contactId;

    /** @var string|null Contact email (resolved by platform adapter) */
    private $email;

    /** @var string|null Pre-resolved fingerprint (skip key lookup) */
    private $fingerprint;

    /** @var bool Whether to fallback to password encryption if no key found */
    private $fallbackToPassword;

    /**
     * @param string $contactType Contact type (customer, supplier, employee, user)
     * @param int    $contactId   Contact ID
     * @param string|null $email  Pre-resolved email (optional)
     * @param string|null $fingerprint Pre-resolved fingerprint (optional)
     * @param bool   $fallbackToPassword Whether to fallback to password encryption
     *
     * @since 1.0.0
     */
    public function __construct(
        string $contactType,
        int $contactId,
        ?string $email = null,
        ?string $fingerprint = null,
        bool $fallbackToPassword = false
    ) {
        $this->contactType = $contactType;
        $this->contactId = $contactId;
        $this->email = $email;
        $this->fingerprint = $fingerprint;
        $this->fallbackToPassword = $fallbackToPassword;
    }

    public function getContactType(): string
    {
        return $this->contactType;
    }

    public function getContactId(): int
    {
        return $this->contactId;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getFingerprint(): ?string
    {
        return $this->fingerprint;
    }

    public function setFingerprint(string $fingerprint): self
    {
        $this->fingerprint = $fingerprint;
        return $this;
    }

    public function isFallbackToPassword(): bool
    {
        return $this->fallbackToPassword;
    }

    public function setFallbackToPassword(bool $fallback): self
    {
        $this->fallbackToPassword = $fallback;
        return $this;
    }
}
