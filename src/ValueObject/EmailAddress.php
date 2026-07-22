<?php
declare(strict_types=1);

namespace Ksf\GPG\ValueObject;

use Ksf\GPG\Exception\InvalidEmailException;

/**
 * Email Address Value Object
 * 
 * Immutable representation of an email address.
 * 
 * @UML Note: Class diagram in ProjectDocs/UML.md
 * @BABOK Related: FR-GPG-004 EmailAddress Value Object
 * 
 * @since 1.0.0
 */
class EmailAddress
{
    /**
     * @var string
     */
    private string $value;

    /**
     * Constructor
     *
     * @param string $email
     * @throws InvalidEmailException If email format is invalid
     *
     * @since 1.0.0
     */
    public function __construct(string $email)
    {
        $trimmed = trim($email);
        
        if (!$this->isValid($trimmed)) {
            throw new InvalidEmailException($email);
        }
        
        $this->value = strtolower($trimmed);
    }

    /**
     * Get the email address
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
     * Get the local part (before @)
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function getLocalPart(): string
    {
        $parts = explode('@', $this->value);
        return $parts[0];
    }

    /**
     * Get the domain part (after @)
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function getDomain(): string
    {
        $parts = explode('@', $this->value);
        return $parts[1];
    }

    /**
     * Check equality
     *
     * @param EmailAddress $other
     * @return bool
     *
     * @since 1.0.0
     */
    public function equals(EmailAddress $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * Validate email format
     *
     * @param string $email
     * @return bool
     *
     * @since 1.0.0
     */
    private function isValid(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
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
