<?php
declare(strict_types=1);

namespace Ksf\GPG\Exception;

/**
 * Invalid Key ID Exception
 * 
 * Thrown when a key ID format is invalid.
 * 
 * @UML Note: Class diagram in ProjectDocs/UML.md
 * @BABOK Related: FR-GPG-011 Exception Hierarchy
 * 
 * @since 1.0.0
 */
class InvalidKeyIdException extends GPGException
{
    /**
     * Constructor
     *
     * @param string $keyId
     * @param int $code
     * @param \Throwable|null $previous
     *
     * @since 1.0.0
     */
    public function __construct(
        string $keyId,
        int $code = 0,
        \Throwable $previous = null
    ) {
        parent::__construct(
            "Invalid GPG key ID format: {$keyId}",
            $code,
            $previous
        );
    }
}
