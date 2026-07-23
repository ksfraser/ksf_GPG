<?php
declare(strict_types=1);

namespace ksfraser\GPG\Exception;

/**
 * Encryption Failed Exception
 * 
 * Thrown when encryption or decryption fails.
 * 
 * @UML Note: Class diagram in ProjectDocs/UML.md
 * @BABOK Related: FR-GPG-011 Exception Hierarchy
 * 
 * @since 1.0.0
 */
class EncryptionFailedException extends GPGException
{
    /**
     * Constructor
     *
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     *
     * @since 1.0.0
     */
    public function __construct(
        string $message = "",
        int $code = 0,
        \Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
