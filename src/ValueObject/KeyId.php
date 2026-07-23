<?php
declare(strict_types=1);

namespace ksfraser\GPG\ValueObject;

use ksfraser\GPG\Exception\InvalidKeyIdException;

/**
 * Key ID Value Object
 * 
 * Immutable representation of a GPG key ID.
 * 
 * @UML Note: Class diagram in ProjectDocs/UML.md
 * @BABOK Related: FR-GPG-003 KeyId Value Object
 * 
 * @since 1.0.0
 */
class KeyId
{
    /**
     * @var string
     */
    private string $value;

    /**
     * Constructor
     *
     * @param string $keyId
     * @throws InvalidKeyIdException If key ID format is invalid
     *
     * @since 1.0.0
     */
    public function __construct(string $keyId)
    {
        $cleaned = $this->clean($keyId);
        
        if (!$this->isValid($cleaned)) {
            throw new InvalidKeyIdException($keyId);
        }
        
        $this->value = $cleaned;
    }

    /**
     * Get the key ID value
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Get the key ID as hex string
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function getHex(): string
    {
        return $this->value;
    }

    /**
     * Check if this is a short key ID (8 chars)
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function isShort(): bool
    {
        return strlen($this->value) === 8;
    }

    /**
     * Check if this is a long key ID (16 chars)
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function isLong(): bool
    {
        return strlen($this->value) === 16;
    }

    /**
     * Get short key ID (last 8 chars)
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function getShortId(): string
    {
        return substr($this->value, -8);
    }

    /**
     * Check equality
     *
     * @param KeyId $other
     * @return bool
     *
     * @since 1.0.0
     */
    public function equals(KeyId $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * Clean key ID string
     *
     * @param string $keyId
     * @return string
     *
     * @since 1.0.0
     */
    private function clean(string $keyId): string
    {
        $cleaned = preg_replace('/[\s:]/', '', strtoupper($keyId));
        
        // Remove 0x prefix if present
        if (strpos($cleaned, '0X') === 0) {
            $cleaned = substr($cleaned, 2);
        }
        
        return $cleaned;
    }

    /**
     * Validate key ID format (8 or 16 hex chars)
     *
     * @param string $keyId
     * @return bool
     *
     * @since 1.0.0
     */
    private function isValid(string $keyId): bool
    {
        return preg_match('/^[0-9A-F]{8,16}$/', $keyId) === 1;
    }

    /**
     * String representation
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function __toString(): string
    {
        return $this->value;
    }
}
