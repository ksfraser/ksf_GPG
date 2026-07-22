<?php
declare(strict_types=1);

namespace Ksf\GPG\ValueObject;

use Ksf\GPG\Exception\InvalidFingerprintException;

/**
 * Fingerprint Value Object
 * 
 * Immutable representation of a GPG key fingerprint.
 * 
 * @UML Note: Class diagram in ProjectDocs/UML.md
 * @BABOK Related: FR-GPG-003 Fingerprint Value Object
 * 
 * @since 1.0.0
 */
class Fingerprint
{
    /**
     * @var string
     */
    private string $value;

    /**
     * Constructor
     *
     * @param string $fingerprint
     * @throws InvalidFingerprintException If fingerprint format is invalid
     *
     * @since 1.0.0
     */
    public function __construct(string $fingerprint)
    {
        $cleaned = $this->clean($fingerprint);
        
        if (!$this->isValid($cleaned)) {
            throw new InvalidFingerprintException($fingerprint);
        }
        
        $this->value = $cleaned;
    }

    /**
     * Get the fingerprint value
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
     * Get formatted fingerprint (XXXX XXXX XXXX ...)
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function getFormatted(): string
    {
        $chunks = str_split($this->value, 4);
        return implode(' ', $chunks);
    }

    /**
     * Get last 8 characters (short key ID)
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
     * @param Fingerprint $other
     * @return bool
     *
     * @since 1.0.0
     */
    public function equals(Fingerprint $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * Clean fingerprint string
     *
     * @param string $fingerprint
     * @return string
     *
     * @since 1.0.0
     */
    private function clean(string $fingerprint): string
    {
        return preg_replace('/[\s:]/', '', strtoupper($fingerprint));
    }

    /**
     * Validate fingerprint format
     *
     * @param string $fingerprint
     * @return bool
     *
     * @since 1.0.0
     */
    private function isValid(string $fingerprint): bool
    {
        return preg_match('/^[0-9A-F]{40}$/', $fingerprint) === 1;
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
